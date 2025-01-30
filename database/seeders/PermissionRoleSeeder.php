<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch roles
        $adminRole = Role::find(UserRole::ADMIN->value);
        $employeeRole = Role::find(UserRole::EMPLOYEE->value);
        $clientRole = Role::find(UserRole::CLIENT->value);

        // Fetch permissions
        $permissions = Permission::all();

        // Assign permissions to roles
        $adminRole->permissions()->attach($permissions->pluck('id')); // Admin gets all permissions

//      Assign specific permissions to Doctor role
        $clientPermissions = $permissions->whereIn('subject', ['company', 'client'])->pluck('id');
        $clientRole->permissions()->attach($clientPermissions);
//
//        // Assign limited permissions to User role
//        $userPermissions = $permissions->whereIn('subject', ['patient', 'doctor'])
//            ->where('action', 'view')
//            ->pluck('id');
//        $userRole->permissions()->attach($userPermissions);
    }
}
