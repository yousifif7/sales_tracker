<?php

namespace App\Http\Controllers;

use App\Enums\ContactSource;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailThread;
use App\Models\Interaction;
use App\Support\Permissions;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::REPORTS_VIEW);

        $responseByCampaign = Campaign::query()
            ->with([
                'emailThreads' => fn ($query) => $query->withCount([
                    'messages as outbound_sent_count' => fn ($messageQuery) => $messageQuery
                        ->where('direction', 'outbound')
                        ->where('delivery_status', 'sent'),
                    'messages as inbound_count' => fn ($messageQuery) => $messageQuery
                        ->where('direction', 'inbound'),
                ]),
            ])
            ->get()
            ->map(function (Campaign $campaign): array {
                [$threadCount, $replyCount] = $this->threadReplyStats($campaign->emailThreads);

                $rate = $threadCount > 0
                    ? round(($replyCount / $threadCount) * 100, 1)
                    : 0;

                return [
                    'label' => $campaign->name,
                    'threads' => $threadCount,
                    'replies' => $replyCount,
                    'rate' => $rate,
                ];
            });

        $responseBySource = Contact::query()
            ->with([
                'emailThreads' => fn ($query) => $query->withCount([
                    'messages as outbound_sent_count' => fn ($messageQuery) => $messageQuery
                        ->where('direction', 'outbound')
                        ->where('delivery_status', 'sent'),
                    'messages as inbound_count' => fn ($messageQuery) => $messageQuery
                        ->where('direction', 'inbound'),
                ]),
            ])
            ->get()
            ->groupBy(fn (Contact $contact) => $contact->source?->value ?? 'unknown')
            ->map(function (Collection $contacts, string $sourceValue): array {
                $threadCount = 0;
                $replyCount = 0;

                foreach ($contacts as $contact) {
                    [$contactThreadCount, $contactReplyCount] = $this->threadReplyStats($contact->emailThreads);
                    $threadCount += $contactThreadCount;
                    $replyCount += $contactReplyCount;
                }

                $rate = $threadCount > 0
                    ? round(($replyCount / $threadCount) * 100, 1)
                    : 0;

                $source = ContactSource::tryFrom($sourceValue);

                return [
                    'label' => $source?->label() ?? 'Unknown',
                    'threads' => $threadCount,
                    'replies' => $replyCount,
                    'rate' => $rate,
                ];
            })
            ->values();

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

    /**
     * @param  iterable<int, EmailThread>  $threads
     * @return array{0: int, 1: int}
     */
    protected function threadReplyStats(iterable $threads): array
    {
        $threadCount = 0;
        $replyCount = 0;

        foreach ($threads as $thread) {
            $outboundSentCount = (int) ($thread->outbound_sent_count ?? 0);
            $inboundCount = (int) ($thread->inbound_count ?? 0);

            if ($outboundSentCount < 1) {
                continue;
            }

            $threadCount++;

            if ($inboundCount > 0) {
                $replyCount++;
            }
        }

        return [$threadCount, $replyCount];
    }

    protected function escapeCsv(mixed $value): string
    {
        $string = str_replace('"', '""', (string) $value);

        return '"'.$string.'"';
    }
}
