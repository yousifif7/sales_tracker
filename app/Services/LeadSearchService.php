<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class LeadSearchService
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, raw_response: array<string, mixed>}
     */
    public function search(string $criteria): array
    {
        $response = $this->http
            ->withToken(config('openrouter.api_key'))
            ->acceptJson()
            ->timeout(90)
            ->post(rtrim(config('openrouter.base_url'), '/').'/chat/completions', [
                'model' => config('openrouter.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a sales lead research assistant. Prefer real public URLs found online. Return strict JSON only with no markdown fences and no commentary.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($criteria),
                    ],
                ],
                'temperature' => 0.2,
            ]);

        $response->throw();

        $payload = $response->json();
        $content = data_get($payload, 'choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('OpenRouter did not return a message payload.');
        }

        return [
            'results' => $this->parseResults($content),
            'raw_response' => is_array($payload) ? $payload : [],
        ];
    }

    protected function buildPrompt(string $criteria): string
    {
        return <<<PROMPT
Find sales leads that match the following criteria:

{$criteria}

Return strict JSON as an array of objects using this exact shape:
[
  {
    "name": "Decision maker full name",
    "role": "Owner / MD / Director / Founder",
    "company": "Company name",
    "email": "public email if available",
    "website": "https://company-website.com",
    "linkedin_url": "https://www.linkedin.com/in/person-profile",
    "company_linkedin_url": "https://www.linkedin.com/company/company-page",
    "social_links": {
      "instagram": "https://instagram.com/...",
      "facebook": "https://facebook.com/...",
      "x": "https://x.com/...",
      "other": "https://..."
    },
    "source_url": "https://page-you-used-to-verify"
  }
]

Rules:
- Return JSON only. No markdown.
- Prioritize a real person to contact (owner, founder, MD, director).
- Always try to include website and LinkedIn person URL when publicly available.
- Include company LinkedIn and other socials only if found.
- Use null for unknown values. For social_links use {} or omit empty keys.
- Prefer absolute https URLs.
- Limit results to 10 leads.
- Do not invent emails or profile URLs. If unsure, use null.
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

        return collect($decoded)
            ->filter(fn (mixed $lead): bool => is_array($lead) && $this->isUsableLeadName($lead['name'] ?? null))
            ->map(function (array $lead): array {
                $socialLinks = $this->normalizeSocialLinks($lead['social_links'] ?? []);

                if (filled($lead['company_linkedin_url'] ?? null)) {
                    $socialLinks['company_linkedin'] = $this->normalizeUrl((string) $lead['company_linkedin_url']);
                }

                return [
                    'name' => trim((string) ($lead['name'] ?? '')),
                    'role' => $this->nullableTrimmedString($lead['role'] ?? null),
                    'company' => $this->nullableTrimmedString($lead['company'] ?? null),
                    'email' => $this->nullableTrimmedString($lead['email'] ?? null),
                    'website' => $this->normalizeUrl($lead['website'] ?? $lead['company_website'] ?? null),
                    'linkedin_url' => $this->normalizeUrl($lead['linkedin_url'] ?? $lead['linkedin'] ?? null),
                    'social_links' => $socialLinks,
                    'source_url' => $this->normalizeUrl($lead['source_url'] ?? null)
                        ?? $this->normalizeUrl($lead['website'] ?? null)
                        ?? $this->normalizeUrl($lead['linkedin_url'] ?? null),
                ];
            })
            ->values()
            ->all();
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

    protected function normalizeUrl(mixed $url): ?string
    {
        if (! filled($url) || ! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
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
