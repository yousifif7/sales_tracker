<?php

namespace App\Http\Controllers;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\Tag;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission(Permissions::CONTACTS_VIEW);

        $contacts = Contact::query()
            ->with('tags')
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
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('contacts.index', [
            'contacts' => $contacts,
            'statusOptions' => ContactStatus::options(),
            'sourceOptions' => ContactSource::options(),
            'tags' => Tag::query()->orderBy('name')->get(),
        ]);
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
        ]);

        return view('contacts.show', [
            'contact' => $contact,
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

        $contact->update($request->safe()->except('tags'));
        $this->syncTags($contact, $request->input('tags'));

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
