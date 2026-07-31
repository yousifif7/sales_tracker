<x-layouts.app title="AI Prompts | Sales Tracker" heading="AI Prompts" eyebrow="Lead search presets">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <p class="text-sm text-slate-400">Edit the prompts used on the AI Lead Search screen.</p>
        @can(\App\Support\Permissions::LEAD_SEARCH_PRESETS_CREATE)
            <a class="btn-primary w-full sm:w-auto" href="{{ route('lead-search-presets.create') }}">New prompt</a>
        @endcan
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Preview</th>
                <th>Order</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($presets as $preset)
                <tr>
                    <td class="font-medium text-white">{{ $preset->name }}</td>
                    <td class="max-w-md truncate text-slate-400" title="{{ $preset->criteria }}">{{ \Illuminate\Support\Str::limit($preset->criteria, 90) }}</td>
                    <td>{{ $preset->sort_order }}</td>
                    <td>
                        <span @class([
                            'rounded-full px-2 py-1 text-xs font-semibold',
                            'bg-emerald-500/15 text-emerald-200' => $preset->is_active,
                            'bg-slate-800 text-slate-400' => ! $preset->is_active,
                        ])>
                            {{ $preset->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <x-row-actions>
                        @can(\App\Support\Permissions::LEAD_SEARCH_PRESETS_UPDATE)
                            <a class="link-action" href="{{ route('lead-search-presets.edit', $preset) }}">Edit</a>
                        @endcan
                        <x-delete-action
                            :action="route('lead-search-presets.destroy', $preset)"
                            :permission="\App\Support\Permissions::LEAD_SEARCH_PRESETS_DELETE"
                            confirm="Delete this AI prompt?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500">No AI prompts yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $presets->links() }}
    </div>
</x-layouts.app>
