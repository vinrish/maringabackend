<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions'])->get();

        $rolesData = $roles->map(function ($role) {
            $permissions = $role->permissions->groupBy('subject')->map(function ($actions, $subject) {
                return [
                    'name' => ucfirst($subject),
                    'view' => $actions->contains('action', 'view'),
                    'write' => $actions->contains('action', 'edit'),
                    'create' => $actions->contains('action', 'create'),
                    'delete' => $actions->contains('action', 'delete'),
                    'manage' => $actions->contains('action', 'manage'),
                ];
            })->values();

            return [
                'role' => $role->name,
                'users' => $role->users->map(fn($user) => $user->avatar ?? $user->id)->values(),
                'details' => [
                    'name' => $role->name,
                    'permissions' => $permissions,
                ],
            ];
        });

        return response()->json($rolesData);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $this->syncPermissions($role, $validated['permissions']);
        }

        return response()->json(['message' => 'Role created successfully.', 'role' => $role]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
        ]);

        $role->update(['name' => $validated['name']]);
        $this->syncPermissions($role, $validated['permissions']);

        return response()->json(['message' => 'Role updated successfully.']);
    }

    private function syncPermissions(Role $role, array $permissions)
    {
        $permissionIds = [];
        foreach ($permissions as $permission) {
            foreach (['view', 'write', 'create', 'delete', 'manage'] as $action) {
                if (!empty($permission[$action])) {
                    $permissionRecord = Permission::firstOrCreate([
                        'action' => $action,
                        'subject' => strtolower($permission['name']),
                    ]);
                    $permissionIds[] = $permissionRecord->id;
                }
            }
        }
        $role->permissions()->sync($permissionIds);
    }
}
