<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::USERS_VIEW);

        return view('users.index', [
            'users' => User::query()
                ->with('roles')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::USERS_CREATE);

        return view('users.create', [
            'user' => new User(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::USERS_CREATE);

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->syncRoles($request->validated('roles', []));

        return redirect()
            ->route('users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorizePermission(Permissions::USERS_UPDATE);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorizePermission(Permissions::USERS_UPDATE);

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        if (filled($request->validated('password'))) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);
        $user->syncRoles($request->validated('roles', []));

        return redirect()
            ->route('users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizePermission(Permissions::USERS_DELETE);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->hasRole('admin') && User::role('admin')->count() === 1) {
            return back()->withErrors(['user' => 'You cannot delete the last admin user.']);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'User deleted successfully.');
    }
}
