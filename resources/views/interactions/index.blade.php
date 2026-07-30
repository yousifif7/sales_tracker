<x-layouts.app title="Interactions | Sales Tracker" heading="Interactions" eyebrow="Outreach activity log">
    <div class="mb-6 flex justify-end">
        @can(\App\Support\Permissions::INTERACTIONS_CREATE)
            <a class="btn-primary" href="{{ route('interactions.create') }}">Log interaction</a>
        @endcan
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th>Contact</th>
                <th>Campaign</th>
                <th>Channel</th>
                <th>Direction</th>
                <th>Response</th>
                <th>Sent at</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($interactions as $interaction)
                <tr>
                    <td class="font-medium text-white">{{ $interaction->contact?->name ?: '—' }}</td>
                    <td>{{ $interaction->campaign?->name ?: '—' }}</td>
                    <td>{{ $interaction->channel->label() }}</td>
                    <td>{{ $interaction->direction->label() }}</td>
                    <td>{{ $interaction->response?->outcome?->label() ?: 'Pending' }}</td>
                    <td class="whitespace-nowrap">{{ optional($interaction->sent_at)->format('M d, Y H:i') ?: $interaction->created_at->format('M d, Y H:i') }}</td>
                    <x-row-actions>
                        @if ($interaction->contact)
                            <a class="link-action" href="{{ route('contacts.show', $interaction->contact) }}">View</a>
                        @endif
                        @can(\App\Support\Permissions::INTERACTIONS_UPDATE)
                            <a class="link-action" href="{{ route('interactions.edit', $interaction) }}">Edit</a>
                        @endcan
                        <x-delete-action
                            :action="route('interactions.destroy', $interaction)"
                            :permission="\App\Support\Permissions::INTERACTIONS_DELETE"
                            confirm="Delete this interaction?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-slate-500">No interactions recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $interactions->links() }}
    </div>
</x-layouts.app>
