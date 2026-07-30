<x-layouts.app title="Dashboard | Sales Tracker" heading="Dashboard" eyebrow="Sales Outreach Overview">
    <div class="grid gap-4 md:grid-cols-3">
        <div class="panel">
            <p class="text-sm text-slate-400">Response rate (30 days)</p>
            <p class="mt-3 text-4xl font-semibold text-white">{{ $responseRate }}%</p>
            <p class="mt-2 text-sm text-slate-500">Calculated from logged interactions that received responses.</p>
        </div>

        <div class="panel">
            <p class="text-sm text-slate-400">Follow-ups due today</p>
            <p class="mt-3 text-4xl font-semibold text-white">{{ $followUpsDueToday->count() }}</p>
            <p class="mt-2 text-sm text-slate-500">Uncompleted reminders that need attention now.</p>
        </div>

        <div class="panel">
            <p class="text-sm text-slate-400">Follow-ups due this week</p>
            <p class="mt-3 text-4xl font-semibold text-white">{{ $followUpsDueThisWeek->count() }}</p>
            <p class="mt-2 text-sm text-slate-500">Upcoming tasks scheduled through the end of the week.</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr,1fr]">
        <section class="panel">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">Pipeline Funnel</h3>
                    <p class="text-sm text-slate-400">Track how outreach is moving through your sales stages.</p>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($pipeline as $stage => $count)
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-200">{{ ucfirst($stage) }}</span>
                            <span class="text-slate-400">{{ $count }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-800">
                            <div class="h-3 rounded-full bg-gradient-to-r from-sky-400 to-cyan-300" style="width: {{ max(8, min(100, $count * 10)) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-white">Due Today</h3>
                    <p class="text-sm text-slate-400">Quick reminder list for today’s follow-ups.</p>
                </div>
                <a href="{{ route('follow-ups.index', ['scope' => 'today']) }}" class="text-sm text-sky-300 hover:text-sky-200">Open list</a>
            </div>

            <x-data-table>
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Note</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($followUpsDueToday as $followUp)
                        <tr>
                            <td class="font-medium text-white">{{ $followUp->contact?->name ?: '—' }}</td>
                            <td class="max-w-[12rem] truncate" title="{{ $followUp->note }}">{{ \Illuminate\Support\Str::limit($followUp->note, 40) }}</td>
                            <x-row-actions>
                                @if ($followUp->contact)
                                    <a class="link-action" href="{{ route('contacts.show', $followUp->contact) }}">View</a>
                                @endif
                                @can(\App\Support\Permissions::FOLLOW_UPS_UPDATE)
                                    <a class="link-action" href="{{ route('follow-ups.edit', $followUp) }}">Edit</a>
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
                            <td colspan="3" class="text-center text-slate-500">No follow-ups due today.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </section>
    </div>
</x-layouts.app>
