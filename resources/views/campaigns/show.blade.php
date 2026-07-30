<x-layouts.app title="{{ $campaign->name }} | Sales Tracker" heading="{{ $campaign->name }}" eyebrow="Campaign overview">
    <section class="panel">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-slate-400">{{ $campaign->channel->label() }}</p>
                <h3 class="mt-1 text-2xl font-semibold text-white">{{ $campaign->name }}</h3>
                <p class="mt-3 text-sm text-slate-400">Started {{ optional($campaign->start_date)->format('M d, Y') ?: 'not scheduled' }}</p>
            </div>

            <div class="flex gap-3">
                @can(\App\Support\Permissions::CAMPAIGNS_UPDATE)
                    <a class="btn-secondary" href="{{ route('campaigns.edit', $campaign) }}">Edit</a>
                @endcan
                @can(\App\Support\Permissions::CAMPAIGNS_DELETE)
                    <form method="post" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign?')">
                        @csrf
                        @method('delete')
                        <button class="btn-secondary" type="submit">Delete</button>
                    </form>
                @endcan
            </div>
        </div>

        <p class="mt-5 whitespace-pre-wrap text-slate-300">{{ $campaign->notes ?: 'No campaign notes yet.' }}</p>
    </section>

    <section class="panel mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-white">Related Interactions</h3>
                <p class="text-sm text-slate-400">All outreach items tied to this campaign.</p>
            </div>
            <a class="btn-secondary" href="{{ route('interactions.create', ['campaign_id' => $campaign->id]) }}">Add interaction</a>
        </div>

        <x-data-table>
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Channel</th>
                    <th>Direction</th>
                    <th>Response</th>
                    <th>Sent at</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($campaign->interactions->sortByDesc('sent_at') as $interaction)
                    <tr>
                        <td class="font-medium text-white">{{ $interaction->contact?->name ?: '—' }}</td>
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
                        <td colspan="6" class="text-center text-slate-500">No interactions linked to this campaign yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </section>
</x-layouts.app>
