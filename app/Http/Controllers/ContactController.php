<?php

namespace App\Http\Controllers;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailMessageDirection;
use App\Enums\EmailSequenceExitReason;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\Tag;
use App\Services\OutreachSequenceService;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission(Permissions::CONTACTS_VIEW);

        $firstEmailFrom = $this->resolveDateFilter($request->input('first_email_from'));
        $firstEmailTo = $this->resolveDateFilter($request->input('first_email_to'));
        $lastEmailFrom = $this->resolveDateFilter($request->input('last_email_from'));
        $lastEmailTo = $this->resolveDateFilter($request->input('last_email_to'));
        $emailed = $request->string('emailed')->toString();

        $contacts = Contact::query()
            ->with('tags')
            ->select('contacts.*')
            ->selectSub($this->outboundEmailDateSubquery('min'), 'first_emailed_at')
            ->selectSub($this->outboundEmailDateSubquery('max'), 'last_emailed_at')
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('source'), fn (Builder $query) => $query->where('source', $request->string('source')))
            ->when($request->filled('tag'), fn (Builder $query) => $query->whereHas('tags', fn (Builder $tagQuery) => $tagQuery->where('name', $request->string('tag'))))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search').'%';

                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', $search)
                        ->orWhere('company', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($emailed === 'never', fn (Builder $query) => $query->whereDoesntHave(
                'emailMessages',
                fn (Builder $messageQuery) => $messageQuery
                    ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
                    ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value),
            ))
            ->when($emailed === 'yes', fn (Builder $query) => $query->whereHas(
                'emailMessages',
                fn (Builder $messageQuery) => $messageQuery
                    ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
                    ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value),
            ))
            ->when($firstEmailFrom, function (Builder $query) use ($firstEmailFrom): void {
                $sub = $this->outboundEmailDateSubquery('min');
                $query->whereRaw('DATE(('.$sub->toSql().')) >= ?', [...$sub->getBindings(), $firstEmailFrom]);
            })
            ->when($firstEmailTo, function (Builder $query) use ($firstEmailTo): void {
                $sub = $this->outboundEmailDateSubquery('min');
                $query->whereRaw('DATE(('.$sub->toSql().')) <= ?', [...$sub->getBindings(), $firstEmailTo]);
            })
            ->when($lastEmailFrom, function (Builder $query) use ($lastEmailFrom): void {
                $sub = $this->outboundEmailDateSubquery('max');
                $query->whereRaw('DATE(('.$sub->toSql().')) >= ?', [...$sub->getBindings(), $lastEmailFrom]);
            })
            ->when($lastEmailTo, function (Builder $query) use ($lastEmailTo): void {
                $sub = $this->outboundEmailDateSubquery('max');
                $query->whereRaw('DATE(('.$sub->toSql().')) <= ?', [...$sub->getBindings(), $lastEmailTo]);
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('contacts.index', [
            'contacts' => $contacts,
            'statusOptions' => ContactStatus::options(),
            'sourceOptions' => ContactSource::options(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'firstEmailFrom' => $firstEmailFrom,
            'firstEmailTo' => $firstEmailTo,
            'lastEmailFrom' => $lastEmailFrom,
            'lastEmailTo' => $lastEmailTo,
            'emailed' => $emailed,
        ]);
    }

    /**
     * Earliest/latest successful outbound email date for a contact.
     */
    protected function outboundEmailDateSubquery(string $aggregate): \Illuminate\Database\Query\Builder
    {
        $aggregate = strtolower($aggregate) === 'max' ? 'max' : 'min';

        return DB::table('email_messages')
            ->join('email_threads', 'email_threads.id', '=', 'email_messages.email_thread_id')
            ->whereColumn('email_threads.contact_id', 'contacts.id')
            ->whereNull('email_threads.deleted_at')
            ->where('email_messages.direction', EmailMessageDirection::Outbound->value)
            ->where('email_messages.delivery_status', EmailDeliveryStatus::Sent->value)
            ->selectRaw($aggregate.'(email_messages.sent_at)');
    }

    protected function resolveDateFilter(mixed $value): ?string
    {
        $date = is_string($value) ? trim($value) : '';

        if ($date === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::CONTACTS_CREATE);

        return view('contacts.create', [
            'contact' => new Contact([
                'source' => ContactSource::Manual,
                'status' => ContactStatus::New,
            ]),
            'statusOptions' => ContactStatus::options(),
            'sourceOptions' => ContactSource::options(),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::CONTACTS_CREATE);

        $contact = Contact::query()->create($request->safe()->except('tags'));
        $this->syncTags($contact, $request->input('tags'));

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Contact created successfully.');
    }

    public function show(Contact $contact): View
    {
        $this->authorizePermission(Permissions::CONTACTS_VIEW);

        $contact->load([
            'tags',
            'interactions.campaign',
            'interactions.response',
            'followUps',
            'emailThreads' => fn ($q) => $q->withCount([
                'messages as outbound_sent_count' => fn ($m) => $m->where('direction', 'outbound')->where('delivery_status', 'sent'),
                'messages as opened_count' => fn ($m) => $m->where('direction', 'outbound')->where(fn ($inner) => $inner->whereNotNull('opened_at')->orWhere('open_count', '>', 0)),
                'messages as inbound_count' => fn ($m) => $m->where('direction', 'inbound'),
            ])->latest('last_message_at')->limit(10),
            'sequenceEnrollments' => fn ($query) => $query->latest('id')->limit(5),
        ]);

        return view('contacts.show', [
            'contact' => $contact,
            'activeSequence' => $contact->sequenceEnrollments
                ->first(fn ($enrollment) => $enrollment->status === \App\Enums\EmailSequenceStatus::Active),
        ]);
    }

    public function edit(Contact $contact): View
    {
        $this->authorizePermission(Permissions::CONTACTS_UPDATE);

        $contact->load('tags');

        return view('contacts.edit', [
            'contact' => $contact,
            'statusOptions' => ContactStatus::options(),
            'sourceOptions' => ContactSource::options(),
            'tagString' => $contact->tags->pluck('name')->implode(', '),
        ]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->authorizePermission(Permissions::CONTACTS_UPDATE);

        $previousStatus = $contact->status;

        $contact->update($request->safe()->except('tags'));
        $this->syncTags($contact, $request->input('tags'));

        if (
            $previousStatus !== $contact->status
            && in_array($contact->status, [
                ContactStatus::Responded,
                ContactStatus::Qualified,
                ContactStatus::Won,
                ContactStatus::Lost,
            ], true)
        ) {
            app(OutreachSequenceService::class)->completeForContact(
                $contact,
                $contact->status === ContactStatus::Responded
                    ? EmailSequenceExitReason::Replied
                    : EmailSequenceExitReason::StatusChanged,
            );
        }

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorizePermission(Permissions::CONTACTS_DELETE);

        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('status', 'Contact deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::CONTACTS_DELETE);

        $validated = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1', 'max:100'],
            'contact_ids.*' => ['integer', 'distinct', 'exists:contacts,id'],
        ]);

        $ids = $validated['contact_ids'];
        $count = Contact::query()->whereIn('id', $ids)->count();

        Contact::query()->whereIn('id', $ids)->delete();

        return redirect()
            ->route('contacts.index')
            ->with('status', $count === 1
                ? '1 contact deleted.'
                : "{$count} contacts deleted.");
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::CONTACTS_UPDATE);

        $validated = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1', 'max:100'],
            'contact_ids.*' => ['integer', 'distinct', 'exists:contacts,id'],
            'status' => ['required', Rule::enum(ContactStatus::class)],
        ]);

        $status = ContactStatus::from($validated['status']);
        $contacts = Contact::query()->whereIn('id', $validated['contact_ids'])->get();
        $updated = 0;

        $sequences = app(OutreachSequenceService::class);
        $stopStatuses = [
            ContactStatus::Responded,
            ContactStatus::Qualified,
            ContactStatus::Won,
            ContactStatus::Lost,
        ];

        foreach ($contacts as $contact) {
            if ($contact->status === $status) {
                continue;
            }

            $previousStatus = $contact->status;
            $contact->update(['status' => $status]);
            $updated++;

            if (
                $previousStatus !== $status
                && in_array($status, $stopStatuses, true)
            ) {
                $sequences->completeForContact(
                    $contact,
                    $status === ContactStatus::Responded
                        ? EmailSequenceExitReason::Replied
                        : EmailSequenceExitReason::StatusChanged,
                );
            }
        }

        $label = $status->label();

        return redirect()
            ->route('contacts.index')
            ->with('status', $updated === 0
                ? "No contacts needed updating — already {$label}."
                : ($updated === 1
                    ? "1 contact marked {$label}."
                    : "{$updated} contacts marked {$label}."));
    }

    protected function syncTags(Contact $contact, ?string $tags): void
    {
        $tagIds = collect(explode(',', (string) $tags))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->map(fn (string $tag) => Tag::query()->firstOrCreate(['name' => $tag])->id)
            ->values()
            ->all();

        $contact->tags()->sync($tagIds);
    }
}
