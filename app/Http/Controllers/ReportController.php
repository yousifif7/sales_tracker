<?php

namespace App\Http\Controllers;

use App\Enums\ContactSource;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Interaction;
use App\Support\Permissions;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::REPORTS_VIEW);

        $responseByCampaign = Campaign::query()
            ->leftJoin('interactions', 'campaigns.id', '=', 'interactions.campaign_id')
            ->leftJoin('responses', 'interactions.id', '=', 'responses.interaction_id')
            ->groupBy('campaigns.id', 'campaigns.name')
            ->selectRaw('campaigns.name, COUNT(DISTINCT interactions.id) as interaction_count, COUNT(responses.id) as response_count')
            ->get()
            ->map(function ($row) {
                $rate = $row->interaction_count > 0
                    ? round(($row->response_count / $row->interaction_count) * 100, 1)
                    : 0;

                return [
                    'label' => $row->name,
                    'interactions' => $row->interaction_count,
                    'responses' => $row->response_count,
                    'rate' => $rate,
                ];
            });

        $responseBySource = Contact::query()
            ->leftJoin('interactions', 'contacts.id', '=', 'interactions.contact_id')
            ->leftJoin('responses', 'interactions.id', '=', 'responses.interaction_id')
            ->groupBy('contacts.source')
            ->selectRaw('contacts.source, COUNT(DISTINCT interactions.id) as interaction_count, COUNT(responses.id) as response_count')
            ->get()
            ->map(function ($row) {
                $rate = $row->interaction_count > 0
                    ? round(($row->response_count / $row->interaction_count) * 100, 1)
                    : 0;

                $source = $row->source instanceof ContactSource
                    ? $row->source
                    : ContactSource::tryFrom((string) $row->source);

                return [
                    'label' => $source?->label() ?? 'Unknown',
                    'interactions' => $row->interaction_count,
                    'responses' => $row->response_count,
                    'rate' => $rate,
                ];
            });

        return view('reports.index', [
            'responseByCampaign' => $responseByCampaign,
            'responseBySource' => $responseBySource,
        ]);
    }

    public function exportContacts(): Response
    {
        $this->authorizePermission(Permissions::REPORTS_EXPORT);

        $csv = Contact::query()
            ->with('tags')
            ->orderBy('name')
            ->get()
            ->reduce(
                fn (string $carry, Contact $contact) => $carry.implode(',', [
                    $this->escapeCsv($contact->name),
                    $this->escapeCsv($contact->company),
                    $this->escapeCsv($contact->email),
                    $this->escapeCsv($contact->phone),
                    $this->escapeCsv($contact->source?->value),
                    $this->escapeCsv($contact->status?->value),
                    $this->escapeCsv($contact->tags->pluck('name')->implode('|')),
                ]).PHP_EOL,
                "name,company,email,phone,source,status,tags".PHP_EOL,
            );

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts.csv"',
        ]);
    }

    public function exportInteractions(): Response
    {
        $this->authorizePermission(Permissions::REPORTS_EXPORT);

        $csv = Interaction::query()
            ->with(['contact', 'campaign', 'response', 'creator'])
            ->latest('sent_at')
            ->get()
            ->reduce(
                fn (string $carry, Interaction $interaction) => $carry.implode(',', [
                    $this->escapeCsv($interaction->contact?->name),
                    $this->escapeCsv($interaction->campaign?->name),
                    $this->escapeCsv($interaction->channel?->value),
                    $this->escapeCsv($interaction->direction?->value),
                    $this->escapeCsv($interaction->sent_at?->toDateTimeString()),
                    $this->escapeCsv($interaction->response?->outcome?->value),
                    $this->escapeCsv($interaction->creator?->name),
                ]).PHP_EOL,
                "contact,campaign,channel,direction,sent_at,response_outcome,created_by".PHP_EOL,
            );

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="interactions.csv"',
        ]);
    }

    protected function escapeCsv(mixed $value): string
    {
        $string = str_replace('"', '""', (string) $value);

        return '"'.$string.'"';
    }
}
