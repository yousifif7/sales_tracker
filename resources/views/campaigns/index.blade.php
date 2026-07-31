<x-layouts.app title="Campaigns | Sales Tracker" heading="Campaigns" eyebrow="Outreach campaign tracking">
    <div class="mb-6 flex flex-wrap justify-stretch gap-3 sm:justify-end">
        @can(\App\Support\Permissions::CAMPAIGNS_CREATE)
            <a class="btn-primary w-full sm:w-auto" href="{{ route('campaigns.create') }}">New campaign</a>
        @endcan
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Channel</th>
                <th>Start date</th>
                <th>Interactions</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($campaigns as $campaign)
                <tr>
                    <td class="font-medium text-white">{{ $campaign->name }}</td>
                    <td>{{ $campaign->channel->label() }}</td>
                    <td>{{ optional($campaign->start_date)->format('M d, Y') ?: '—' }}</td>
                    <td>{{ $campaign->interactions_count }}</td>
                    <x-row-actions>
                        <a class="link-action" href="{{ route('campaigns.show', $campaign) }}">View</a>
                        @can(\App\Support\Permissions::CAMPAIGNS_UPDATE)
                            <a class="link-action" href="{{ route('campaigns.edit', $campaign) }}">Edit</a>
                        @endcan
                        <x-delete-action
                            :action="route('campaigns.destroy', $campaign)"
                            :permission="\App\Support\Permissions::CAMPAIGNS_DELETE"
                            confirm="Delete this campaign?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500">No campaigns created yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $campaigns->links() }}
    </div>
</x-layouts.app>
