<x-layouts.app title="Inbox | Sales Tracker" heading="Inbox" eyebrow="Email threads">
    @php
        $hasActiveFilters = request()->hasAny(['status', 'unread', 'responded', 'opened', 'search', 'date_from', 'date_to', 'created_from', 'created_to']);
    @endphp

    <div class="mb-4 grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-5">
        <a href="{{ route('email-threads.index', ['reset' => 1]) }}" @class(['rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-600', 'ring-1 ring-sky-500/40' => ! $hasActiveFilters])>
            <p class="text-xs text-slate-400">Total threads</p>
            <p class="mt-0.5 text-lg font-semibold text-white">{{ $stats['total'] }}</p>
        </a>
        <a href="{{ route('email-threads.index', ['unread' => 1]) }}" @class(['rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-600', 'ring-1 ring-sky-500/40' => request('unread') === '1' && ! request()->hasAny(['status', 'responded', 'opened', 'search', 'date_from', 'date_to', 'created_from', 'created_to'])])>
            <p class="text-xs text-slate-400">Unread replies</p>
            <p class="mt-0.5 text-lg font-semibold text-sky-300">{{ $stats['unread'] }}</p>
        </a>
        <a href="{{ route('email-threads.index', ['status' => 'awaiting_reply']) }}" @class(['rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-600', 'ring-1 ring-sky-500/40' => request('status') === 'awaiting_reply' && ! request()->hasAny(['unread', 'responded', 'opened', 'search', 'date_from', 'date_to', 'created_from', 'created_to'])])>
            <p class="text-xs text-slate-400">Awaiting reply</p>
            <p class="mt-0.5 text-lg font-semibold text-white">{{ $stats['awaiting_reply'] }}</p>
        </a>
        <a href="{{ route('email-threads.index', ['responded' => 1]) }}" @class(['rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-600', 'ring-1 ring-sky-500/40' => request('responded') === '1' && ! request()->hasAny(['status', 'unread', 'opened', 'search', 'date_from', 'date_to', 'created_from', 'created_to'])])>
            <p class="text-xs text-slate-400">Responded</p>
            <p class="mt-0.5 text-lg font-semibold text-amber-200">{{ $stats['responded'] }}</p>
        </a>
        <a href="{{ route('email-threads.index', ['opened' => 1]) }}" @class(['rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-600', 'ring-1 ring-sky-500/40' => request('opened') === '1' && ! request()->hasAny(['status', 'unread', 'responded', 'search', 'date_from', 'date_to', 'created_from', 'created_to'])])>
            <p class="text-xs text-slate-400">Opened by client</p>
            <p class="mt-0.5 text-lg font-semibold text-emerald-200">{{ $stats['opened'] }}</p>
        </a>
    </div>

    <section class="panel mb-6">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-white">Filters</h3>
                <p class="mt-1 text-sm text-slate-400">Replies attach to your sent threads only. Deleted threads stay in trash.</p>
            </div>
            <a class="btn-secondary w-full shrink-0 sm:w-auto" href="{{ route('email-threads.trash') }}">
                Trash{{ ($trashedCount ?? 0) > 0 ? ' ('.$trashedCount.')' : '' }}
            </a>
        </div>

        <form method="get" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="sm:col-span-2 xl:col-span-2">
                    <label class="label" for="inbox-search">Search</label>
                    <input
                        id="inbox-search"
                        class="input"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Contact, email, subject"
                    >
                </div>

                <div>
                    <label class="label" for="inbox-status">Status</label>
                    <select id="inbox-status" class="input" name="status">
                        <option value="">All statuses</option>
                        @foreach (\App\Enums\EmailThreadStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="inbox-opened">Opened</label>
                    <select id="inbox-opened" class="input" name="opened">
                        <option value="">Opened + unopened</option>
                        <option value="1" @selected(request('opened') === '1')>Opened by client</option>
                    </select>
                </div>

                <div>
                    <label class="label" for="inbox-unread">Unread</label>
                    <select id="inbox-unread" class="input" name="unread">
                        <option value="">Read + unread</option>
                        <option value="1" @selected(request('unread') === '1')>Unread replies only</option>
                    </select>
                </div>

                <div>
                    <label class="label" for="inbox-created-from">Thread created from</label>
                    <input
                        id="inbox-created-from"
                        class="input"
                        type="date"
                        name="created_from"
                        value="{{ $createdFrom ?? request('created_from') }}"
                    >
                </div>

                <div>
                    <label class="label" for="inbox-created-to">Thread created to</label>
                    <input
                        id="inbox-created-to"
                        class="input"
                        type="date"
                        name="created_to"
                        value="{{ $createdTo ?? request('created_to') }}"
                    >
                </div>

                <div>
                    <label class="label" for="inbox-date-from">Last message from</label>
                    <input
                        id="inbox-date-from"
                        class="input"
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom ?? request('date_from') }}"
                    >
                </div>

                <div>
                    <label class="label" for="inbox-date-to">Last message to</label>
                    <input
                        id="inbox-date-to"
                        class="input"
                        type="date"
                        name="date_to"
                        value="{{ $dateTo ?? request('date_to') }}"
                    >
                </div>

                <div>
                    <label class="label" for="inbox-per-page">Per page</label>
                    <select id="inbox-per-page" class="input" name="per_page">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($perPage ?? 20) === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="filter-actions border-t border-slate-800 pt-4">
                <button class="btn-primary" type="submit">Apply filters</button>
                <a class="btn-secondary" href="{{ route('email-threads.index', ['reset' => 1]) }}">Reset</a>
            </div>
        </form>
    </section>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if ($threads->total() > 0)
                Showing {{ $threads->firstItem() }}–{{ $threads->lastItem() }} of {{ $threads->total() }}
            @else
                No threads
            @endif
        </p>
        <form method="post" action="{{ route('email-threads.bulk-destroy') }}" id="inbox-bulk-form"
              onsubmit="return confirm('Move selected threads to trash?')">
            @csrf
            <button class="btn-secondary" type="submit" id="inbox-bulk-delete" disabled>
                Trash selected
            </button>
        </form>
    </div>

    <x-data-table wide>
        <thead>
            <tr>
                <th class="w-10">
                    <input type="checkbox" id="inbox-select-all" class="rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40" title="Select all on this page">
                </th>
                <th>Contact</th>
                <th>Company</th>
                <th>Subject</th>
                <th>First message</th>
                <th>Emails</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Last message</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800" id="inbox-rows">
            @forelse ($threads as $thread)
                @php
                    $messageCount = (int) $thread->messages_count;
                    $replyCount = max(0, $messageCount - 1);
                @endphp
                <tr @class(['bg-sky-500/5' => $thread->has_unread])>
                    <td>
                        <input type="checkbox" form="inbox-bulk-form" name="ids[]" value="{{ $thread->id }}" class="inbox-row-check rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40">
                    </td>
                    <td>
                        <div class="flex items-start gap-2">
                            @if ($thread->has_unread)
                                <span class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full bg-sky-400" title="New reply"></span>
                            @endif
                            <div>
                                <p @class(['font-medium text-white', 'font-semibold' => $thread->has_unread])>{{ $thread->contact?->name ?: '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $thread->contact?->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="max-w-[12rem] truncate" title="{{ $thread->contact?->company }}">
                        {{ $thread->contact?->company ?: '—' }}
                    </td>
                    <td class="max-w-sm truncate" title="{{ $thread->subject }}">
                        @if ($thread->has_unread)
                            <span class="mr-1 rounded bg-sky-500/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-200">New</span>
                        @endif
                        {{ $thread->subject }}
                    </td>
                    <td class="whitespace-nowrap">
                        <p class="text-white">{{ optional($thread->created_at)->format('M d, Y') ?: '—' }}</p>
                        <p class="text-xs text-slate-500">{{ optional($thread->created_at)->format('H:i') ?: '' }}</p>
                    </td>
                    <td class="whitespace-nowrap">
                        <p class="font-medium text-white">{{ $messageCount }} {{ $messageCount === 1 ? 'email' : 'emails' }}</p>
                        <p @class([
                            'text-xs',
                            'text-amber-200' => $replyCount > 0,
                            'text-slate-500' => $replyCount === 0,
                        ])>
                            {{ $replyCount }} {{ $replyCount === 1 ? 'reply' : 'replies' }}
                        </p>
                    </td>
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
                    <td colspan="10" class="text-center text-slate-500">No email threads yet. Send an outreach email to start one.</td>
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
            var form = document.getElementById('inbox-bulk-form');
            var rowsRoot = document.getElementById('inbox-rows');
            if (!form || !rowsRoot) return;
            var selectAll = document.getElementById('inbox-select-all');
            var button = document.getElementById('inbox-bulk-delete');
            var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.inbox-row-check')); };
            var sync = function () {
                var rows = checks();
                var selected = rows.filter(function (el) { return el.checked; }).length;
                button.disabled = selected === 0;
                button.textContent = selected > 0 ? ('Trash selected (' + selected + ')') : 'Trash selected';
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
                if (event.target && event.target.classList.contains('inbox-row-check')) sync();
            });
            sync();
        })();
    </script>
</x-layouts.app>
