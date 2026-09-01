<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('RolesPermissions/Index', [
            'roles' => Role::with('permissions:id,name')->orderBy('level')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'level' => ['required', Rule::in(['coordination', 'eglise'])],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->forceFill(['level' => $data['level']])->save();
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Role cree avec permissions.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        Permission::create(['name' => $data['name'], 'guard_name' => 'web']);

        return back()->with('success', 'Permission creee.');
    }

    public function syncRolePermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Permissions du role mises a jour.');
    }
}
