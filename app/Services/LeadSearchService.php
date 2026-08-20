<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
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
        'michael johnson',
        'michael smith',
        'sarah brown',
        'sarah johnson',
        'david smith',
        'david jones',
        'robert jones',
        'robert smith',
        'james wilson',
        'emily wilson',
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

    /**
     * Country-code / regional TLDs that are never the UK company we want.
     *
     * @var list<string>
     */
    protected array $nonUkTldSuffixes = [
        '.com.au', '.co.nz', '.co.za', '.co.in', '.com.br', '.co.jp', '.com.sg',
        '.au', '.nz', '.us', '.ca', '.za', '.in', '.br', '.jp', '.sg', '.ae',
        '.ie', '.de', '.fr', '.es', '.it', '.nl', '.be', '.ch', '.at', '.pl',
        '.se', '.no', '.dk', '.fi', '.pt', '.mx', '.ar', '.cl', '.cn', '.hk',
        '.kr', '.tw', '.ph', '.my', '.id', '.pk', '.ng', '.ke', '.eg',
    ];

    /** @var array<string, mixed>|null */
    protected ?array $existingContactIndexCache = null;

    /**
     * Why leads were dropped on each pass, so a thin run explains itself.
     *
     * @var array<int, array<string, int|string>>
     */
    protected array $passDiagnostics = [];

    public function __construct(
        protected HttpFactory $http,
    ) {}

    /**
     * @return array{results: array<int, array<string, mixed>>, raw_response: array<string, mixed>, diagnostics: array<string, mixed>}
     */
    public function search(string $criteria): array
    {
        $this->passDiagnostics = [];

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
        $minNewLeads = max(1, min($maxLeads, (int) ($webSearch['min_new_leads'] ?? 2)));
        $maxDiversifyAttempts = max(0, (int) ($webSearch['max_diversify_attempts'] ?? 3));

        $toolParameters = $this->buildWebSearchToolParameters(
            engine: (string) ($webSearch['engine'] ?? 'exa'),
            maxResults: $maxResults,
            maxUses: $maxUses,
            maxTotalResults: $maxTotalResults,
            searchContextSize: (string) ($webSearch['search_context_size'] ?? 'medium'),
            maxCharacters: $maxCharacters,
        );

        $rawResponse = $this->chatCompletion(
            model: $model,
            system: $this->systemPrompt(),
            user: $this->buildPrompt($criteria, $maxLeads, $minLeads, $maxUses),
            toolParameters: $toolParameters,
            maxToolCalls: $maxToolCalls,
        );

        $parsedResults = $this->parseResults((string) data_get($rawResponse, 'choices.0.message.content', '[]'));

        $results = $this->finalizeResults(
            $parsedResults,
            $rawResponse,
            $requireWebEvidence,
        );

        // Second pass: convert leftover citation companies into real named leads.
        if (count($results) < $minLeads) {
            $needed = $minLeads - count($results);
            $candidates = $this->candidateCompaniesFromCitations($rawResponse, $parsedResults);

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
                    'refill',
                );

                $results = $this->mergeLeads($results, $refillResults, $maxLeads);
                $rawResponse['refill'] = $refillResponse;
            }
        }

        // Every pass above can legitimately return leads we already have in the CRM, which
        // leaves the run empty. Keep asking for different companies until we hit the floor.
        $seenHosts = $this->hostsFromLeads($parsedResults);

        for ($attempt = 1; $attempt <= $maxDiversifyAttempts && count($results) < $minNewLeads; $attempt++) {
            $blockedHosts = $this->blockedHostsForDiversify($seenHosts);

            if ($blockedHosts === []) {
                break;
            }

            $retryResponse = $this->chatCompletion(
                model: $model,
                system: $this->systemPrompt(),
                user: $this->buildDiversifyPrompt($criteria, $blockedHosts, $maxLeads, max($minLeads, $minNewLeads), $maxUses),
                toolParameters: $toolParameters,
                maxToolCalls: $maxToolCalls,
            );

            $parsedRetry = $this->parseResults((string) data_get($retryResponse, 'choices.0.message.content', '[]'));
            $retryResults = $this->finalizeResults(
                $parsedRetry,
                $retryResponse,
                $requireWebEvidence,
                'diversify_'.$attempt,
            );

            if ($retryResults !== []) {
                $results = $this->mergeLeads($results, $retryResults, $maxLeads);
            }

            $rawResponse[$attempt === 1 ? 'diversify_retry' : 'diversify_retry_'.$attempt] = $retryResponse;

            $nextSeenHosts = array_values(array_unique(array_merge($seenHosts, $this->hostsFromLeads($parsedRetry))));

            // The model is out of new companies — stop instead of burning more credits.
            if ($nextSeenHosts === $seenHosts) {
                break;
            }

            $seenHosts = $nextSeenHosts;
        }

        $results = array_slice($results, 0, $maxLeads);

        return [
            'results' => $results,
            'diagnostics' => $this->buildDiagnostics($results, $minNewLeads),
            'raw_response' => $rawResponse,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    protected function buildDiagnostics(array $results, int $minNewLeads): array
    {
        $sum = fn (string $key): int => (int) collect($this->passDiagnostics)->sum($key);

        $droppedExisting = $sum('dropped_already_in_crm');
        $droppedUnverified = $sum('dropped_unverified');
        $droppedWrongCountry = $sum('dropped_wrong_country');
        $droppedDuplicate = $sum('dropped_duplicate_company');

        $summary = count($results) >= $minNewLeads
            ? null
            : trim(sprintf(
                'Only %d usable lead(s) from %d passes. The model returned %d companies: %d already in your CRM, %d unverifiable from citations, %d wrong-country/namesake, %d duplicate companies. %s',
                count($results),
                count($this->passDiagnostics),
                $sum('returned_by_model'),
                $droppedExisting,
                $droppedUnverified,
                $droppedWrongCountry,
                $droppedDuplicate,
                $droppedWrongCountry > 0
                    ? 'The model mixed in non-UK namesakes — retry with a county + “SIA” / site:.co.uk.'
                    : ($droppedExisting > 0
                        ? 'This region/ICP looks close to exhausted — widen the region or loosen the size range.'
                        : 'Try a broader region or allow general company emails.'),
            ));

        return [
            'min_new_leads' => $minNewLeads,
            'usable_leads' => count($results),
            'model_returned' => $sum('returned_by_model'),
            'dropped_already_in_crm' => $droppedExisting,
            'dropped_unverified' => $droppedUnverified,
            'dropped_wrong_country' => $droppedWrongCountry,
            'dropped_duplicate_company' => $droppedDuplicate,
            'passes' => $this->passDiagnostics,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildWebSearchToolParameters(
        string $engine,
        int $maxResults,
        int $maxUses,
        int $maxTotalResults,
        string $searchContextSize,
        int $maxCharacters,
        bool $minimal = false,
    ): array {
        $parameters = [
            'engine' => $engine !== '' ? $engine : 'exa',
            'max_results' => max(1, min(25, $maxResults)),
            'max_uses' => max(1, $maxUses),
            'max_total_results' => max(1, $maxTotalResults),
        ];

        if ($minimal) {
            return $parameters;
        }

        $parameters['search_context_size'] = in_array($searchContextSize, ['low', 'medium', 'high'], true)
            ? $searchContextSize
            : 'medium';
        $parameters['max_characters'] = max(800, min(100000, $maxCharacters));

        // Exa/Perplexity ignore this; keep it only for native-capable models when asked.
        if (($parameters['engine'] === 'native' || $parameters['engine'] === 'auto')) {
            $parameters['user_location'] = [
                'type' => 'approximate',
                'country' => 'GB',
                'timezone' => 'Europe/London',
            ];
        }

        return $parameters;
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
        try {
            return $this->postChatCompletion($model, $system, $user, $toolParameters, $maxToolCalls);
        } catch (RequestException $exception) {
            if (! $this->isServerToolFailure($exception)) {
                throw $this->wrapOpenRouterException($exception);
            }

            // OpenRouter often 400s on rich web_search params / auto engine — retry lean Exa.
            $fallback = $this->buildWebSearchToolParameters(
                engine: 'exa',
                maxResults: min(5, (int) ($toolParameters['max_results'] ?? 5)),
                maxUses: min(4, (int) ($toolParameters['max_uses'] ?? 4)),
                maxTotalResults: min(12, (int) ($toolParameters['max_total_results'] ?? 12)),
                searchContextSize: 'low',
                maxCharacters: 1500,
                minimal: true,
            );

            try {
                return $this->postChatCompletion(
                    $model,
                    $system,
                    $user,
                    $fallback,
                    min($maxToolCalls, (int) $fallback['max_uses']),
                );
            } catch (RequestException $retryException) {
                throw $this->wrapOpenRouterException($retryException);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $toolParameters
     * @return array<string, mixed>
     */
    protected function postChatCompletion(
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
                'max_tool_calls' => max(1, min(30, $maxToolCalls)),
            ]);

        $response->throw();

        $payload = $response->json();

        if (! is_array($payload) || ! is_string(data_get($payload, 'choices.0.message.content'))) {
            throw new RuntimeException('OpenRouter did not return a message payload.');
        }

        return $payload;
    }

    protected function isServerToolFailure(RequestException $exception): bool
    {
        $status = $exception->response?->status();
        $body = (string) ($exception->response?->body() ?? '');

        return $status === 400
            && (
                str_contains($body, 'Server tool request failed')
                || str_contains($body, 'server tool')
                || str_contains($body, 'web_search')
            );
    }

    protected function wrapOpenRouterException(RequestException $exception): RuntimeException
    {
        $status = $exception->response?->status();
        $message = (string) data_get($exception->response?->json(), 'error.message', $exception->getMessage());
        $provider = data_get($exception->response?->json(), 'error.metadata.provider_name');

        $detail = trim(sprintf(
            'OpenRouter lead search failed (%s): %s%s',
            $status ?? 'unknown',
            $message,
            filled($provider) ? " [provider: {$provider}]" : '',
        ));

        return new RuntimeException($detail, (int) ($status ?? 0), $exception);
    }

    /**
     * True when retrying the queue job will not help (bad tool config / provider outage shape).
     */
    public function isNonRetryableFailure(\Throwable $exception): bool
    {
        if ($exception instanceof RequestException) {
            return $this->isServerToolFailure($exception);
        }

        $previous = $exception->getPrevious();

        return $previous instanceof RequestException && $this->isServerToolFailure($previous);
    }

    protected function systemPrompt(): string
    {
        return implode("\n", [
            'You are a B2B lead research assistant with live web search for United Kingdom security companies only.',
            'Return usable outreach leads with REAL full names (first + last) currently at that UK company.',
            'Hard geographic rule: United Kingdom only. Never return US, Australian, Canadian, Irish, or other non-UK companies.',
            'Same-name trap: a US/AU clone is not the UK firm (e.g. OMS Group LLC / omsgroupllc.com is not UK OMS Group / oms.co.uk). Use the UK domain.',
            'Prefer .co.uk / .uk websites. A .com is allowed only when the page itself is clearly the UK firm (SIA, Companies House, UK address or +44).',
            'Never invent people. Never use placeholders (John Doe, Jane Doe, John Smith, Michael Johnson, Sarah Brown, James Smith, Joe Bloggs).',
            'Never return a single first name only (e.g. Bobby, Mike).',
            'If you cannot verify a real named UK decision-maker, omit that company. An empty JSON array is better than guesses.',
            'Workflow: search “manned guarding” + UK county + SIA → open the UK about/team page → extract Owner/MD/Director full name.',
            'Skip clear ICP mismatches (pure keyholding, already-have-an-app, mega-brands). Do not skip a UK guarding firm just because size is uncertain.',
            'Return strict JSON array only.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $leads
     * @param  array<string, mixed>  $rawResponse
     * @return array<int, array<string, mixed>>
     */
    protected function finalizeResults(array $leads, array $rawResponse, bool $requireWebEvidence, string $pass = 'initial'): array
    {
        $returned = count($leads);
        $leads = $this->retainUkLeads($leads, $rawResponse);
        $ukLeads = count($leads);

        if ($requireWebEvidence) {
            $leads = $this->retainEvidenceBackedLeads($leads, $rawResponse);
        }

        $verified = count($leads);
        $leads = $this->excludeExistingContacts($leads);
        $new = count($leads);
        $leads = $this->dedupeByCompany($leads);

        $this->passDiagnostics[] = [
            'pass' => $pass,
            'returned_by_model' => $returned,
            'dropped_wrong_country' => $returned - $ukLeads,
            'dropped_unverified' => $ukLeads - $verified,
            'dropped_already_in_crm' => $verified - $new,
            'dropped_duplicate_company' => $new - count($leads),
            'kept' => count($leads),
        ];

        return $leads;
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

            if ($this->hostIsClearlyNonUk($host)) {
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
1) Confirm it is the UNITED KINGDOM company (SIA, Companies House, UK address, .co.uk). Skip US/AU/CA namesakes even if the trading name matches.
2) Open that company's about / team / meet-the-team / contact page (same UK domain preferred).
3) Extract ONE real full name (first + last) of Owner / Managing Director / Director / Founder.
4) Include public email and phone if shown, else null.

{$excludeBlock}

Return JSON array ONLY in this exact shape (field names must match):
[
  {
    "name": "First Last",
    "role": "Managing Director",
    "company": "Company Name",
    "email": null,
    "phone": null,
    "website": "https://company-domain.co.uk",
    "linkedin_url": null,
    "company_linkedin_url": null,
    "social_links": {},
    "source_url": "https://company-domain.co.uk/about"
  }
]

Hard rules:
- Use "name" (not full_name). Use "email" (not public_email).
- source_url must be on the UK company website domain, not exa.ai / LinkedIn scrapers / foreign clones.
- Full first + last name required. No placeholders (John Smith, Michael Johnson, Sarah Brown).
- Skip a company if you cannot verify a named UK decision-maker on their site. Empty array is fine.
- One lead per company. Return up to {$needed} leads.
PROMPT;
    }

    /**
     * @param  list<string>  $blockedHosts
     */
    protected function buildDiversifyPrompt(string $criteria, array $blockedHosts, int $maxLeads, int $minLeads, int $maxUses): string
    {
        $excludeBlock = $this->buildExclusionPromptBlock();
        $blocked = implode(', ', array_slice($blockedHosts, 0, 40));

        return <<<PROMPT
Find sales leads that match:

{$criteria}

The previous attempt returned companies that are already in our CRM or duplicate domains.
Find DIFFERENT companies this time.

Do not return any lead from these domains:
{$blocked}

Mandatory search plan (max {$maxUses} web searches):
1) Discover distinct NEW UK candidate company websites (query must include UK or a UK county + “SIA” or “manned guarding”; prefer site:.co.uk).
2) Skip companies/domains in the CRM exclude list below and skip the blocked domains above.
3) Skip any non-UK namesake (LLC, Inc, .com.au, United States, Australia) even if the trading name matches a UK firm.
4) For EVERY remaining UK candidate, open about / team / meet-the-team / our-people / contact pages.
5) Extract a REAL full name (first AND last) with role Owner / Managing Director / Director / Founder.
6) Include public email and phone if shown (info@ is fine). If missing, still include the lead with nulls.

{$excludeBlock}

Return JSON array only:
[
  {
    "name": "First Last",
    "role": "Owner / MD / Director / Founder",
    "company": "Company name",
    "email": "public email or null",
    "phone": "public phone or null",
    "website": "https://company-website.co.uk",
    "linkedin_url": "https://www.linkedin.com/in/... or null",
    "company_linkedin_url": null,
    "social_links": {},
    "source_url": "https://about-or-team-page-that-names-the-person"
  }
]

Hard rules:
- JSON only. United Kingdom companies only.
- Return NEW companies only, not the blocked domains.
- Aim for at least {$minLeads} verified NEW leads (Target {$maxLeads}). Never invent names to hit a quota — omit or return [] instead.
- Forbidden placeholders: John Doe, Jane Doe, John Smith, Michael Johnson, Sarah Brown, James Smith, Joe Bloggs.
- One decision-maker per company (prefer Owner / Managing Director).
- name MUST be a real first + last name found on the UK source page.
- Max {$maxLeads} leads.
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
1) Discover distinct UK candidate company websites (every query must include UK or a UK county + “SIA” or “manned guarding”; prefer site:.co.uk).
2) Skip companies/domains in the CRM exclude list below.
3) Skip non-UK namesakes (US LLC, Australia, Canada, Ireland) even when the trading name matches.
4) For EVERY remaining UK candidate, open about / team / meet-the-team / our-people / contact pages.
5) Extract a REAL full name (first AND last) with role Owner / Managing Director / Director / Founder.
6) Include public email and phone if shown (info@ is fine). If missing, still include the lead with nulls.

{$excludeBlock}

Return JSON array only:
[
  {
    "name": "First Last",
    "role": "Owner / MD / Director / Founder",
    "company": "Company name",
    "email": "public email or null",
    "phone": "public phone or null",
    "website": "https://company-website.co.uk",
    "linkedin_url": "https://www.linkedin.com/in/... or null",
    "company_linkedin_url": null,
    "social_links": {},
    "source_url": "https://about-or-team-page-that-names-the-person"
  }
]

Hard rules:
- JSON only. United Kingdom companies only.
- Aim for at least {$minLeads} verified leads (Target {$maxLeads}). Never invent names to hit a quota — omit or return [] instead.
- One decision-maker per company (prefer Owner / Managing Director).
- name MUST be a real first + last name found on the UK source page.
- Forbidden: John Doe, Jane Doe, John Smith, Michael Johnson, Sarah Brown, James Smith, Joe Bloggs, single first names (Bobby, Mike), invented people.
- Country is a hard reject. Other ICP preferences are not hard disqualifiers when size is uncertain.
- Skip only clear mismatches, foreign namesakes, or exclude-list companies.
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
            return 'CRM exclude list: (empty — no existing contacts yet)';
        }

        // Truncating this list makes the model re-suggest contacts we already have,
        // which then get filtered out and waste the whole run.
        $companies = array_slice($index['companies'], 0, 60);
        $hosts = array_slice($index['hosts'], 0, 60);

        $lines = ['CRM exclude list (already in our database — skip these companies/domains):'];

        if ($companies !== []) {
            $lines[] = '- Companies: '.implode('; ', $companies);
        }

        if ($hosts !== []) {
            $lines[] = '- Domains: '.implode('; ', $hosts);
        }

        $lines[] = 'Leads from the exclude list are discarded, so returning them wastes the run. Find different companies instead.';

        return implode("\n", $lines);
    }

    /**
     * Domains the next pass must avoid: everything already returned this run plus
     * every company already sitting in the CRM.
     *
     * @param  list<string>  $seenHosts
     * @return list<string>
     */
    protected function blockedHostsForDiversify(array $seenHosts): array
    {
        $index = $this->existingContactIndex();

        return array_values(array_unique(array_merge(
            $seenHosts,
            array_slice($index['hosts'], 0, 60),
        )));
    }

    /**
     * @param  array<int, array<string, mixed>>  $leads
     * @return list<string>
     */
    protected function hostsFromLeads(array $leads): array
    {
        return collect($leads)
            ->flatMap(function (array $lead): array {
                return [
                    $this->normalizeHost($lead['website'] ?? null),
                    $this->normalizeHost($lead['source_url'] ?? null),
                ];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
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
                    'phone' => $this->normalizePhone($lead['phone'] ?? $lead['telephone'] ?? $lead['mobile'] ?? null),
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

                // A company page often shows only a first name ("Gennine, Director") while the
                // full name is proven by another citation (LinkedIn, directory). Accept either.
                $evidenceBlob = trim($hostEvidence."\n".$allCitationText);

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

                $title = data_get($annotation, 'url_citation.title') ?? '';
                $snippet = data_get($annotation, 'url_citation.content') ?? '';
                $content = trim(implode("\n", array_filter([
                    is_string($title) ? $title : '',
                    is_string($snippet) ? $snippet : '',
                ])));

                return [
                    'url' => $url,
                    'content' => $content,
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

        // Catch "Mr John Doe" style filler. Single-token placeholders (mike, bobby)
        // must not match real people like "Mike Thompson" or "Bobby Gaze".
        foreach ($this->placeholderNames as $placeholder) {
            if (! str_contains($placeholder, ' ')) {
                continue;
            }

            if (str_contains($normalized, $placeholder)) {
                return true;
            }
        }

        return false;
    }

    /**
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

    protected function normalizePhone(mixed $phone): ?string
    {
        $value = $this->nullableTrimmedString($phone);

        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) < 7) {
            return null;
        }

        return $value;
    }

    /**
     * Drop US/AU/CA namesakes and other clearly non-UK companies.
     *
     * @param  array<int, array<string, mixed>>  $leads
     * @param  array<string, mixed>  $rawResponse
     * @return array<int, array<string, mixed>>
     */
    protected function retainUkLeads(array $leads, array $rawResponse): array
    {
        $citationTextsByHost = [];

        foreach ($this->extractCitations($rawResponse) as $citation) {
            $host = $this->normalizeHost($citation['url']);
            if ($host !== null) {
                $citationTextsByHost[$host][] = $citation['content'];
            }
        }

        return collect($leads)
            ->reject(function (array $lead) use ($citationTextsByHost): bool {
                $hosts = array_values(array_filter([
                    $this->normalizeHost($lead['website'] ?? null),
                    $this->normalizeHost($lead['source_url'] ?? null),
                    $this->emailDomain(isset($lead['email']) ? (string) $lead['email'] : ''),
                ]));

                $hostEvidence = collect($hosts)
                    ->flatMap(fn (string $host) => $citationTextsByHost[$host] ?? [])
                    ->implode("\n");

                return $this->leadIsClearlyNonUk($lead, $hostEvidence);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function leadIsClearlyNonUk(array $lead, string $hostEvidence = ''): bool
    {
        if ($this->companyNameLooksNonUk(isset($lead['company']) ? (string) $lead['company'] : null)) {
            return true;
        }

        $hosts = [
            $this->normalizeHost($lead['website'] ?? null),
            $this->normalizeHost($lead['source_url'] ?? null),
            $this->emailDomain(isset($lead['email']) ? (string) $lead['email'] : ''),
        ];

        foreach ($hosts as $host) {
            if ($host !== null && $this->hostIsClearlyNonUk($host)) {
                return true;
            }
        }

        $ukHost = collect($hosts)->first(
            fn (?string $host): bool => $host !== null && $this->hostLooksUk($host),
        );

        if ($ukHost !== null) {
            return false;
        }

        return $hostEvidence !== '' && $this->evidenceLooksNonUk($hostEvidence);
    }

    protected function companyNameLooksNonUk(?string $company): bool
    {
        if (! filled($company)) {
            return false;
        }

        return preg_match('/\b(llc|inc\.?|pty|gmbh|s\.?a\.?s\.?)\b/i', $company) === 1;
    }

    protected function hostLooksUk(string $host): bool
    {
        return (bool) preg_match(
            '/(\.co\.uk|\.org\.uk|\.ltd\.uk|\.me\.uk|\.gov\.uk|\.ac\.uk|\.net\.uk|\.uk\.com|\.uk\.net|\.uk|\.scot|\.wales|\.cymru|\.london|\.gb)$/',
            $host,
        );
    }

    protected function hostIsClearlyNonUk(string $host): bool
    {
        if ($this->hostLooksUk($host)) {
            return false;
        }

        // US LLC-style hosts: omsgroupllc.com, acme-llc.com
        if (preg_match('/(^|\.)[a-z0-9-]*llc(\.|$)/i', $host) === 1) {
            return true;
        }

        foreach ($this->nonUkTldSuffixes as $suffix) {
            if ($host === ltrim($suffix, '.') || str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    protected function evidenceLooksNonUk(string $evidence): bool
    {
        $haystack = strtolower($evidence);

        return (bool) preg_match(
            '/\b(united states|u\.s\.a\.?|usa|canada|canadian|australia|llc|pty ltd|gmbh)\b/i',
            $haystack,
        );
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
