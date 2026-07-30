<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::ROLES_VIEW);

        return view('roles.index', [
            'roles' => Role::query()
                ->withCount('permissions', 'users')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::ROLES_CREATE);

        return view('roles.create', [
            'role' => new Role(),
            'permissionGroups' => Permissions::grouped(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::ROLES_CREATE);

        $role = Role::query()->create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $this->authorizePermission(Permissions::ROLES_UPDATE);

        return view('roles.edit', [
            'role' => $role,
            'permissionGroups' => Permissions::grouped(),
            'selectedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizePermission(Permissions::ROLES_UPDATE);

        if ($role->name === 'admin' && $request->validated('name') !== 'admin') {
            return back()->withErrors(['name' => 'The admin role name cannot be changed.']);
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role permissions updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizePermission(Permissions::ROLES_DELETE);

        if ($role->name === 'admin') {
            return back()->withErrors(['role' => 'The admin role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Reassign users before deleting this role.']);
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role deleted successfully.');
    }
}
