<x-layouts.app title="Users | Sales Tracker" heading="Users" eyebrow="Team access management">
    <div class="mb-6 flex flex-wrap justify-stretch gap-3 sm:justify-end">
        @can(\App\Support\Permissions::USERS_CREATE)
            <a class="btn-primary w-full sm:w-auto" href="{{ route('users.create') }}">New user</a>
        @endcan
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Roles</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($users as $user)
                <tr>
                    <td class="font-medium text-white">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->map(fn ($name) => ucfirst($name))->implode(', ') ?: 'None' }}</td>
                    <x-row-actions>
                        @can(\App\Support\Permissions::USERS_UPDATE)
                            <a class="link-action" href="{{ route('users.edit', $user) }}">Edit</a>
                        @endcan
                        <x-delete-action
                            :action="route('users.destroy', $user)"
                            :permission="\App\Support\Permissions::USERS_DELETE"
                            confirm="Delete this user?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-slate-500">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</x-layouts.app>
