<x-layouts.app title="Email Templates | Sales Tracker" heading="Email Templates" eyebrow="Outreach message library">
    <div class="mb-6 flex flex-wrap justify-stretch gap-3 sm:justify-end">
        @can(\App\Support\Permissions::EMAIL_TEMPLATES_CREATE)
            <a class="btn-primary w-full sm:w-auto" href="{{ route('email-templates.create') }}">New template</a>
        @endcan
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Subject</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($templates as $template)
                <tr>
                    <td class="font-medium text-white">{{ $template->name }}</td>
                    <td class="max-w-md truncate" title="{{ $template->subject }}">{{ $template->subject }}</td>
                    <td>
                        <span @class([
                            'rounded-full px-2 py-1 text-xs font-semibold',
                            'bg-emerald-500/15 text-emerald-200' => $template->is_active,
                            'bg-slate-800 text-slate-400' => ! $template->is_active,
                        ])>
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <x-row-actions>
                        @can(\App\Support\Permissions::EMAIL_TEMPLATES_UPDATE)
                            <a class="link-action" href="{{ route('email-templates.edit', $template) }}">Edit</a>
                        @endcan
                        <x-delete-action
                            :action="route('email-templates.destroy', $template)"
                            :permission="\App\Support\Permissions::EMAIL_TEMPLATES_DELETE"
                            confirm="Delete this template?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-slate-500">No templates yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $templates->links() }}
    </div>
</x-layouts.app>
