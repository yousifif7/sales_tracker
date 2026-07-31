<?php

namespace App\Services;

use App\Models\Contact;
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
        'james smith',
        'joe bloggs',
        'joe blogs',
        'a n other',
        'another person',
        'test user',
        'test lead',
        'sample name',
        'full name',
        'first last',
        'firstname lastname',
        'decision maker',
        'contact person',
        'managing director',
        'n/a',
        'unknown',
        'tbd',
        'bobby',
        'mike',
        'admin',
        'info',
        'support',
    ];

    /** @var array<string, mixed>|null */
    protected ?array $existingContactIndexCache = null;

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
        $maxLeads = $this->resolveMaxLeads($criteria, (int) ($webSearch['max_leads'] ?? 8));
        $minLeads = max(1, min($maxLeads, (int) ($webSearch['min_leads'] ?? 5)));
        $maxUses = max(1, (int) ($webSearch['max_uses'] ?? 8));
        $maxResults = max(1, (int) ($webSearch['max_results'] ?? 6));
        $maxTotalResults = max($maxResults, (int) ($webSearch['max_total_results'] ?? 30));
        $maxToolCalls = max($maxUses, (int) ($webSearch['max_tool_calls'] ?? $maxUses));
        $maxCharacters = max(800, (int) ($webSearch['max_characters'] ?? 2800));
        $requireWebEvidence = (bool) ($webSearch['require_web_evidence'] ?? true);
        $refillMaxUses = max(1, (int) ($webSearch['refill_max_uses'] ?? 6));

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

        $rawResponse = $this->chatCompletion(
            model: $model,
            system: $this->systemPrompt(),
            user: $this->buildPrompt($criteria, $maxLeads, $minLeads, $maxUses),
            toolParameters: $toolParameters,
            maxToolCalls: $maxToolCalls,
        );

        $results = $this->finalizeResults(
            $this->parseResults((string) data_get($rawResponse, 'choices.0.message.content', '[]')),
            $rawResponse,
            $requireWebEvidence,
        );

        // Second pass: convert leftover citation companies into real named leads.
        if (count($results) < $minLeads) {
            $needed = $minLeads - count($results);
            $candidates = $this->candidateCompaniesFromCitations($rawResponse, $results);

            if ($candidates !== []) {
                $refillTools = $toolParameters;
                $refillTools['max_uses'] = $refillMaxUses;
                $refillTools['max_total_results'] = max(12, (int) ($refillTools['max_total_results'] ?? 12));

                $refillResponse = $this->chatCompletion(
                    model: $model,
                    system: $this->systemPrompt(),
                    user: $this->buildRefillPrompt($criteria, $candidates, $needed),
                    toolParameters: $refillTools,
                    maxToolCalls: $refillMaxUses,
                );

                $refillResults = $this->finalizeResults(
                    $this->parseResults((string) data_get($refillResponse, 'choices.0.message.content', '[]')),
                    $refillResponse,
                    $requireWebEvidence,
                );

                $results = $this->mergeLeads($results, $refillResults, $maxLeads);
                $rawResponse['refill'] = $refillResponse;
            }
        }

        return [
            'results' => array_slice($results, 0, $maxLeads),
            'raw_response' => $rawResponse,
        ];
    }

    /**
     * @param  array<string, mixed>  $toolParameters
     * @return array<string, mixed>
     */
    protected function chatCompletion(
        string $model,
        string $system,
        string $user,
        array $toolParameters,
        int $maxToolCalls,
    ): array {
        $response = $this->http
            ->withToken(config('openrouter.api_key'))
            ->acceptJson()
            ->timeout(150)
            ->post(rtrim(config('openrouter.base_url'), '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
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

        if (! is_array($payload) || ! is_string(data_get($payload, 'choices.0.message.content'))) {
            throw new RuntimeException('OpenRouter did not return a message payload.');
        }

        return $payload;
    }

    protected function systemPrompt(): string
    {
        return implode("\n", [
            'You are a B2B lead research assistant with live web search for UK security companies.',
            'Return usable outreach leads with REAL full names (first + last).',
            'Never invent people. Never use placeholders (John Doe, Jane Doe, John Smith, James Smith, Joe Bloggs).',
            'Never return a single first name only (e.g. Bobby, Mike).',
            'Workflow: shortlist company sites → open about/team/meet-the-team pages → extract Owner/MD/Director full name.',
            'Soft ICP preferences are optional clues, not hard rejects.',
            'Return strict JSON array only.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $leads
     * @param  array<string, mixed>  $rawResponse
     * @return array<int, array<string, mixed>>
     */
    protected function finalizeResults(array $leads, array $rawResponse, bool $requireWebEvidence): array
    {
        if ($requireWebEvidence) {
            $leads = $this->retainEvidenceBackedLeads($leads, $rawResponse);
        }

        $leads = $this->excludeExistingContacts($leads);

        return $this->dedupeByCompany($leads);
    }

    /**
     * Keep one outreach contact per company (prefer MD/Owner over secondary directors).
     *
     * @param  array<int, array<string, mixed>>  $leads
     * @return array<int, array<string, mixed>>
     */
    protected function dedupeByCompany(array $leads): array
    {
        $bestByKey = [];

        foreach ($leads as $lead) {
            $key = $this->leadDedupeKey($lead);
            if ($key === null) {
                $bestByKey['row:'.count($bestByKey)] = $lead;

                continue;
            }

            if (! isset($bestByKey[$key]) || $this->rolePriority($lead) > $this->rolePriority($bestByKey[$key])) {
                $bestByKey[$key] = $lead;
            }
        }

        return array_values($bestByKey);
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function rolePriority(array $lead): int
    {
        $role = strtolower((string) ($lead['role'] ?? ''));

        return match (true) {
            str_contains($role, 'owner'), str_contains($role, 'founder'), str_contains($role, 'co-owner') => 100,
            str_contains($role, 'managing director'), $role === 'md' => 90,
            str_contains($role, 'ceo'), str_contains($role, 'chief executive') => 85,
            str_contains($role, 'director') && ! str_contains($role, 'operations') => 70,
            str_contains($role, 'operations director') => 50,
            default => 10,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $existing
     * @param  array<int, array<string, mixed>>  $incoming
     * @return array<int, array<string, mixed>>
     */
    protected function mergeLeads(array $existing, array $incoming, int $maxLeads): array
    {
        return array_slice(
            $this->dedupeByCompany(array_merge($existing, $incoming)),
            0,
            $maxLeads,
        );
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function leadDedupeKey(array $lead): ?string
    {
        $host = $this->normalizeHost($lead['website'] ?? null)
            ?? $this->normalizeHost($lead['source_url'] ?? null);
        if ($host !== null) {
            return 'host:'.$host;
        }

        $company = $this->normalizeCompanyName(isset($lead['company']) ? (string) $lead['company'] : null);
        if ($company !== null) {
            return 'company:'.$company;
        }

        return null;
    }

    /**
     * Pull unmatched company hosts/URLs from web citations for a refill pass.
     *
     * @param  array<string, mixed>  $rawResponse
     * @param  array<int, array<string, mixed>>  $acceptedLeads
     * @return list<array{host: string, url: string}>
     */
    protected function candidateCompaniesFromCitations(array $rawResponse, array $acceptedLeads): array
    {
        $index = $this->existingContactIndex();
        $acceptedHosts = [];

        foreach ($acceptedLeads as $lead) {
            foreach ([$lead['website'] ?? null, $lead['source_url'] ?? null] as $url) {
                $host = $this->normalizeHost($url);
                if ($host !== null) {
                    $acceptedHosts[$host] = true;
                }
            }
        }

        $blockedHosts = [
            'linkedin.com', 'facebook.com', 'instagram.com', 'twitter.com', 'x.com',
            'youtube.com', 'google.com', 'bing.com', 'gov.uk', 'companieshouse.gov.uk',
            'wikipedia.org', 'yell.com', 'thomsonlocal.com', 'exa.ai', 'crunchbase.com',
        ];

        $candidates = [];

        foreach ($this->extractCitations($rawResponse) as $citation) {
            $host = $this->normalizeHost($citation['url']);
            if ($host === null || isset($acceptedHosts[$host]) || isset($index['host_set'][$host])) {
                continue;
            }

            foreach ($blockedHosts as $blocked) {
                if ($host === $blocked || str_ends_with($host, '.'.$blocked)) {
                    continue 2;
                }
            }

            if (! isset($candidates[$host])) {
                $candidates[$host] = [
                    'host' => $host,
                    'url' => $citation['url'],
                ];
            }
        }

        return array_values(array_slice($candidates, 0, 10));
    }

    /**
     * @param  list<array{host: string, url: string}>  $candidates
     */
    protected function buildRefillPrompt(string $criteria, array $candidates, int $needed): string
    {
        $lines = collect($candidates)
            ->map(fn (array $c): string => '- '.$c['host'].' ('.$c['url'].')')
            ->implode("\n");

        $excludeBlock = $this->buildExclusionPromptBlock();

        return <<<PROMPT
The previous search found these candidate company websites but did not return enough verified leads.

Original brief:
{$criteria}

Candidate companies to convert into leads (need {$needed} more — one person per company):
{$lines}

For each candidate:
1) Open that company's about / team / meet-the-team / contact page (same domain preferred).
2) Extract ONE real full name (first + last) of Owner / Managing Director / Director / Founder.
3) Include public email if shown, else null.
4) Prefer UK decision-makers for UK companies. Ignore Australian / unrelated companies with similar names.

{$excludeBlock}

Return JSON array ONLY in this exact shape (field names must match):
[
  {
    "name": "First Last",
    "role": "Managing Director",
    "company": "Company Name",
    "email": null,
    "website": "https://company-domain.co.uk",
    "linkedin_url": null,
    "company_linkedin_url": null,
    "social_links": {},
    "source_url": "https://company-domain.co.uk/about"
  }
]

Hard rules:
- Use "name" (not full_name). Use "email" (not public_email).
- source_url must be on the company website domain, not exa.ai / LinkedIn scrapers.
- Full first + last name required. No placeholders.
- Skip a company if you cannot verify a named decision-maker on their site.
- One lead per company. Return up to {$needed} leads.
PROMPT;
    }

    protected function resolveModel(): string
    {
        $model = trim((string) config('openrouter.model'));

        // :online is deprecated; web search is attached via tools instead.
        return preg_replace('/:online$/i', '', $model) ?: $model;
    }

    /**
     * Honor "max N" in the user's criteria when it is higher than config.
     */
    protected function resolveMaxLeads(string $criteria, int $configuredMax): int
    {
        $max = max(1, min(15, $configuredMax));

        if (preg_match('/\bmax(?:imum)?\s*[:=]?\s*(\d{1,2})\b/i', $criteria, $match) === 1) {
            $max = max($max, min(15, (int) $match[1]));
        }

        return $max;
    }

    protected function buildPrompt(string $criteria, int $maxLeads, int $minLeads, int $maxUses): string
    {
        $excludeBlock = $this->buildExclusionPromptBlock();

        return <<<PROMPT
Find sales leads that match:

{$criteria}

Mandatory search plan (max {$maxUses} web searches):
1) Discover at least {$maxLeads} distinct candidate company websites that match the region + service type.
2) Skip companies/domains in the CRM exclude list below.
3) For EVERY remaining candidate, open about / team / meet-the-team / our-people / contact pages.
4) Extract a REAL full name (first AND last) with role Owner / Managing Director / Director / Founder.
5) Include public email if shown (info@ is fine). If no email, still include the lead with email null.
6) Keep going until you have at least {$minLeads} verified leads (target {$maxLeads}).

{$excludeBlock}

Return JSON array only:
[
  {
    "name": "First Last",
    "role": "Owner / MD / Director / Founder",
    "company": "Company name",
    "email": "public email or null",
    "website": "https://company-website.com",
    "linkedin_url": "https://www.linkedin.com/in/... or null",
    "company_linkedin_url": null,
    "social_links": {},
    "source_url": "https://about-or-team-page-that-names-the-person"
  }
]

Hard rules:
- JSON only.
- Minimum {$minLeads} leads when candidate firms exist. Target {$maxLeads}.
- One decision-maker per company (prefer Owner / Managing Director).
- name MUST be a real first + last name found on the source page.
- Forbidden: John Doe, Jane Doe, John Smith, James Smith, Joe Bloggs, single first names (Bobby, Mike), invented people.
- Soft preferences in the criteria are NOT hard disqualifiers.
- Skip only clear mismatches or exclude-list companies.
- Max {$maxLeads} leads.
PROMPT;
    }

    /**
     * Tell the model which CRM contacts/companies to skip.
     */
    protected function buildExclusionPromptBlock(): string
    {
        $index = $this->existingContactIndex();

        if ($index['companies'] === [] && $index['emails'] === [] && $index['hosts'] === []) {
            return "CRM exclude list: (empty — no existing contacts yet)";
        }

        // Keep this compact — a huge exclude list burns tokens and makes the model under-return.
        $companies = array_slice($index['companies'], 0, 25);
        $hosts = array_slice($index['hosts'], 0, 25);

        $lines = ['CRM exclude list (already in our database — skip these companies/domains):'];

        if ($companies !== []) {
            $lines[] = '- Companies: '.implode('; ', $companies);
        }

        if ($hosts !== []) {
            $lines[] = '- Domains: '.implode('; ', $hosts);
        }

        return implode("\n", $lines);
    }

    /**
     * Drop leads that already exist as contacts (email, domain, company, LinkedIn, name+company).
     *
     * @param  array<int, array<string, mixed>>  $leads
     * @return array<int, array<string, mixed>>
     */
    public function excludeExistingContacts(array $leads): array
    {
        $index = $this->existingContactIndex();

        return collect($leads)
            ->reject(fn (array $lead): bool => $this->leadMatchesExistingContact($lead, $index))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     emails: list<string>,
     *     hosts: list<string>,
     *     companies: list<string>,
     *     linkedin: list<string>,
     *     name_company: list<string>,
     *     email_set: array<string, true>,
     *     host_set: array<string, true>,
     *     company_set: array<string, true>,
     *     linkedin_set: array<string, true>,
     *     name_company_set: array<string, true>
     * }
     */
    protected function existingContactIndex(): array
    {
        if ($this->existingContactIndexCache !== null) {
            return $this->existingContactIndexCache;
        }

        $emails = [];
        $hosts = [];
        $companies = [];
        $linkedin = [];
        $nameCompany = [];

        Contact::query()
            ->select(['name', 'company', 'email', 'website', 'linkedin_url'])
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->each(function (Contact $contact) use (&$emails, &$hosts, &$companies, &$linkedin, &$nameCompany): void {
                if (filled($contact->email)) {
                    $email = strtolower(trim((string) $contact->email));
                    $emails[] = $email;

                    $domain = $this->emailDomain($email);
                    if ($domain !== null) {
                        $hosts[] = $domain;
                    }
                }

                $websiteHost = $this->normalizeHost($contact->website);
                if ($websiteHost !== null) {
                    $hosts[] = $websiteHost;
                }

                $company = $this->normalizeCompanyName($contact->company);
                if ($company !== null) {
                    $companies[] = $company;
                }

                $linkedinUrl = $this->normalizeLinkedInUrl($contact->linkedin_url, person: true);
                if ($linkedinUrl !== null) {
                    $linkedin[] = strtolower($linkedinUrl);
                }

                $name = $this->normalizePersonName($contact->name);
                if ($name !== null && $company !== null) {
                    $nameCompany[] = $name.'|'.$company;
                }
            });

        $emails = array_values(array_unique($emails));
        $hosts = array_values(array_unique($hosts));
        $companies = array_values(array_unique($companies));
        $linkedin = array_values(array_unique($linkedin));
        $nameCompany = array_values(array_unique($nameCompany));

        return $this->existingContactIndexCache = [
            'emails' => $emails,
            'hosts' => $hosts,
            'companies' => $companies,
            'linkedin' => $linkedin,
            'name_company' => $nameCompany,
            'email_set' => array_fill_keys($emails, true),
            'host_set' => array_fill_keys($hosts, true),
            'company_set' => array_fill_keys($companies, true),
            'linkedin_set' => array_fill_keys($linkedin, true),
            'name_company_set' => array_fill_keys($nameCompany, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $lead
     * @param  array<string, mixed>  $index
     */
    protected function leadMatchesExistingContact(array $lead, array $index): bool
    {
        $email = isset($lead['email']) ? strtolower(trim((string) $lead['email'])) : '';
        if ($email !== '' && isset($index['email_set'][$email])) {
            return true;
        }

        $linkedin = $this->normalizeLinkedInUrl($lead['linkedin_url'] ?? null, person: true);
        if ($linkedin !== null && isset($index['linkedin_set'][strtolower($linkedin)])) {
            return true;
        }

        $company = $this->normalizeCompanyName(isset($lead['company']) ? (string) $lead['company'] : null);
        if ($company !== null && isset($index['company_set'][$company])) {
            return true;
        }

        foreach ([$lead['website'] ?? null, $lead['source_url'] ?? null] as $url) {
            $host = $this->normalizeHost($url);
            if ($host !== null && isset($index['host_set'][$host])) {
                return true;
            }
        }

        if ($email !== '') {
            $domain = $this->emailDomain($email);
            if ($domain !== null && isset($index['host_set'][$domain])) {
                return true;
            }
        }

        $name = $this->normalizePersonName(isset($lead['name']) ? (string) $lead['name'] : null);
        if ($name !== null && $company !== null && isset($index['name_company_set'][$name.'|'.$company])) {
            return true;
        }

        return false;
    }

    protected function normalizeCompanyName(?string $company): ?string
    {
        if (! filled($company)) {
            return null;
        }

        $name = strtolower(trim($company));
        $name = preg_replace('/\b(limited|ltd\.?|llp|llc|inc\.?|plc|co\.|company)\b/i', '', $name) ?? $name;
        $name = preg_replace('/[^a-z0-9\s&]/', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name);

        return $name !== '' ? $name : null;
    }

    protected function normalizePersonName(?string $name): ?string
    {
        if (! filled($name)) {
            return null;
        }

        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

        return ($normalized !== '' && $normalized !== 'null') ? $normalized : null;
    }

    protected function emailDomain(string $email): ?string
    {
        $parts = explode('@', strtolower(trim($email)), 2);
        $domain = $parts[1] ?? '';
        $domain = preg_replace('/^www\./', '', $domain) ?? $domain;

        if ($domain === '' || in_array($domain, ['gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com', 'live.com', 'msn.com'], true)) {
            return null;
        }

        return $domain;
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
            ->filter(fn (mixed $lead): bool => is_array($lead))
            ->map(function (array $lead): ?array {
                $name = $lead['name'] ?? $lead['full_name'] ?? $lead['person_name'] ?? null;
                if (! $this->isUsableLeadName($name)) {
                    return null;
                }

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
                $sourceUrl = $this->normalizeUrl($lead['source_url'] ?? null);

                if ($this->isUntrustedSourceUrl($sourceUrl)) {
                    $sourceUrl = null;
                }

                $sourceUrl = $sourceUrl ?? $website ?? $linkedinUrl;

                if ($this->isUntrustedSourceUrl($sourceUrl)) {
                    return null;
                }

                return [
                    'name' => trim((string) $name),
                    'role' => $this->nullableTrimmedString($lead['role'] ?? $lead['title'] ?? null),
                    'company' => $this->nullableTrimmedString($lead['company'] ?? $lead['company_name'] ?? null),
                    'email' => $this->normalizeEmail($lead['email'] ?? $lead['public_email'] ?? null),
                    'website' => $website,
                    'linkedin_url' => $linkedinUrl,
                    'social_links' => $socialLinks,
                    'source_url' => $sourceUrl,
                ];
            })
            ->filter(fn (?array $lead): bool => is_array($lead) && $this->isCredibleLead($lead))
            ->values()
            ->all();
    }

    protected function isUntrustedSourceUrl(?string $url): bool
    {
        $host = $this->normalizeHost($url);

        if ($host === null) {
            return false;
        }

        foreach (['exa.ai', 'perplexity.ai', 'openai.com', 'bing.com', 'google.com'] as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.'.$blocked)) {
                return true;
            }
        }

        return false;
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
                $websiteHost = $this->normalizeHost($lead['website'] ?? null);
                $sourceHost = $this->normalizeHost($lead['source_url'] ?? null);

                $matchedHosts = array_values(array_filter(
                    [$websiteHost, $sourceHost],
                    fn (?string $host): bool => $host !== null && isset($citedHosts[$host]),
                ));

                if ($matchedHosts === []) {
                    return null;
                }

                $name = (string) ($lead['name'] ?? '');
                $hostEvidence = collect($matchedHosts)
                    ->flatMap(fn (string $host) => $citationTextsByHost[$host] ?? [])
                    ->implode("\n");
                $evidenceBlob = $hostEvidence !== ''
                    ? $hostEvidence
                    : trim($allCitationText);

                if ($evidenceBlob !== '' && ! $this->personNameAppearsInEvidence($name, $evidenceBlob)) {
                    return null;
                }

                // Prefer aligning website to a cited company host when domains differ slightly.
                if ($websiteHost !== null && ! isset($citedHosts[$websiteHost]) && $sourceHost !== null && isset($citedHosts[$sourceHost])) {
                    $lead['website'] = $lead['source_url'];
                }

                $linkedin = $lead['linkedin_url'] ?? null;

                if (is_string($linkedin) && $linkedin !== '') {
                    $linkedinPath = rtrim(strtolower((string) (parse_url($linkedin, PHP_URL_PATH) ?? '')), '/');
                    $linkedinHost = $this->normalizeHost($linkedin);

                    // Drop non-UK LinkedIn noise and unverified profiles.
                    if (
                        $linkedinHost !== null && str_starts_with($linkedinHost, 'au.')
                        || $linkedinPath === ''
                        || ! isset($citedLinkedInPaths[$linkedinPath])
                    ) {
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
        $name = (string) ($lead['name'] ?? '');

        if (! $this->hasFullPersonName($name) || $this->isPlaceholderName($name)) {
            return false;
        }

        // Require a verifiable company web presence — directory-only / invented rows usually lack this.
        if (! filled($lead['website'] ?? null) && ! filled($lead['source_url'] ?? null)) {
            return false;
        }

        return true;
    }

    protected function hasFullPersonName(string $name): bool
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $name) ?? '');

        if ($normalized === '' || strtolower($normalized) === 'null') {
            return false;
        }

        $parts = preg_split('/\s+/', $normalized) ?: [];

        if (count($parts) < 2) {
            return false;
        }

        foreach ($parts as $part) {
            $letters = preg_replace('/[^a-zA-Z\-]/', '', $part) ?? '';
            if (strlen($letters) < 2) {
                return false;
            }
        }

        return true;
    }

    protected function isPlaceholderName(string $name): bool
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

        if (in_array($normalized, $this->placeholderNames, true)) {
            return true;
        }

        // Catch "Mr John Doe" style filler.
        foreach ($this->placeholderNames as $placeholder) {
            if (str_contains($normalized, $placeholder)) {
                return true;
            }
        }

        return false;
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

        return $this->hasFullPersonName($trimmed) && ! $this->isPlaceholderName($trimmed);
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
