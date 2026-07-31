<x-layouts.app title="Contacts | Sales Tracker" heading="Contacts" eyebrow="Lead and prospect management">
    <section class="panel mb-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <input class="input mt-0 sm:col-span-2" name="search" value="{{ request('search') }}" placeholder="Search name, company, email">
            <select class="input mt-0" name="status">
                <option value="">All statuses</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="input mt-0" name="source">
                <option value="">All sources</option>
                @foreach ($sourceOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="input mt-0" name="tag">
                <option value="">All tags</option>
                @foreach ($tags as $tag)
                    <option value="{{ $tag->name }}" @selected(request('tag') === $tag->name)>{{ $tag->name }}</option>
                @endforeach
            </select>
            <div class="filter-actions sm:col-span-2 lg:col-span-5">
                <button class="btn-primary" type="submit">Filter</button>
                <a class="btn-secondary" href="{{ route('contacts.index') }}">Reset</a>
                @can(\App\Support\Permissions::CONTACTS_CREATE)
                    <a class="btn-secondary sm:ml-auto" href="{{ route('contacts.create') }}">New contact</a>
                @endcan
            </div>
        </form>
    </section>

    <x-data-table wide>
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Email</th>
                <th>Status</th>
                <th>Source</th>
                <th>Links</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($contacts as $contact)
                <tr>
                    <td class="font-medium text-white">{{ $contact->name ?: '—' }}</td>
                    <td>{{ $contact->company ?: '—' }}</td>
                    <td>
                        @if ($contact->email)
                            <a class="link-action" href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $contact->status->label() }}</td>
                    <td>{{ $contact->source->label() }}</td>
                    <td>
                        <div class="flex flex-wrap gap-2 text-xs">
                            @if ($contact->linkedin_url)
                                <a class="link-action" href="{{ $contact->linkedin_url }}" target="_blank" rel="noopener">LinkedIn</a>
                            @endif
                            @if ($contact->website)
                                <a class="link-action" href="{{ $contact->website }}" target="_blank" rel="noopener">Website</a>
                            @endif
                            @if (! $contact->linkedin_url && ! $contact->website)
                                —
                            @endif
                        </div>
                    </td>
                    <x-row-actions>
                        <a class="link-action" href="{{ route('contacts.show', $contact) }}">View</a>
                        @can(\App\Support\Permissions::CONTACTS_UPDATE)
                            <a class="link-action" href="{{ route('contacts.edit', $contact) }}">Edit</a>
                        @endcan
                        @can(\App\Support\Permissions::EMAILS_SEND)
                            @if ($contact->email)
                                <a class="link-action" href="{{ route('contacts.email.create', $contact) }}">Email</a>
                            @endif
                        @endcan
                        <x-delete-action
                            :action="route('contacts.destroy', $contact)"
                            :permission="\App\Support\Permissions::CONTACTS_DELETE"
                            confirm="Delete this contact?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-slate-500">No contacts found yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $contacts->links() }}
    </div>
</x-layouts.app>
