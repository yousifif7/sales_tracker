<x-layouts.app title="AI Lead Search | Sales Tracker" heading="AI Lead Search" eyebrow="Queued OpenRouter lead discovery">
    <div class="mb-6 flex justify-end">
        @can(\App\Support\Permissions::LEAD_SEARCHES_CREATE)
            <a class="btn-primary" href="{{ route('lead-searches.create') }}">New AI Search</a>
        @endcan
    </div>

    <x-data-table>
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
            @forelse ($queries as $query)
                <tr>
                    <td class="whitespace-nowrap text-slate-300">{{ $query->created_at->format('M d, H:i') }}</td>
                    <td>{{ $query->creator?->name ?: 'System' }}</td>
                    <td class="max-w-md">
                        <p class="truncate text-slate-200" title="{{ $query->criteria }}">{{ \Illuminate\Support\Str::limit($query->criteria, 90) }}</p>
                    </td>
                    <td>{{ count($query->raw_results['results'] ?? []) }}</td>
                    <td>
                        <span @class([
                            'rounded-full px-2 py-1 text-xs font-semibold',
                            'bg-emerald-500/15 text-emerald-200' => filled($query->raw_results),
                            'bg-amber-500/15 text-amber-200' => blank($query->raw_results),
                        ])>
                            {{ filled($query->raw_results) ? 'Ready' : 'Pending' }}
                        </span>
                    </td>
                    <x-row-actions>
                        <a class="link-action" href="{{ route('lead-searches.show', $query) }}">View</a>
                        <x-delete-action
                            :action="route('lead-searches.destroy', $query)"
                            :permission="\App\Support\Permissions::LEAD_SEARCHES_DELETE"
                            confirm="Delete this lead search?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-500">No AI lead searches yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $queries->links() }}
    </div>
</x-layouts.app>
