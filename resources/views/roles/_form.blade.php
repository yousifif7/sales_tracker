@php
    use App\Support\Permissions;
@endphp

<div class="space-y-6">
    <div>
        <label class="label" for="name">Role name</label>
        <input
            class="input"
            id="name"
            name="name"
            value="{{ old('name', $role->name) }}"
            required
            {{ ($role->exists && $role->name === 'admin') ? 'readonly' : '' }}
            placeholder="sales-manager"
        >
        <p class="mt-2 text-sm text-slate-500">Use lowercase letters, numbers, dashes, or underscores.</p>
    </div>

    <div>
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-white">Permissions</h3>
                <p class="text-sm text-slate-400">Toggle what this role can access across the CRM.</p>
            </div>
            <button type="button" class="btn-secondary" onclick="document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => el.checked = true)">
                Select all
            </button>
        </div>

        <div class="mt-5 space-y-4">
            @foreach ($permissionGroups as $group => $permissions)
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $group }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="inline-flex items-center gap-3 rounded-xl border border-slate-800 px-3 py-2 text-sm text-slate-200">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission }}"
                                    @checked(collect(old('permissions', $selectedPermissions))->contains($permission))
                                >
                                <span>
                                    <span class="block font-medium text-white">{{ Permissions::label($permission) }}</span>
                                    <span class="block text-xs text-slate-500">{{ $permission }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
