<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class LeadSearchService
{
    /**
     * Names the model commonly invents when it cannot find a real contact.
     *
     * @var list<string>
     */
    protected array $placeholderNames = [
        'john doe',
        'jane doe',
        'john smith',
        'jane smith',
        'test user',
        'test lead',
        'sample name',
        'full name',
        'decision maker',
        'contact person',
        'n/a',
        'unknown',
        'tbd',
    ];

    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, raw_response: array<string, mixed>}
     */
    public function search(string $criteria): array
    {
        $model = $this->resolveModel();
        $webSearch = config('openrouter.web_search', []);
        $maxLeads = max(1, (int) ($webSearch['max_leads'] ?? 5));
        $maxUses = max(1, (int) ($webSearch['max_uses'] ?? 5));
        $maxResults = max(1, (int) ($webSearch['max_results'] ?? 4));
        $maxTotalResults = max($maxResults, (int) ($webSearch['max_total_results'] ?? 16));
        $maxToolCalls = max(1, (int) ($webSearch['max_tool_calls'] ?? $maxUses));
        $maxCharacters = max(800, (int) ($webSearch['max_characters'] ?? 2200));
        $requireWebEvidence = (bool) ($webSearch['require_web_evidence'] ?? true);

        $toolParameters = [
            'engine' => (string) ($webSearch['engine'] ?? 'auto'),
            'max_results' => $maxResults,
            'max_uses' => $maxUses,
            'max_total_results' => $maxTotalResults,
            'search_context_size' => (string) ($webSearch['search_context_size'] ?? 'medium'),
            'max_characters' => $maxCharacters,
            'user_location' => [
                'type' => 'approximate',
                'country' => 'GB',
                'timezone' => 'Europe/London',
            ],
        ];

        $response = $this->http
            ->withToken(config('openrouter.api_key'))
            ->acceptJson()
            ->timeout(150)
            ->post(rtrim(config('openrouter.base_url'), '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => implode("\n", [
                            'You are a careful B2B lead research assistant with live web search.',
                            'Only report contacts you can verify from web sources returned in this request.',
                            'Never invent, guess, or pad results with placeholder people or URLs.',
                            'Prefer fewer verified leads over a longer unverified list.',
                            'Search efficiently: discover candidates, then verify decision-makers on about/team pages.',
                            'Return strict JSON only: no markdown, no commentary.',
                        ]),
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($criteria, $maxLeads, $maxUses),
                    ],
                ],
                'temperature' => 0,
                'tools' => [
                    [
                        'type' => 'openrouter:web_search',
                        'parameters' => $toolParameters,
                    ],
                ],
                'max_tool_calls' => $maxToolCalls,
            ]);

        $response->throw();

        $payload = $response->json();
        $content = data_get($payload, 'choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('OpenRouter did not return a message payload.');
        }

        $rawResponse = is_array($payload) ? $payload : [];
        $results = $this->parseResults($content);

        if ($requireWebEvidence) {
            $results = $this->retainEvidenceBackedLeads($results, $rawResponse);
        }

        return [
            'results' => $results,
            'raw_response' => $rawResponse,
        ];
    }

    protected function resolveModel(): string
    {
        $model = trim((string) config('openrouter.model'));

        // :online is deprecated; web search is attached via tools instead.
        return preg_replace('/:online$/i', '', $model) ?: $model;
    }

    protected function buildPrompt(string $criteria, int $maxLeads, int $maxUses): string
    {
        return <<<PROMPT
Find sales leads that match:

{$criteria}

Search plan (max {$maxUses} web searches — batch aggressively):
1) Find candidate companies that match the criteria (region + ICP).
2) Verify each keepable company on its own about/team/contact page: named owner/MD/director + website email if shown.
3) Only if still needed, confirm that person on LinkedIn or Companies House.
Stop as soon as you have {$maxLeads} verified leads. Do not keep searching for padding.

Return JSON array only, shape:
[
  {
    "name": "Decision maker full name",
    "role": "Owner / MD / Director / Founder",
    "company": "Company name",
    "email": "public email if available",
    "website": "https://company-website.com",
    "linkedin_url": "https://www.linkedin.com/in/person-profile",
    "company_linkedin_url": "https://www.linkedin.com/company/company-page",
    "social_links": {},
    "source_url": "https://page-you-used-to-verify"
  }
]

Rules:
- JSON only. No markdown.
- Include a lead ONLY when the named decision-maker appears in a retrieved source (company about/team page preferred).
- source_url must be that verification page.
- Never invent names, emails, or LinkedIn URLs. Use JSON null when unknown (never the string "null").
- No placeholder names (John Doe, Jane Doe, Test User, etc.).
- Skip firms that fail the user's disqualify rules.
- Max {$maxLeads} leads. Fewer (or []) is required when evidence is thin.
PROMPT;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseResults(string $content): array
    {
        $normalized = trim($content);

        if (str_starts_with($normalized, '```')) {
            $normalized = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $normalized) ?? $normalized;
            $normalized = trim($normalized);
        }

        $decoded = json_decode($normalized, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Lead search response was not valid JSON.');
        }

        // Allow either a bare array or {"results": [...]}.
        if (array_is_list($decoded) === false) {
            $decoded = $decoded['results'] ?? $decoded['leads'] ?? null;

            if (! is_array($decoded)) {
                throw new RuntimeException('Lead search response was not valid JSON.');
            }
        }

        return collect($decoded)
            ->filter(fn (mixed $lead): bool => is_array($lead) && $this->isUsableLeadName($lead['name'] ?? null))
            ->map(function (array $lead): array {
                $socialLinks = $this->normalizeSocialLinks($lead['social_links'] ?? []);

                $companyLinkedIn = $this->normalizeLinkedInUrl(
                    $lead['company_linkedin_url'] ?? null,
                    person: false,
                );

                if ($companyLinkedIn !== null) {
                    $socialLinks['company_linkedin'] = $companyLinkedIn;
                }

                $website = $this->normalizeUrl($lead['website'] ?? $lead['company_website'] ?? null);
                $linkedinUrl = $this->normalizeLinkedInUrl(
                    $lead['linkedin_url'] ?? $lead['linkedin'] ?? null,
                    person: true,
                );
                $sourceUrl = $this->normalizeUrl($lead['source_url'] ?? null)
                    ?? $website
                    ?? $linkedinUrl;

                return [
                    'name' => trim((string) ($lead['name'] ?? '')),
                    'role' => $this->nullableTrimmedString($lead['role'] ?? null),
                    'company' => $this->nullableTrimmedString($lead['company'] ?? null),
                    'email' => $this->normalizeEmail($lead['email'] ?? null),
                    'website' => $website,
                    'linkedin_url' => $linkedinUrl,
                    'social_links' => $socialLinks,
                    'source_url' => $sourceUrl,
                ];
            })
            ->filter(fn (array $lead): bool => $this->isCredibleLead($lead))
            ->values()
            ->all();
    }

    /**
     * Keep only leads evidenced by OpenRouter web-search citations.
     * If the provider returned no citations, leave parsed leads unchanged.
     *
     * @param  array<int, array<string, mixed>>  $leads
     * @param  array<string, mixed>  $rawResponse
     * @return array<int, array<string, mixed>>
     */
    protected function retainEvidenceBackedLeads(array $leads, array $rawResponse): array
    {
        $citations = $this->extractCitations($rawResponse);

        if ($citations === []) {
            return $leads;
        }

        $citedHosts = [];
        $citedLinkedInPaths = [];
        $citationTextsByHost = [];

        foreach ($citations as $citation) {
            $url = $citation['url'];
            $host = $this->normalizeHost($url);
            $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?? ''));

            if ($host !== null) {
                $citedHosts[$host] = true;
                $citationTextsByHost[$host][] = $citation['content'];
            }

            if ($host !== null && str_contains($host, 'linkedin.com') && str_starts_with($path, '/in/')) {
                $citedLinkedInPaths[rtrim($path, '/')] = true;
            }
        }

        $allCitationText = collect($citations)
            ->pluck('content')
            ->filter()
            ->implode("\n");

        return collect($leads)
            ->map(function (array $lead) use ($citedHosts, $citedLinkedInPaths, $citationTextsByHost, $allCitationText): ?array {
                $companyHost = $this->normalizeHost($lead['website'] ?? null)
                    ?? $this->normalizeHost($lead['source_url'] ?? null);

                if ($companyHost === null || ! isset($citedHosts[$companyHost])) {
                    return null;
                }

                $name = (string) ($lead['name'] ?? '');
                $hostEvidence = implode("\n", $citationTextsByHost[$companyHost] ?? []);
                $evidenceBlob = $hostEvidence !== ''
                    ? $hostEvidence
                    : trim($allCitationText);

                if ($evidenceBlob !== '' && ! $this->personNameAppearsInEvidence($name, $evidenceBlob)) {
                    return null;
                }

                $linkedin = $lead['linkedin_url'] ?? null;

                if (is_string($linkedin) && $linkedin !== '') {
                    $linkedinPath = rtrim(strtolower((string) (parse_url($linkedin, PHP_URL_PATH) ?? '')), '/');

                    if ($linkedinPath === '' || ! isset($citedLinkedInPaths[$linkedinPath])) {
                        $lead['linkedin_url'] = null;
                    }
                }

                return $lead;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     * @return array<int, array{url: string, content: string}>
     */
    protected function extractCitations(array $rawResponse): array
    {
        $annotations = data_get($rawResponse, 'choices.0.message.annotations');

        if (! is_array($annotations)) {
            return [];
        }

        return collect($annotations)
            ->map(function (mixed $annotation): ?array {
                if (! is_array($annotation)) {
                    return null;
                }

                $url = data_get($annotation, 'url_citation.url')
                    ?? data_get($annotation, 'url')
                    ?? null;

                if (! is_string($url) || $this->normalizeUrl($url) === null) {
                    return null;
                }

                $content = data_get($annotation, 'url_citation.content')
                    ?? data_get($annotation, 'url_citation.title')
                    ?? '';

                return [
                    'url' => $url,
                    'content' => is_string($content) ? $content : '',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeHost(mixed $url): ?string
    {
        $normalized = $this->normalizeUrl($url);

        if ($normalized === null) {
            return null;
        }

        $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return $host !== '' ? $host : null;
    }

    protected function personNameAppearsInEvidence(string $name, string $evidence): bool
    {
        $normalizedName = strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

        if ($normalizedName === '') {
            return false;
        }

        $haystack = strtolower($evidence);

        if (str_contains($haystack, $normalizedName)) {
            return true;
        }

        $parts = preg_split('/\s+/', $normalizedName) ?: [];

        if (count($parts) < 2) {
            return false;
        }

        $first = $parts[0];
        $last = $parts[array_key_last($parts)];

        return strlen($first) >= 2
            && strlen($last) >= 2
            && str_contains($haystack, $first)
            && str_contains($haystack, $last);
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function isCredibleLead(array $lead): bool
    {
        if ($this->isPlaceholderName((string) ($lead['name'] ?? ''))) {
            return false;
        }

        // Require a verifiable company web presence — directory-only / invented rows usually lack this.
        if (! filled($lead['website'] ?? null) && ! filled($lead['source_url'] ?? null)) {
            return false;
        }

        return true;
    }

    protected function isPlaceholderName(string $name): bool
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

        return in_array($normalized, $this->placeholderNames, true);
    }

    /**
     * @param  mixed  $links
     * @return array<string, string>
     */
    protected function normalizeSocialLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        return collect($links)
            ->filter(fn (mixed $url, mixed $key): bool => is_string($key) && filled($url))
            ->mapWithKeys(function (mixed $url, string $key): array {
                $normalized = $this->normalizeUrl((string) $url);

                return $normalized ? [strtolower($key) => $normalized] : [];
            })
            ->all();
    }

    protected function normalizeLinkedInUrl(mixed $url, bool $person): ?string
    {
        $normalized = $this->normalizeUrl($url);

        if ($normalized === null) {
            return null;
        }

        $path = strtolower((string) (parse_url($normalized, PHP_URL_PATH) ?? ''));
        $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?? ''));

        if (! str_contains($host, 'linkedin.com')) {
            return null;
        }

        if ($person) {
            if (! preg_match('#^/in/([^/]+)/?$#', $path, $matches)) {
                return null;
            }

            $slug = $matches[1];

            if ($this->isPlaceholderLinkedInSlug($slug)) {
                return null;
            }
        } elseif (! preg_match('#^/company/([^/]+)/?$#', $path, $matches)) {
            return null;
        } elseif ($this->isPlaceholderLinkedInSlug($matches[1])) {
            return null;
        }

        return $normalized;
    }

    protected function isPlaceholderLinkedInSlug(string $slug): bool
    {
        $slug = strtolower(trim($slug));

        if ($slug === '' || in_array($slug, ['null', 'undefined', 'n-a', 'na', 'none', 'example', 'placeholder', 'test', 'user', 'name'], true)) {
            return true;
        }

        // Common LLM filler like jordan-wiggins-123456 / person-000000
        if (preg_match('/-(?:12345|123456|00000|000000|99999|999999)$/', $slug) === 1) {
            return true;
        }

        return false;
    }

    protected function normalizeEmail(mixed $email): ?string
    {
        $value = $this->nullableTrimmedString($email);

        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    protected function normalizeUrl(mixed $url): ?string
    {
        $url = $this->nullableTrimmedString($url);

        if ($url === null) {
            return null;
        }

        // Models sometimes emit the literal words null/undefined as URL values.
        if (preg_match('/^(https?:\/\/)?(null|undefined|n\/?a|none)$/i', $url) === 1) {
            return null;
        }

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));

        if ($host === '' || in_array($host, ['null', 'undefined', 'example.com', 'example.org'], true)) {
            return null;
        }

        return $url;
    }

    protected function isUsableLeadName(mixed $name): bool
    {
        if (! is_string($name) && ! is_numeric($name)) {
            return false;
        }

        $trimmed = trim((string) $name);

        return $trimmed !== '' && strtolower($trimmed) !== 'null';
    }

    protected function nullableTrimmedString(mixed $value): ?string
    {
        if (! filled($value) || (! is_string($value) && ! is_numeric($value))) {
            return null;
        }

        $trimmed = trim((string) $value);

        return ($trimmed === '' || strtolower($trimmed) === 'null') ? null : $trimmed;
    }
}
