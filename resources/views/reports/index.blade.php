<x-layouts.app title="Reports | Sales Tracker" heading="Reports" eyebrow="Response rates and exports">
    <section class="panel">
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
