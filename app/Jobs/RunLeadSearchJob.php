<?php

namespace App\Jobs;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\LeadSearchQuery;
use App\Services\LeadSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class RunLeadSearchJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $leadSearchQueryId,
    ) {
    }

    public function handle(LeadSearchService $leadSearchService): void
    {
        $leadSearchQuery = LeadSearchQuery::query()->findOrFail($this->leadSearchQueryId);

        try {
            $searchResult = $leadSearchService->search($leadSearchQuery->criteria);
        } catch (Throwable $exception) {
            $this->persistFailure($leadSearchQuery, $exception);

            // OpenRouter server-tool 400s will not heal by retrying three times.
            if ($leadSearchService->isNonRetryableFailure($exception)) {
                return;
            }

            throw $exception;
        }

        // Extra safety net — service already excludes, but never re-import known contacts.
        $searchResult['results'] = $leadSearchService->excludeExistingContacts($searchResult['results'] ?? []);

        $leadSearchQuery->update([
            'raw_results' => $searchResult,
        ]);

        foreach ($searchResult['results'] as $lead) {
            $name = trim((string) ($lead['name'] ?? ''));

            if ($name === '' || strtolower($name) === 'null') {
                continue;
            }

            if ($this->findExistingContact($lead, $name)) {
                continue;
            }

            $notes = collect([
                filled($lead['role'] ?? null) ? 'Role: '.$lead['role'] : null,
                'Imported from AI lead search #'.$leadSearchQuery->id,
            ])->filter()->implode("\n");

            Contact::query()->create([
                'name' => $name,
                'company' => $lead['company'] ?? null,
                'email' => $lead['email'] ?? null,
                'phone' => $lead['phone'] ?? null,
                'source' => ContactSource::AiSearch,
                'source_url' => $lead['source_url'] ?? null,
                'website' => $lead['website'] ?? null,
                'linkedin_url' => $lead['linkedin_url'] ?? null,
                'social_links' => ($lead['social_links'] ?? null) ?: null,
                'status' => ContactStatus::New,
                'notes' => $notes,
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $leadSearchQuery = LeadSearchQuery::query()->find($this->leadSearchQueryId);

        if ($leadSearchQuery === null) {
            return;
        }

        // Avoid overwriting a successful result if somehow failed() races.
        if (filled(data_get($leadSearchQuery->raw_results, 'results'))) {
            return;
        }

        $this->persistFailure($leadSearchQuery, $exception ?? new \RuntimeException('Lead search job failed.'));
    }

    protected function persistFailure(LeadSearchQuery $leadSearchQuery, Throwable $exception): void
    {
        $message = $exception->getMessage();

        if ($exception instanceof RequestException) {
            $message = (string) data_get(
                $exception->response?->json(),
                'error.message',
                $message,
            );
        }

        $leadSearchQuery->update([
            'raw_results' => [
                'results' => [],
                'failed' => true,
                'error' => $message,
                'diagnostics' => [
                    'summary' => 'AI lead search failed: '.$message,
                    'usable_leads' => 0,
                    'model_returned' => 0,
                ],
                'raw_response' => null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function findExistingContact(array $lead, string $name): ?Contact
    {
        if (filled($lead['email'] ?? null)) {
            $existing = Contact::query()
                ->whereRaw('LOWER(email) = ?', [strtolower((string) $lead['email'])])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if (filled($lead['linkedin_url'] ?? null)) {
            $existing = Contact::query()
                ->where('linkedin_url', $lead['linkedin_url'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if (filled($lead['company'] ?? null)) {
            $existing = Contact::query()
                ->whereRaw('LOWER(company) = ?', [strtolower((string) $lead['company'])])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return Contact::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->when(
                filled($lead['company'] ?? null),
                fn ($q) => $q->whereRaw('LOWER(company) = ?', [strtolower((string) $lead['company'])]),
            )
            ->first();
    }
}
