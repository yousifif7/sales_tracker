@php
    use App\Support\Permissions;

    $scopeTabs = [
        'today' => 'Due today',
        'upcoming' => 'Upcoming',
        'active' => 'All active',
        'recent' => 'Recently finished',
    ];

    $canBulk = auth()->user()?->can(Permissions::EMAILS_SEND);
    $tableColspan = ($scope === 'recent' ? 7 : 8) + ($canBulk ? 1 : 0);
    $sequenceService = app(\App\Services\OutreachSequenceService::class);
@endphp

<x-layouts.app title="Sequences | Sales Tracker" heading="Email sequences" eyebrow="Automated follow-up schedule">
    <section class="panel min-w-0">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-white">Automation schedule</h3>
                <p class="mt-1 text-sm text-slate-400">
                    Follow-up on business day {{ $sequenceConfig['followup_business_days'] }},
                    final nudge on day {{ $sequenceConfig['nudge_business_days'] }},
                    exit check on day {{ $sequenceConfig['exit_business_days'] }} ({{ $timezone }}).
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Now: {{ $now->format('D M j, Y g:ia') }} UK
                    · Cron runs every 15 min on UK weekdays
                    @if ($isBusinessDay)
                        · <span class="text-emerald-300">Business day — sends can run</span>
                    @else
                        · <span class="text-amber-300">Weekend — automation paused until Monday</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Active enrollments</p>
                <p class="mt-2 text-2xl font-semibold text-white sm:text-3xl">{{ number_format($stats['active']) }}</p>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Due right now</p>
                <p class="mt-2 text-2xl font-semibold text-amber-300 sm:text-3xl">{{ number_format($stats['due_now']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Scheduled time has passed</p>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Due today or overdue</p>
                <p class="mt-2 text-2xl font-semibold text-sky-300 sm:text-3xl">{{ number_format($stats['due_today']) }}</p>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Next 14 days</p>
                <p class="mt-2 text-2xl font-semibold text-white sm:text-3xl">{{ number_format($stats['upcoming']) }}</p>
            </div>
        </div>
    </section>

    <section class="panel mt-6 min-w-0">
        <div class="flex flex-wrap gap-2">
            @foreach ($scopeTabs as $key => $label)
                <a
                    href="{{ route('sequences.index', ['scope' => $key]) }}"
                    @class([
                        'rounded-xl px-4 py-2 text-sm font-semibold transition',
                        'bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/30' => $scope === $key,
                        'border border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-600 hover:bg-slate-900' => $scope !== $key,
                    ])
                >
                    {{ $label }}
                    @if ($key === 'today')
                        <span class="tabular-nums">({{ $stats['due_today'] }})</span>
                    @elseif ($key === 'upcoming')
                        <span class="tabular-nums">({{ $stats['upcoming'] }})</span>
                    @elseif ($key === 'active')
                        <span class="tabular-nums">({{ $stats['active'] }})</span>
                    @endif
                </a>
            @endforeach
        </div>
    </section>

    <section class="panel mt-6 min-w-0">
        <div class="mb-4 min-w-0">
            <h3 class="text-lg font-semibold text-white">{{ $scopeTabs[$scope] }}</h3>
            <p class="mt-1 text-sm text-slate-400">
                @if ($scope === 'today')
                    Contacts whose next automated step is today or already overdue.
                @elseif ($scope === 'upcoming')
                    Scheduled within the next 14 UK calendar days.
                @elseif ($scope === 'active')
                    Every contact currently enrolled in the email sequence.
                @else
                    Sequences that finished in the last 30 days. For missing template or send failures, use <strong class="font-semibold text-slate-300">Retry send</strong> after fixing templates.
                @endif
            </p>
        </div>

        @if ($canBulk && $enrollments->total() > 0)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-4">
                <p class="text-sm text-slate-500">
                    Showing {{ $enrollments->firstItem() }}–{{ $enrollments->lastItem() }} of {{ $enrollments->total() }}
                </p>
                <form method="post" action="{{ route('sequences.bulk-cancel') }}" id="sequences-bulk-form" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <button
                        class="btn-secondary text-xs"
                        type="submit"
                        id="sequences-bulk-send"
                        formaction="{{ route('sequences.bulk-send-now') }}"
                        disabled
                        onclick="return confirm('Send the current step now for all selected active enrollments?');"
                    >
                        Send now
                    </button>
                    <button
                        class="btn-secondary text-xs"
                        type="submit"
                        id="sequences-bulk-mark"
                        formaction="{{ route('sequences.bulk-mark-step') }}"
                        disabled
                        onclick="return confirm('Mark the current step as sent (without sending) for all selected enrollments?');"
                    >
                        Mark step sent
                    </button>
                    <button
                        class="btn-secondary text-xs"
                        type="submit"
                        id="sequences-bulk-retry"
                        formaction="{{ route('sequences.bulk-retry') }}"
                        disabled
                        onclick="return confirm('Reactivate and retry send for all selected failed enrollments?');"
                    >
                        Retry send
                    </button>
                    <button
                        class="btn-secondary text-xs"
                        type="submit"
                        id="sequences-bulk-cancel"
                        formaction="{{ route('sequences.bulk-cancel') }}"
                        disabled
                        onclick="return confirm('Cancel automation for all selected active enrollments?');"
                    >
                        Cancel selected
                    </button>
                </form>
            </div>
        @endif

        <div class="space-y-3 md:hidden">
            @forelse ($enrollments as $enrollment)
                @php
                    $contact = $enrollment->contact;
                    $isDue = $enrollment->isActive()
                        && $enrollment->next_action_at
                        && $enrollment->next_action_at->lte(now());
                    $canRetryRow = $sequenceService->canReactivate($enrollment);
                @endphp
                <article @class([
                    'rounded-xl border p-4',
                    'border-amber-500/30 bg-amber-500/5' => $isDue && $scope !== 'recent',
                    'border-slate-800 bg-slate-950/40' => ! $isDue || $scope === 'recent',
                ])>
                    @if ($canBulk)
                        <div class="mb-3 flex items-center gap-3 border-b border-slate-800/80 pb-3">
                            <input
                                type="checkbox"
                                form="sequences-bulk-form"
                                name="enrollment_ids[]"
                                value="{{ $enrollment->id }}"
                                class="sequences-row-check rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40"
                                data-can-cancel="{{ $enrollment->isActive() ? '1' : '0' }}"
                                data-can-send="{{ $enrollment->canSendNow() ? '1' : '0' }}"
                                data-can-mark="{{ $enrollment->canMarkCurrentStepComplete() ? '1' : '0' }}"
                                data-can-retry="{{ $canRetryRow ? '1' : '0' }}"
                            >
                            <span class="text-xs text-slate-500">Select for bulk action</span>
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($contact)
                                <a class="break-words font-medium text-white hover:text-sky-200" href="{{ route('contacts.show', $contact) }}">{{ $contact->name }}</a>
                                <p class="mt-1 break-words text-sm text-slate-400">{{ $contact->company ?: 'No company' }}</p>
                            @else
                                <p class="font-medium text-slate-500">Contact removed</p>
                            @endif
                        </div>
                        @if ($scope !== 'recent')
                            <x-sequence-schedule-badge :next-action-at="$enrollment->next_action_at" />
                        @else
                            <x-sequence-exit-badge :reason="$enrollment->exit_reason" />
                        @endif
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($scope !== 'recent')
                            <x-sequence-step-badge :step="$enrollment->next_step" />
                        @endif
                    </div>

                    <div class="mt-2 overflow-x-auto">
                        <x-sequence-progress :enrollment="$enrollment" />
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        @if ($scope !== 'recent')
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Scheduled (UK)</dt>
                                <dd class="text-right tabular-nums text-slate-200">
                                    @if ($enrollment->next_action_at)
                                        {{ $enrollment->next_action_at->timezone($timezone)->format('D M j, g:ia') }}
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                        @else
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Finished</dt>
                                <dd class="text-right tabular-nums text-slate-200">
                                    {{ $enrollment->completed_at?->timezone($timezone)->format('D M j, Y') ?: '—' }}
                                </dd>
                            </div>
                        @endif
                        @if ($enrollment->cold_subject)
                            <div>
                                <dt class="text-slate-500">Thread</dt>
                                <dd class="mt-1 break-words text-slate-300">{{ $enrollment->cold_subject }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-800/80 pt-4">
                        @if ($contact)
                            <a class="link-action text-sm" href="{{ route('contacts.show', $contact) }}">View contact</a>
                            @if ($enrollment->thread)
                                <a class="link-action text-sm" href="{{ route('email-threads.show', $enrollment->thread) }}">View thread</a>
                            @endif
                        @endif
                    </div>

                    <div class="mt-3 border-t border-slate-800/80 pt-3">
                        <x-sequence-manage-actions :enrollment="$enrollment" :contact="$contact" variant="panel" />
                    </div>
                </article>
            @empty
                <p class="text-center text-sm text-slate-500">
                    @if ($scope === 'recent')
                        No completed sequences in the last 30 days.
                    @else
                        No enrollments in this view.
                    @endif
                </p>
            @endforelse
        </div>

        <div class="hidden min-w-0 md:block">
            <x-data-table wide>
                <thead>
                    <tr>
                        @if ($canBulk)
                            <th class="w-10">
                                <input type="checkbox" id="sequences-select-all" class="rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40" title="Select all on this page">
                            </th>
                        @endif
                        <th>Contact</th>
                        @if ($scope === 'recent')
                            <th>Outcome</th>
                            <th>Finished</th>
                        @else
                            <th>Next step</th>
                            <th>Scheduled (UK)</th>
                            <th>Status</th>
                        @endif
                        <th class="whitespace-nowrap">Progress</th>
                        <th>Thread subject</th>
                        <th class="text-right whitespace-nowrap">Links</th>
                        <th class="text-right whitespace-nowrap">Automation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800" id="sequences-rows">
                    @forelse ($enrollments as $enrollment)
                        @php
                            $contact = $enrollment->contact;
                            $isDue = $enrollment->isActive()
                                && $enrollment->next_action_at
                                && $enrollment->next_action_at->lte(now());
                            $canRetryRow = $sequenceService->canReactivate($enrollment);
                        @endphp
                        <tr @class([
                            'bg-amber-500/[0.04]' => $isDue && $scope !== 'recent',
                        ])>
                            @if ($canBulk)
                                <td class="align-middle">
                                    <input
                                        type="checkbox"
                                        form="sequences-bulk-form"
                                        name="enrollment_ids[]"
                                        value="{{ $enrollment->id }}"
                                        class="sequences-row-check rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500/40"
                                        data-can-cancel="{{ $enrollment->isActive() ? '1' : '0' }}"
                                        data-can-send="{{ $enrollment->canSendNow() ? '1' : '0' }}"
                                        data-can-mark="{{ $enrollment->canMarkCurrentStepComplete() ? '1' : '0' }}"
                                        data-can-retry="{{ $canRetryRow ? '1' : '0' }}"
                                    >
                                </td>
                            @endif
                            <td class="min-w-[11rem] align-middle">
                                @if ($contact)
                                    <a class="block break-words font-medium text-white hover:text-sky-200" href="{{ route('contacts.show', $contact) }}">{{ $contact->name }}</a>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $contact->company ?: 'No company' }}</p>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            @if ($scope === 'recent')
                                <td class="align-middle whitespace-nowrap">
                                    <x-sequence-exit-badge :reason="$enrollment->exit_reason" />
                                </td>
                                <td class="whitespace-nowrap align-middle tabular-nums text-slate-300">
                                    {{ $enrollment->completed_at?->timezone($timezone)->format('M j, Y') ?: '—' }}
                                </td>
                            @else
                                <td class="align-middle whitespace-nowrap">
                                    <x-sequence-step-badge :step="$enrollment->next_step" />
                                </td>
                                <td class="whitespace-nowrap align-middle">
                                    @if ($enrollment->next_action_at)
                                        @php $scheduled = $enrollment->next_action_at->timezone($timezone); @endphp
                                        <span class="tabular-nums text-slate-200">{{ $scheduled->format('D M j, Y') }}</span>
                                        <span class="ml-2 text-xs tabular-nums text-slate-500">{{ $scheduled->format('g:ia') }} UK</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="align-middle whitespace-nowrap">
                                    <x-sequence-schedule-badge :next-action-at="$enrollment->next_action_at" />
                                </td>
                            @endif
                            <td class="align-middle whitespace-nowrap">
                                <x-sequence-progress :enrollment="$enrollment" />
                            </td>
                            <td class="max-w-[16rem] align-middle">
                                @if ($enrollment->cold_subject)
                                    <p class="truncate text-sm text-slate-300" title="{{ $enrollment->cold_subject }}">{{ $enrollment->cold_subject }}</p>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="align-middle text-right whitespace-nowrap">
                                <x-row-actions :as-cell="false">
                                    @if ($contact)
                                        <a class="link-action" href="{{ route('contacts.show', $contact) }}">Contact</a>
                                        @if ($enrollment->thread)
                                            <a class="link-action" href="{{ route('email-threads.show', $enrollment->thread) }}">Thread</a>
                                        @endif
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </x-row-actions>
                            </td>
                            <td class="align-middle text-right whitespace-nowrap">
                                <x-sequence-manage-actions :enrollment="$enrollment" :contact="$contact" variant="table" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColspan }}" class="py-10 text-center text-slate-500">
                                @if ($scope === 'recent')
                                    No completed sequences in the last 30 days.
                                @else
                                    No enrollments in this view.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>

        <div class="mt-6">
            {{ $enrollments->links() }}
        </div>
    </section>

    @if ($canBulk)
        <script>
            (function () {
                var form = document.getElementById('sequences-bulk-form');
                var rowsRoot = document.getElementById('sequences-rows');
                if (!form) return;

                var selectAll = document.getElementById('sequences-select-all');
                var sendButton = document.getElementById('sequences-bulk-send');
                var markButton = document.getElementById('sequences-bulk-mark');
                var retryButton = document.getElementById('sequences-bulk-retry');
                var cancelButton = document.getElementById('sequences-bulk-cancel');
                var checks = function () {
                    return Array.prototype.slice.call(document.querySelectorAll('.sequences-row-check'));
                };
                var countAttr = function (rows, attr) {
                    return rows.filter(function (el) {
                        return el.checked && el.getAttribute(attr) === '1';
                    }).length;
                };
                var sync = function () {
                    var rows = checks();
                    var selected = rows.filter(function (el) { return el.checked; });
                    var selectedCount = selected.length;
                    var sendCount = countAttr(selected, 'data-can-send');
                    var markCount = countAttr(selected, 'data-can-mark');
                    var retryCount = countAttr(selected, 'data-can-retry');
                    var cancelCount = countAttr(selected, 'data-can-cancel');

                    if (sendButton) {
                        sendButton.disabled = sendCount === 0;
                        sendButton.textContent = sendCount > 0 ? ('Send now (' + sendCount + ')') : 'Send now';
                    }
                    if (markButton) {
                        markButton.disabled = markCount === 0;
                        markButton.textContent = markCount > 0 ? ('Mark step sent (' + markCount + ')') : 'Mark step sent';
                    }
                    if (retryButton) {
                        retryButton.disabled = retryCount === 0;
                        retryButton.textContent = retryCount > 0 ? ('Retry send (' + retryCount + ')') : 'Retry send';
                    }
                    if (cancelButton) {
                        cancelButton.disabled = cancelCount === 0;
                        cancelButton.textContent = cancelCount > 0 ? ('Cancel selected (' + cancelCount + ')') : 'Cancel selected';
                    }
                    if (selectAll && rows.length > 0) {
                        selectAll.indeterminate = selectedCount > 0 && selectedCount < rows.length;
                        selectAll.checked = selectedCount === rows.length;
                    }
                };

                document.addEventListener('change', function (event) {
                    if (event.target.classList && event.target.classList.contains('sequences-row-check')) {
                        sync();
                    }
                    if (selectAll && event.target === selectAll) {
                        checks().forEach(function (el) {
                            el.checked = selectAll.checked;
                        });
                        sync();
                    }
                });

                sync();
            })();
        </script>
    @endif
</x-layouts.app>
