<x-layouts.app title="Reports | Sales Tracker" heading="Reports" eyebrow="Live outreach analytics">
    <section class="panel">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white">Email outreach overview</h3>
                <p class="mt-1 text-sm text-slate-400">Live totals from sent outbound mail, opens, and inbound replies.</p>
            </div>
            <p class="text-xs text-slate-500">Updated when you open this page</p>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Outbound sent</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($outreach['outbound_sent']) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($outreach['sends_this_week']) }} this week</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Open rate</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ $outreach['open_rate'] }}%</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($outreach['opened_messages']) }} opened messages</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Contacts emailed</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($outreach['contacts_emailed']) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($outreach['contacts_opened']) }} opened at least once</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-sm text-slate-400">Reply rate</p>
                <p class="mt-2 text-3xl font-semibold text-sky-300">{{ $outreach['reply_rate'] }}%</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($outreach['contacts_replied']) }} replied · {{ number_format($outreach['inbound_messages']) }} inbound</p>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <h3 class="text-lg font-semibold text-white">Outreach funnel</h3>
            <p class="mt-1 mb-5 text-sm text-slate-400">How the list moves from contacts to replies (unique people).</p>

            @php
                $funnelMax = max(1, ...array_column($funnel, 'value'));
            @endphp

            <div class="space-y-4">
                @foreach ($funnel as $step)
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-200">{{ $step['label'] }}</span>
                            <span class="tabular-nums text-slate-400">{{ number_format($step['value']) }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-800">
                            <div
                                class="h-3 rounded-full bg-gradient-to-r from-sky-400 to-cyan-300"
                                style="width: {{ max($step['value'] > 0 ? 8 : 0, min(100, ($step['value'] / $funnelMax) * 100)) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <h3 class="text-lg font-semibold text-white">Sends by day</h3>
            <p class="mt-1 mb-5 text-sm text-slate-400">Outbound volume for the last 14 days.</p>

            @php
                $dayMax = max(1, ...array_column($sendsByDay, 'value'));
            @endphp

            <div class="flex h-40 items-end gap-1.5">
                @foreach ($sendsByDay as $day)
                    <div class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-[10px] tabular-nums text-slate-500 opacity-0 transition group-hover:opacity-100">{{ $day['value'] }}</span>
                        <div
                            class="w-full rounded-t bg-sky-400/80"
                            style="height: {{ max($day['value'] > 0 ? 8 : 2, (int) round(($day['value'] / $dayMax) * 100)) }}%"
                            title="{{ $day['label'] }}: {{ $day['value'] }}"
                        ></div>
                        <span class="truncate text-[10px] text-slate-500">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-3">
        <section class="panel">
            <h3 class="text-lg font-semibold text-white">Touches per contact</h3>
            <p class="mt-1 mb-4 text-sm text-slate-400">How many outbound emails each contacted person has received.</p>
            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                    <span class="text-sm text-slate-300">1 email</span>
                    <span class="text-lg font-semibold tabular-nums text-white">{{ number_format($touches['one']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                    <span class="text-sm text-slate-300">2 emails</span>
                    <span class="text-lg font-semibold tabular-nums text-white">{{ number_format($touches['two']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                    <span class="text-sm text-slate-300">3+ emails</span>
                    <span class="text-lg font-semibold tabular-nums text-sky-300">{{ number_format($touches['three_plus']) }}</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <h3 class="text-lg font-semibold text-white">Pipeline status</h3>
            <p class="mt-1 mb-4 text-sm text-slate-400">Current contact statuses across the CRM.</p>
            <div class="space-y-3">
                @forelse ($statusMix as $status)
                    <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                        <span class="text-sm text-slate-300">{{ $status['label'] }}</span>
                        <span class="text-lg font-semibold tabular-nums text-white">{{ number_format($status['count']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No contacts yet.</p>
                @endforelse
            </div>
        </section>

        <section class="panel">
            <h3 class="text-lg font-semibold text-white">List quality</h3>
            <p class="mt-1 mb-4 text-sm text-slate-400">Gaps that limit multi-channel follow-up.</p>
            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                    <span class="text-sm text-slate-300">With phone</span>
                    <span class="text-lg font-semibold tabular-nums text-white">{{ number_format($dataQuality['with_phone']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                    <span class="text-sm text-slate-300">With LinkedIn</span>
                    <span class="text-lg font-semibold tabular-nums text-white">{{ number_format($dataQuality['with_linkedin']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2.5">
                    <span class="text-sm text-slate-300">Email on file, never emailed</span>
                    <span class="text-lg font-semibold tabular-nums text-amber-300">{{ number_format($dataQuality['never_emailed']) }}</span>
                </div>
            </div>
        </section>
    </div>

    <section class="panel mt-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-white">Hottest opens</h3>
            <p class="mt-1 text-sm text-slate-400">Contacts with the most tracked opens — prioritize these for LinkedIn / personal follow-up.</p>
        </div>

        <x-data-table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Contact</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th class="text-right">Emails opened</th>
                    <th class="text-right">Total opens</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($hotOpens as $index => $row)
                    <tr>
                        <td class="text-slate-500">{{ $index + 1 }}</td>
                        <td class="font-medium text-white">{{ $row->name }}</td>
                        <td>{{ $row->company ?: '—' }}</td>
                        <td class="capitalize text-slate-300">{{ $row->status ?: '—' }}</td>
                        <td class="text-right tabular-nums">{{ number_format((int) $row->emails_opened) }}</td>
                        <td class="text-right tabular-nums font-semibold text-emerald-300">{{ number_format((int) $row->total_opens) }}</td>
                        <td class="text-right">
                            <a class="link-action" href="{{ route('contacts.show', $row->contact_id) }}">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No tracked opens yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </section>

    <section class="panel mt-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-white">Multi-touch contacts</h3>
            <p class="mt-1 text-sm text-slate-400">People who received 2+ outbound emails — useful for sequence discipline and break-up timing.</p>
        </div>

        <x-data-table>
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th class="text-right">Emails sent</th>
                    <th>Replied</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($multiTouch as $row)
                    <tr>
                        <td class="font-medium text-white">{{ $row->name }}</td>
                        <td>{{ $row->company ?: '—' }}</td>
                        <td class="capitalize text-slate-300">{{ $row->status ?: '—' }}</td>
                        <td class="text-right tabular-nums">{{ number_format((int) $row->emails_sent) }}</td>
                        <td>
                            @if ($row->replied)
                                <span class="text-emerald-300">Yes</span>
                            @else
                                <span class="text-slate-500">No</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a class="link-action" href="{{ route('contacts.show', $row->contact_id) }}">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500">No multi-touch contacts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </section>

    <section class="panel mt-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white">Exports</h3>
                <p class="text-sm text-slate-400">Download CSV snapshots of contacts and interactions.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="btn-secondary" href="{{ route('reports.contacts.export') }}">Export contacts CSV</a>
                <a class="btn-secondary" href="{{ route('reports.interactions.export') }}">Export interactions CSV</a>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <h3 class="text-lg font-semibold text-white">Reply rate by campaign</h3>
            <p class="mt-1 mb-4 text-sm text-slate-400">Sent email threads that received at least one inbound reply.</p>

            <x-data-table>
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Replies</th>
                        <th>Sent threads</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($responseByCampaign as $row)
                        <tr>
                            <td class="font-medium text-white">{{ $row['label'] }}</td>
                            <td>{{ $row['replies'] }}</td>
                            <td>{{ $row['threads'] }}</td>
                            <td class="text-sky-300 font-semibold">{{ $row['rate'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-slate-500">No campaign data yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </section>

        <section class="panel">
            <h3 class="text-lg font-semibold text-white">Reply rate by source</h3>
            <p class="mt-1 mb-4 text-sm text-slate-400">Compare which lead sources get actual email replies.</p>

            <x-data-table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Replies</th>
                        <th>Sent threads</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($responseBySource as $row)
                        <tr>
                            <td class="font-medium text-white">{{ $row['label'] }}</td>
                            <td>{{ $row['replies'] }}</td>
                            <td>{{ $row['threads'] }}</td>
                            <td class="text-sky-300 font-semibold">{{ $row['rate'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-slate-500">No source data yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </section>
    </div>
</x-layouts.app>
