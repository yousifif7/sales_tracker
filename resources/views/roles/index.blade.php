<x-layouts.app title="Roles & Permissions | Sales Tracker" heading="Roles & Permissions" eyebrow="Access control">
    <div class="mb-6 flex flex-wrap justify-stretch gap-3 sm:justify-end">
        @can(\App\Support\Permissions::ROLES_CREATE)
            <a class="btn-primary w-full sm:w-auto" href="{{ route('roles.create') }}">New role</a>
        @endcan
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th>Role</th>
                <th>Permissions</th>
                <th>Users</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($roles as $role)
                <tr>
                    <td class="font-medium text-white">{{ ucfirst($role->name) }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>{{ $role->users_count }}</td>
                    <x-row-actions>
                        @can(\App\Support\Permissions::ROLES_UPDATE)
                            <a class="link-action" href="{{ route('roles.edit', $role) }}">Edit</a>
                        @endcan
                        @if ($role->name !== 'admin')
                            <x-delete-action
                                :action="route('roles.destroy', $role)"
                                :permission="\App\Support\Permissions::ROLES_DELETE"
                                confirm="Delete this role?"
                            />
                        @endif
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-slate-500">No roles defined yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>
</x-layouts.app>
