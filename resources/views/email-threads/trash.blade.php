<x-layouts.app title="Inbox Trash | Sales Tracker" heading="Inbox Trash" eyebrow="Soft-deleted email threads">
    <section class="panel mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-400">Restore a thread, or permanently delete it. Permanent delete cannot be undone.</p>
            <a class="btn-secondary" href="{{ route('email-threads.index') }}">Back to inbox</a>
        </div>
        <form method="get" class="mt-4 grid gap-3 md:grid-cols-3">
            <input class="input mt-0 md:col-span-2" name="search" value="{{ request('search') }}" placeholder="Search contact, email, subject">
            <button class="btn-primary" type="submit">Filter</button>
        </form>
    </section>

    <x-data-table>
        <thead>
            <tr>
                <th>Contact</th>
                <th>Subject</th>
                <th>Tracking</th>
                <th>Trashed</th>
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
                    <td class="whitespace-nowrap">{{ optional($thread->deleted_at)->format('M d, H:i') ?: '—' }}</td>
                    <x-row-actions>
                        <form method="post" action="{{ route('email-threads.restore', $thread->id) }}" class="inline">
                            @csrf
                            <button class="link-action" type="submit">Restore</button>
                        </form>
                        <x-delete-action
                            :action="route('email-threads.force-destroy', $thread->id)"
                            confirm="Permanently delete this thread and its messages? This cannot be undone."
                            label="Delete forever"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500">Trash is empty.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $threads->links() }}
    </div>
</x-layouts.app>
