@php
    use App\Support\Permissions;
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="label" for="name">Name</label>
        <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <input class="input" id="password" type="password" name="password" {{ $user->exists ? '' : 'required' }}>
        @if ($user->exists)
            <p class="mt-2 text-sm text-slate-500">Leave blank to keep the current password.</p>
        @endif
    </div>
    <div>
        <label class="label" for="password_confirmation">Confirm password</label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }}>
    </div>
    <div class="md:col-span-2">
        <p class="label">Roles</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            @foreach ($roles as $role)
                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-700 px-4 py-3 text-sm text-slate-200">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role->name }}"
                        @checked(collect(old('roles', $user->roles?->pluck('name')->all() ?? []))->contains($role->name))
                    >
                    {{ ucfirst($role->name) }}
                </label>
            @endforeach
        </div>
    </div>
</div>
