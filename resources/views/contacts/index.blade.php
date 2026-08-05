<x-layouts.app title="Contacts | Sales Tracker" heading="Contacts" eyebrow="Lead and prospect management">
    @php
        $canBulkEmail = auth()->user()?->can(\App\Support\Permissions::EMAILS_SEND);
        $canBulkDelete = auth()->user()?->can(\App\Support\Permissions::CONTACTS_DELETE);
        $canBulkSelect = $canBulkEmail || $canBulkDelete;
        $colspan = ($canBulkSelect ? 11 : 10);
    @endphp

    <section class="panel mb-6">
        <form method="get" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
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
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="label" for="emailed">Outreach</label>
                    <select class="input mt-0" id="emailed" name="emailed">
                        <option value="">All contacts</option>
                        <option value="never" @selected(($emailed ?? '') === 'never')>Never emailed</option>
                        <option value="yes" @selected(($emailed ?? '') === 'yes')>Has been emailed</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="first_email_from">First email from</label>
                    <input class="input mt-0" id="first_email_from" type="date" name="first_email_from" value="{{ $firstEmailFrom ?? '' }}">
                </div>
                <div>
                    <label class="label" for="first_email_to">First email to</label>
                    <input class="input mt-0" id="first_email_to" type="date" name="first_email_to" value="{{ $firstEmailTo ?? '' }}">
                </div>
                <div>
                    <label class="label" for="last_email_from">Last email from</label>
                    <input class="input mt-0" id="last_email_from" type="date" name="last_email_from" value="{{ $lastEmailFrom ?? '' }}">
                </div>
                <div>
                    <label class="label" for="last_email_to">Last email to</label>
                    <input class="input mt-0" id="last_email_to" type="date" name="last_email_to" value="{{ $lastEmailTo ?? '' }}">
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn-primary" type="submit">Filter</button>
                <a class="btn-secondary" href="{{ route('contacts.index') }}">Reset</a>
                @can(\App\Support\Permissions::CONTACTS_CREATE)
                    <a class="btn-secondary sm:ml-auto" href="{{ route('contacts.create') }}">New contact</a>
                @endcan
            </div>
        </form>
    </section>

    @if ($canBulkSelect)
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">
                @if ($contacts->total() > 0)
                    Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} of {{ $contacts->total() }}
                @else
                    No contacts
                @endif
            </p>
            <form
                method="post"
                action="{{ $canBulkEmail ? route('contacts.email.bulk.create') : route('contacts.bulk-destroy') }}"
                id="contacts-bulk-form"
                class="flex flex-wrap gap-2"
            >
                @csrf
                @if ($canBulkEmail)
                    <button class="btn-secondary" type="submit" id="contacts-bulk-email" disabled>
                        Email selected
                    </button>
                @endif
                @if ($canBulkDelete)
                    <button
                        class="btn-secondary"
                        type="submit"
                        id="contacts-bulk-delete"
                        formaction="{{ route('contacts.bulk-destroy') }}"
                        disabled
                        onclick="return confirm('Delete selected contacts? This cannot be undone.');"
                    >
                        Delete selected
                    </button>
                @endif
            </form>
        </div>
    @endif

    <x-data-table wide>
        <thead>
            <tr>
                @if ($canBulkSelect)
                    <th class="w-10">
                        <input type="checkbox" id="contacts-select-all" class="rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40" title="Select all on this page">
                    </th>
                @endif
                <th>Name</th>
                <th>Company</th>
                <th>Email</th>
                <th>Status</th>
                <th>Source</th>
                <th>Created</th>
                <th>First email</th>
                <th>Last email</th>
                <th>Links</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800" id="contacts-rows">
            @forelse ($contacts as $contact)
                <tr>
                    @if ($canBulkSelect)
                        <td>
                            <input
                                type="checkbox"
                                form="contacts-bulk-form"
                                name="contact_ids[]"
                                value="{{ $contact->id }}"
                                data-has-email="{{ $contact->email ? '1' : '0' }}"
                                class="contacts-row-check rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40"
                            >
                        </td>
                    @endif
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
                    <td class="whitespace-nowrap">
                        <p class="text-white">{{ optional($contact->created_at)->format('M d, Y') ?: '—' }}</p>
                        <p class="text-xs text-slate-500">{{ optional($contact->created_at)->format('H:i') ?: '' }}</p>
                    </td>
                    <td class="whitespace-nowrap">
                        @if ($contact->first_emailed_at)
                            <p class="text-white">{{ \Illuminate\Support\Carbon::parse($contact->first_emailed_at)->format('M d, Y') }}</p>
                            <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($contact->first_emailed_at)->format('H:i') }}</p>
                        @else
                            <span class="text-slate-500">Never</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap">
                        @if ($contact->last_emailed_at)
                            <p class="text-white">{{ \Illuminate\Support\Carbon::parse($contact->last_emailed_at)->format('M d, Y') }}</p>
                            <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($contact->last_emailed_at)->format('H:i') }}</p>
                        @else
                            <span class="text-slate-500">Never</span>
                        @endif
                    </td>
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
                    <td colspan="{{ $colspan }}" class="text-center text-slate-500">No contacts found yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $contacts->links() }}
    </div>

    @if ($canBulkSelect)
        <script>
            (function () {
                var form = document.getElementById('contacts-bulk-form');
                var rowsRoot = document.getElementById('contacts-rows');
                if (!form || !rowsRoot) return;
                var selectAll = document.getElementById('contacts-select-all');
                var emailButton = document.getElementById('contacts-bulk-email');
                var deleteButton = document.getElementById('contacts-bulk-delete');
                var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.contacts-row-check')); };
                var sync = function () {
                    var rows = checks();
                    var selected = rows.filter(function (el) { return el.checked; });
                    var selectedCount = selected.length;
                    var emailableCount = selected.filter(function (el) { return el.getAttribute('data-has-email') === '1'; }).length;

                    if (emailButton) {
                        emailButton.disabled = emailableCount === 0;
                        emailButton.textContent = emailableCount > 0
                            ? ('Email selected (' + emailableCount + ')')
                            : 'Email selected';
                    }
                    if (deleteButton) {
                        deleteButton.disabled = selectedCount === 0;
                        deleteButton.textContent = selectedCount > 0
                            ? ('Delete selected (' + selectedCount + ')')
                            : 'Delete selected';
                    }
                    if (selectAll) {
                        selectAll.checked = rows.length > 0 && selectedCount === rows.length;
                        selectAll.indeterminate = selectedCount > 0 && selectedCount < rows.length;
                    }
                };
                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        checks().forEach(function (el) { el.checked = selectAll.checked; });
                        sync();
                    });
                }
                if (emailButton) {
                    form.addEventListener('submit', function (event) {
                        if (event.submitter !== emailButton) return;
                        checks().forEach(function (el) {
                            if (el.checked && el.getAttribute('data-has-email') !== '1') {
                                el.checked = false;
                            }
                        });
                    });
                }
                rowsRoot.addEventListener('change', function (event) {
                    if (event.target && event.target.classList.contains('contacts-row-check')) sync();
                });
                sync();
            })();
        </script>
    @endif
</x-layouts.app>
