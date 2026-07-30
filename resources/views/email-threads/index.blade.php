<x-layouts.app title="Inbox | Sales Tracker" heading="Inbox" eyebrow="Email threads">
    <section class="panel mb-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-400">Active conversations. Deleted threads go to trash first.</p>
            <a class="btn-secondary" href="{{ route('email-threads.trash') }}">
                Trash{{ ($trashedCount ?? 0) > 0 ? ' ('.$trashedCount.')' : '' }}
            </a>
        </div>
        <form method="get" class="grid gap-3 md:grid-cols-4">
            <input class="input mt-0 md:col-span-2" name="search" value="{{ request('search') }}" placeholder="Search contact, email, subject">
            <select class="input mt-0" name="status">
                <option value="">All statuses</option>
                @foreach (\App\Enums\EmailThreadStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="btn-primary" type="submit">Filter</button>
        </form>
    </section>

    <x-data-table>
        <thead>
            <tr>
                <th>Contact</th>
                <th>Subject</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Last message</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($threads as $thread)
                <tr>
                    <td>
                        <p class="font-medium text-white">{{ $thread->contact?->name ?: '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $thread->contact?->email }}</p>
                    </td>
                    <td class="max-w-sm truncate" title="{{ $thread->subject }}">{{ $thread->subject }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1 text-xs">
                            <span @class([
                                'rounded-full px-2 py-1 font-semibold',
                                'bg-emerald-500/15 text-emerald-200' => $thread->outbound_sent_count > 0,
                                'bg-slate-800 text-slate-500' => $thread->outbound_sent_count < 1,
                            ])>Sent</span>
                            <span @class([
                                'rounded-full px-2 py-1 font-semibold',
                                'bg-sky-500/15 text-sky-200' => $thread->opened_count > 0,
                                'bg-slate-800 text-slate-500' => $thread->opened_count < 1,
                            ])>Opened</span>
                            <span @class([
                                'rounded-full px-2 py-1 font-semibold',
                                'bg-amber-500/15 text-amber-200' => $thread->inbound_count > 0,
                                'bg-slate-800 text-slate-500' => $thread->inbound_count < 1,
                            ])>Responded</span>
                        </div>
                    </td>
                    <td>{{ $thread->status->label() }}</td>
                    <td class="whitespace-nowrap">{{ optional($thread->last_message_at)->format('M d, H:i') ?: '—' }}</td>
                    <x-row-actions>
                        <a class="link-action" href="{{ route('email-threads.show', $thread) }}">View</a>
                        <x-delete-action
                            :action="route('email-threads.destroy', $thread)"
                            confirm="Move this thread to trash?"
                            label="Trash"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-500">No email threads yet. Send an outreach email to start one.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $threads->links() }}
    </div>
</x-layouts.app>
