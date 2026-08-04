<x-layouts.app title="AI Lead Search | Sales Tracker" heading="AI Lead Search" eyebrow="Queued OpenRouter lead discovery">
    <div class="mb-6 flex flex-wrap justify-stretch gap-3 sm:justify-end">
        @can(\App\Support\Permissions::LEAD_SEARCHES_CREATE)
            <a class="btn-primary w-full sm:w-auto" href="{{ route('lead-searches.create') }}">New AI Search</a>
        @endcan
    </div>

    <x-data-table wide>
        <thead>
            <tr>
                <th>When</th>
                <th>By</th>
                <th>Criteria</th>
                <th>Leads</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($leadSearches as $leadSearch)
                @php
                    $resultCount = count($leadSearch->raw_results['results'] ?? []);
                    $isReady = filled($leadSearch->raw_results);
                @endphp
                <tr>
                    <td class="whitespace-nowrap text-slate-300">{{ $leadSearch->created_at->format('M d, H:i') }}</td>
                    <td>{{ $leadSearch->creator?->name ?: 'System' }}</td>
                    <td class="max-w-md">
                        <p class="truncate text-slate-200" title="{{ $leadSearch->criteria }}">{{ \Illuminate\Support\Str::limit($leadSearch->criteria, 90) }}</p>
                    </td>
                    <td>{{ $resultCount }}</td>
                    <td>
                        <span @class([
                            'rounded-full px-2 py-1 text-xs font-semibold',
                            'bg-emerald-500/15 text-emerald-200' => $isReady,
                            'bg-amber-500/15 text-amber-200' => ! $isReady,
                        ])>
                            {{ $isReady ? 'Ready' : 'Pending' }}
                        </span>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <div class="row-actions">
                            <a class="link-action" href="{{ route('lead-searches.show', $leadSearch) }}">View</a>
                            @can(\App\Support\Permissions::LEAD_SEARCHES_DELETE)
                                <form method="post" action="{{ route('lead-searches.destroy', $leadSearch) }}" onsubmit="return confirm('Delete this lead search?')" class="inline">
                                    @csrf
                                    @method('delete')
                                    <button class="link-danger" type="submit">Delete</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-500">No AI lead searches yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $leadSearches->links() }}
    </div>
</x-layouts.app>
