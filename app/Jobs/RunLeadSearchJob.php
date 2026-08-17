<?php

namespace App\Jobs;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\LeadSearchQuery;
use App\Services\LeadSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunLeadSearchJob implements ShouldQueue
{
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
        $searchResult = $leadSearchService->search($leadSearchQuery->criteria);

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
