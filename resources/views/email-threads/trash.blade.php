<x-layouts.app title="Inbox Trash | Sales Tracker" heading="Inbox Trash" eyebrow="Soft-deleted email threads">
    <section class="panel mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-400">Restore a thread, or permanently delete it. Permanent delete cannot be undone.</p>
            <a class="btn-secondary" href="{{ route('email-threads.index') }}">Back to inbox</a>
        </div>
        <form method="get" class="mt-4 grid gap-3 md:grid-cols-4">
            <input class="input mt-0 md:col-span-2" name="search" value="{{ request('search') }}" placeholder="Search contact, email, subject">
            <select class="input mt-0" name="per_page">
                @foreach ([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) ($perPage ?? 20) === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <button class="btn-primary" type="submit">Filter</button>
        </form>
    </section>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if ($threads->total() > 0)
                Showing {{ $threads->firstItem() }}–{{ $threads->lastItem() }} of {{ $threads->total() }}
            @else
                Trash is empty
            @endif
        </p>
        <form method="post" action="{{ route('email-threads.bulk-force-destroy') }}" id="trash-bulk-form"
              onsubmit="return confirm('Permanently delete selected threads and their messages? This cannot be undone.')">
            @csrf
            <button class="btn-secondary" type="submit" id="trash-bulk-delete" disabled>
                Delete forever
            </button>
        </form>
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th class="w-10">
                    <input type="checkbox" id="trash-select-all" class="rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40" title="Select all on this page">
                </th>
                <th>Contact</th>
                <th>Subject</th>
                <th>Tracking</th>
                <th>Trashed</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800" id="trash-rows">
            @forelse ($threads as $thread)
                <tr>
                    <td>
                        <input type="checkbox" form="trash-bulk-form" name="ids[]" value="{{ $thread->id }}" class="trash-row-check rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40">
                    </td>
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
                    <td colspan="6" class="text-center text-slate-500">Trash is empty.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            Page {{ $threads->currentPage() }} of {{ max(1, $threads->lastPage()) }}
        </p>
        <div>
            {{ $threads->links() }}
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('trash-bulk-form');
            var rowsRoot = document.getElementById('trash-rows');
            if (!form || !rowsRoot) return;
            var selectAll = document.getElementById('trash-select-all');
            var button = document.getElementById('trash-bulk-delete');
            var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.trash-row-check')); };
            var sync = function () {
                var rows = checks();
                var selected = rows.filter(function (el) { return el.checked; }).length;
                button.disabled = selected === 0;
                button.textContent = selected > 0 ? ('Delete forever (' + selected + ')') : 'Delete forever';
                if (selectAll) {
                    selectAll.checked = rows.length > 0 && selected === rows.length;
                    selectAll.indeterminate = selected > 0 && selected < rows.length;
                }
            };
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checks().forEach(function (el) { el.checked = selectAll.checked; });
                    sync();
                });
            }
            rowsRoot.addEventListener('change', function (event) {
                if (event.target && event.target.classList.contains('trash-row-check')) sync();
            });
            sync();
        })();
    </script>
</x-layouts.app>
