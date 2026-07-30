<x-layouts.app title="{{ $contact->name }} | Sales Tracker" heading="{{ $contact->name }}" eyebrow="Contact profile">
    <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
        <section class="panel">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-400">{{ $contact->company ?: 'No company set' }}</p>
                    <h3 class="mt-1 text-2xl font-semibold text-white">{{ $contact->name }}</h3>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-sky-500/15 px-3 py-1 font-semibold text-sky-200">{{ $contact->status->label() }}</span>
                        <span class="rounded-full bg-slate-800 px-3 py-1 font-semibold text-slate-300">{{ $contact->source->label() }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    @can(\App\Support\Permissions::EMAILS_SEND)
                        @if ($contact->email)
                            <a class="btn-primary" href="{{ route('contacts.email.create', $contact) }}">Send email</a>
                        @endif
                    @endcan
                    @can(\App\Support\Permissions::CONTACTS_UPDATE)
                        <a class="btn-secondary" href="{{ route('contacts.edit', $contact) }}">Edit</a>
                    @endcan
                    @can(\App\Support\Permissions::CONTACTS_DELETE)
                        <form method="post" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Delete this contact?')">
                            @csrf
                            @method('delete')
                            <button class="btn-secondary" type="submit">Delete</button>
                        </form>
                    @endcan
                </div>
            </div>

            <dl class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Email</dt>
                    <dd class="mt-1">
                        @if ($contact->email)
                            <a href="mailto:{{ $contact->email }}" class="text-sky-300 hover:text-sky-200">{{ $contact->email }}</a>
                        @else
                            <span class="text-slate-200">Not provided</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Phone</dt>
                    <dd class="mt-1 text-slate-200">{{ $contact->phone ?: 'Not provided' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm text-slate-500 mb-2">Quick contact links</dt>
                    <dd>
                        <x-outreach-links :links="$contact->outreachLinks()" />
                        @if (! count($contact->outreachLinks()))
                            <span class="text-slate-500">No website / LinkedIn / social links yet.</span>
                        @endif
                    </dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm text-slate-500">Tags</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @forelse ($contact->tags as $tag)
                            <span class="rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-200">{{ $tag->name }}</span>
                        @empty
                            <span class="text-slate-500">No tags yet.</span>
                        @endforelse
                    </dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm text-slate-500">Notes</dt>
                    <dd class="mt-2 whitespace-pre-wrap text-slate-300">{{ $contact->notes ?: 'No notes yet.' }}</dd>
                </div>
            </dl>
        </section>

        <section class="panel">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-white">Upcoming Follow-ups</h3>
                    <p class="text-sm text-slate-400">Tasks tied to this contact.</p>
                </div>
                <a href="{{ route('follow-ups.create', ['contact_id' => $contact->id]) }}" class="text-sm text-sky-300 hover:text-sky-200">Add task</a>
            </div>

            <x-data-table>
                <thead>
                    <tr>
                        <th>Due</th>
                        <th>Note</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($contact->followUps->sortBy('due_date') as $followUp)
                        <tr>
                            <td class="whitespace-nowrap font-medium text-white">{{ $followUp->due_date->format('M d, Y') }}</td>
                            <td class="max-w-xs truncate" title="{{ $followUp->note }}">{{ \Illuminate\Support\Str::limit($followUp->note, 50) }}</td>
                            <td>
                                <span class="text-xs {{ $followUp->completed ? 'text-emerald-300' : 'text-amber-300' }}">
                                    {{ $followUp->completed ? 'Done' : 'Open' }}
                                </span>
                            </td>
                            <x-row-actions>
                                @can(\App\Support\Permissions::FOLLOW_UPS_UPDATE)
                                    <a class="link-action" href="{{ route('follow-ups.edit', $followUp) }}">Edit</a>
                                    <form method="post" action="{{ route('follow-ups.toggle', $followUp) }}" class="inline">
                                        @csrf
                                        @method('patch')
                                        <button class="link-action" type="submit">{{ $followUp->completed ? 'Reopen' : 'Done' }}</button>
                                    </form>
                                @endcan
                                <x-delete-action
                                    :action="route('follow-ups.destroy', $followUp)"
                                    :permission="\App\Support\Permissions::FOLLOW_UPS_DELETE"
                                    confirm="Delete this follow-up?"
                                />
                            </x-row-actions>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-slate-500">No follow-ups scheduled yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </section>
    </div>

    @can(\App\Support\Permissions::EMAILS_INBOX)
        <section class="panel mt-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-white">Email Threads</h3>
                    <p class="text-sm text-slate-400">Sent / Opened / Responded tracking for this contact.</p>
                </div>
                @can(\App\Support\Permissions::EMAILS_SEND)
                    @if ($contact->email)
                        <a class="btn-secondary" href="{{ route('contacts.email.create', $contact) }}">Send email</a>
                    @endif
                @endcan
            </div>

            <x-data-table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Tracking</th>
                        <th>Status</th>
                        <th>Last message</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($contact->emailThreads as $thread)
                        <tr>
                            <td class="font-medium text-white max-w-sm truncate">{{ $thread->subject }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1 text-xs">
                                    <span @class(['rounded-full px-2 py-1 font-semibold', 'bg-emerald-500/15 text-emerald-200' => $thread->outbound_sent_count > 0, 'bg-slate-800 text-slate-500' => $thread->outbound_sent_count < 1])>Sent</span>
                                    <span @class(['rounded-full px-2 py-1 font-semibold', 'bg-sky-500/15 text-sky-200' => $thread->opened_count > 0, 'bg-slate-800 text-slate-500' => $thread->opened_count < 1])>Opened</span>
                                    <span @class(['rounded-full px-2 py-1 font-semibold', 'bg-amber-500/15 text-amber-200' => $thread->inbound_count > 0, 'bg-slate-800 text-slate-500' => $thread->inbound_count < 1])>Responded</span>
                                </div>
                            </td>
                            <td>{{ $thread->status->label() }}</td>
                            <td class="whitespace-nowrap">{{ optional($thread->last_message_at)->format('M d, H:i') ?: '—' }}</td>
                            <x-row-actions>
                                <a class="link-action" href="{{ route('email-threads.show', $thread) }}">View</a>
                            </x-row-actions>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500">No email threads yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </section>
    @endcan

    <section class="panel mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-white">Activity Timeline</h3>
                <p class="text-sm text-slate-400">Interactions and recorded responses in reverse chronological order.</p>
            </div>
            <a class="btn-secondary" href="{{ route('interactions.create', ['contact_id' => $contact->id]) }}">Add interaction</a>
        </div>

        <x-data-table>
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Details</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($contact->activity_timeline as $item)
                    <tr>
                        <td class="font-medium text-white whitespace-nowrap">{{ $item['title'] }}</td>
                        <td class="max-w-xl truncate" title="{{ $item['description'] }}">{{ $item['description'] ?: '—' }}</td>
                        <td class="whitespace-nowrap text-slate-400">{{ $item['timestamp'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-slate-500">No activity recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </section>
</x-layouts.app>
