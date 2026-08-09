<?php

namespace App\Http\Controllers;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailMessageDirection;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Interaction;
use App\Support\Permissions;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::REPORTS_VIEW);

        $outreach = $this->outreachOverview();

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
            'outreach' => $outreach,
            'funnel' => $this->outreachFunnel($outreach),
            'touches' => $this->touchBuckets(),
            'statusMix' => $this->statusMix(),
            'dataQuality' => $this->contactDataQuality($outreach['contacts_emailed']),
            'sendsByDay' => $this->sendsByDay(14),
            'hotOpens' => $this->hotOpenContacts(),
            'multiTouch' => $this->multiTouchContacts(),
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
     * @return array{
     *     outbound_sent: int,
     *     opened_messages: int,
     *     open_rate: float,
     *     contacts_emailed: int,
     *     contacts_replied: int,
     *     reply_rate: float,
     *     sends_this_week: int,
     *     inbound_messages: int
     * }
     */
    protected function outreachOverview(): array
    {
        $outboundSent = EmailMessage::query()
            ->where('direction', EmailMessageDirection::Outbound)
            ->where('delivery_status', EmailDeliveryStatus::Sent)
            ->count();

        $openedMessages = EmailMessage::query()
            ->where('direction', EmailMessageDirection::Outbound)
            ->where('delivery_status', EmailDeliveryStatus::Sent)
            ->where(fn ($query) => $query
                ->whereNotNull('opened_at')
                ->orWhere('open_count', '>', 0))
            ->count();

        $sendsThisWeek = EmailMessage::query()
            ->where('direction', EmailMessageDirection::Outbound)
            ->where('delivery_status', EmailDeliveryStatus::Sent)
            ->where('sent_at', '>=', now()->startOfWeek())
            ->count();

        $inboundMessages = EmailMessage::query()
            ->where('direction', EmailMessageDirection::Inbound)
            ->count();

        $contactsEmailed = EmailThread::query()
            ->whereHas('messages', fn ($query) => $query
                ->where('direction', EmailMessageDirection::Outbound)
                ->where('delivery_status', EmailDeliveryStatus::Sent))
            ->distinct()
            ->count('contact_id');

        $contactsReplied = EmailThread::query()
            ->whereHas('messages', fn ($query) => $query
                ->where('direction', EmailMessageDirection::Outbound)
                ->where('delivery_status', EmailDeliveryStatus::Sent))
            ->whereHas('messages', fn ($query) => $query
                ->where('direction', EmailMessageDirection::Inbound))
            ->distinct()
            ->count('contact_id');

        $contactsOpened = (int) DB::table('email_messages')
            ->join('email_threads', 'email_threads.id', '=', 'email_messages.email_thread_id')
            ->whereNull('email_threads.deleted_at')
            ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
            ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value)
            ->where(fn ($query) => $query
                ->whereNotNull('email_messages.opened_at')
                ->orWhere('email_messages.open_count', '>', 0))
            ->selectRaw('count(distinct email_threads.contact_id) as aggregate')
            ->value('aggregate');

        return [
            'outbound_sent' => $outboundSent,
            'opened_messages' => $openedMessages,
            'open_rate' => $outboundSent > 0
                ? round(($openedMessages / $outboundSent) * 100, 1)
                : 0.0,
            'contacts_emailed' => $contactsEmailed,
            'contacts_replied' => $contactsReplied,
            'reply_rate' => $contactsEmailed > 0
                ? round(($contactsReplied / $contactsEmailed) * 100, 1)
                : 0.0,
            'sends_this_week' => $sendsThisWeek,
            'inbound_messages' => $inboundMessages,
            'contacts_opened' => $contactsOpened,
            'contacts_with_email' => Contact::query()->whereNotNull('email')->where('email', '!=', '')->count(),
            'contacts_total' => Contact::query()->count(),
        ];
    }

    /**
     * @param  array{
     *     contacts_total: int,
     *     contacts_with_email: int,
     *     contacts_emailed: int,
     *     contacts_opened: int,
     *     contacts_replied: int
     * }  $outreach
     * @return list<array{label: string, value: int}>
     */
    protected function outreachFunnel(array $outreach): array
    {
        return [
            ['label' => 'Contacts', 'value' => $outreach['contacts_total']],
            ['label' => 'With email', 'value' => $outreach['contacts_with_email']],
            ['label' => 'Emailed', 'value' => $outreach['contacts_emailed']],
            ['label' => 'Opened', 'value' => $outreach['contacts_opened']],
            ['label' => 'Replied', 'value' => $outreach['contacts_replied']],
        ];
    }

    /**
     * @return array{one: int, two: int, three_plus: int}
     */
    protected function touchBuckets(): array
    {
        $counts = $this->outboundTouchCounts()->values();

        return [
            'one' => $counts->filter(fn (int $sends) => $sends === 1)->count(),
            'two' => $counts->filter(fn (int $sends) => $sends === 2)->count(),
            'three_plus' => $counts->filter(fn (int $sends) => $sends >= 3)->count(),
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    protected function statusMix(): array
    {
        $rows = Contact::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(ContactStatus::cases())
            ->map(fn (ContactStatus $status) => [
                'label' => $status->label(),
                'count' => (int) ($rows[$status->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     with_phone: int,
     *     with_linkedin: int,
     *     never_emailed: int
     * }
     */
    protected function contactDataQuality(int $contactsEmailed): array
    {
        $withEmail = Contact::query()->whereNotNull('email')->where('email', '!=', '')->count();

        return [
            'with_phone' => Contact::query()->whereNotNull('phone')->where('phone', '!=', '')->count(),
            'with_linkedin' => Contact::query()->whereNotNull('linkedin_url')->where('linkedin_url', '!=', '')->count(),
            'never_emailed' => max(0, $withEmail - $contactsEmailed),
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    protected function sendsByDay(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = EmailMessage::query()
            ->where('direction', EmailMessageDirection::Outbound)
            ->where('delivery_status', EmailDeliveryStatus::Sent)
            ->where('sent_at', '>=', $start)
            ->selectRaw('DATE(sent_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $series[] = [
                'label' => $day->format('M j'),
                'value' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return Collection<int, object{
     *     contact_id: int,
     *     name: string,
     *     company: ?string,
     *     status: ?string,
     *     emails_sent: int|string,
     *     replied: int|string
     * }>
     */
    protected function multiTouchContacts(): Collection
    {
        $repliedIds = array_flip(
            EmailThread::query()
                ->whereHas('messages', fn ($query) => $query
                    ->where('direction', EmailMessageDirection::Outbound)
                    ->where('delivery_status', EmailDeliveryStatus::Sent))
                ->whereHas('messages', fn ($query) => $query
                    ->where('direction', EmailMessageDirection::Inbound))
                ->distinct()
                ->pluck('contact_id')
                ->all()
        );

        return DB::table('email_messages')
            ->join('email_threads', 'email_threads.id', '=', 'email_messages.email_thread_id')
            ->join('contacts', 'contacts.id', '=', 'email_threads.contact_id')
            ->whereNull('email_threads.deleted_at')
            ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
            ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value)
            ->groupBy('contacts.id', 'contacts.name', 'contacts.company', 'contacts.status')
            ->havingRaw('count(email_messages.id) >= 2')
            ->orderByDesc(DB::raw('count(email_messages.id)'))
            ->orderBy('contacts.name')
            ->limit(20)
            ->get([
                'contacts.id as contact_id',
                'contacts.name',
                'contacts.company',
                'contacts.status',
                DB::raw('count(email_messages.id) as emails_sent'),
            ])
            ->map(function (object $row) use ($repliedIds): object {
                $row->replied = isset($repliedIds[(int) $row->contact_id]);

                return $row;
            });
    }

    /**
     * @return Collection<int, int>
     */
    protected function outboundTouchCounts(): Collection
    {
        return DB::table('email_messages')
            ->join('email_threads', 'email_threads.id', '=', 'email_messages.email_thread_id')
            ->whereNull('email_threads.deleted_at')
            ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
            ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value)
            ->groupBy('email_threads.contact_id')
            ->select('email_threads.contact_id', DB::raw('count(email_messages.id) as sends'))
            ->pluck('sends', 'contact_id')
            ->map(fn ($sends) => (int) $sends);
    }

    /**
     * @return Collection<int, object{
     *     contact_id: int,
     *     name: string,
     *     company: ?string,
     *     status: ?string,
     *     emails_opened: int|string,
     *     total_opens: int|string
     * }>
     */
    protected function hotOpenContacts(): Collection
    {
        return DB::table('email_messages')
            ->join('email_threads', 'email_threads.id', '=', 'email_messages.email_thread_id')
            ->join('contacts', 'contacts.id', '=', 'email_threads.contact_id')
            ->whereNull('email_threads.deleted_at')
            ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
            ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value)
            ->where(fn ($query) => $query
                ->whereNotNull('email_messages.opened_at')
                ->orWhere('email_messages.open_count', '>', 0))
            ->groupBy('contacts.id', 'contacts.name', 'contacts.company', 'contacts.status')
            ->orderByDesc(DB::raw('sum(email_messages.open_count)'))
            ->orderByDesc(DB::raw('count(email_messages.id)'))
            ->limit(10)
            ->get([
                'contacts.id as contact_id',
                'contacts.name',
                'contacts.company',
                'contacts.status',
                DB::raw('count(email_messages.id) as emails_opened'),
                DB::raw('sum(email_messages.open_count) as total_opens'),
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
