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

    public int $timeout = 120;

    public function __construct(
        public int $leadSearchQueryId,
    ) {
    }

    public function handle(LeadSearchService $leadSearchService): void
    {
        $leadSearchQuery = LeadSearchQuery::query()->findOrFail($this->leadSearchQueryId);
        $searchResult = $leadSearchService->search($leadSearchQuery->criteria);

        $leadSearchQuery->update([
            'raw_results' => $searchResult,
        ]);

        foreach ($searchResult['results'] as $lead) {
            $name = trim((string) ($lead['name'] ?? ''));

            if ($name === '' || strtolower($name) === 'null') {
                continue;
            }

            $attributes = filled($lead['email'] ?? null)
                ? ['email' => $lead['email']]
                : ['name' => $name, 'company' => $lead['company'] ?? null];

            $notes = collect([
                filled($lead['role'] ?? null) ? 'Role: '.$lead['role'] : null,
                'Imported from AI lead search #'.$leadSearchQuery->id,
            ])->filter()->implode("\n");

            Contact::query()->updateOrCreate($attributes, [
                'name' => $name,
                'company' => $lead['company'] ?? null,
                'email' => $lead['email'] ?? null,
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
}
