<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbilityController extends Controller
{
    public function getAbilities(Request $request)
    {
        $permissions = auth()->user()->roles()
            ->with('permissions')  // Eager load permissions with roles
            ->get()
            ->pluck('permissions') // Get permissions from roles
            ->flatten()  // Flatten the nested collection of permissions
            ->map(function ($permission) {
                return [
                    'action' => $permission->action,
                    'subject' => $permission->subject,
//                    'conditions' => $permission->conditions, // Include conditions (optional)
                ];
            })
            ->toArray();

        return response()->json([
            'userAbilityRules' => $permissions,
        ]);

//        $user = $request->user();
//
//        // Fetch the user's roles and permissions
//        $roles = $user->roles()->with('permissions')->get();
//
//        // Gather all permissions into a list
//        $abilities = $roles->flatMap(function ($role) {
//            return $role->permissions->pluck('name');
//        })->unique();
//
//        return response()->json([
//            'roles' => $roles->pluck('name'), // list of roles
//            'permissions' => $abilities // unique permissions of the user
//        ]);
    }
}
