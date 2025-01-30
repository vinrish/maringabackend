<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['action' => 'create', 'subject' => 'client', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'client', 'conditions' => null],
            ['action' => 'view', 'subject' => 'client', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'client', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'client', 'conditions' => null],
            ['action' => 'create', 'subject' => 'company', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'company', 'conditions' => null],
            ['action' => 'view', 'subject' => 'company', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'company', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'company', 'conditions' => null],
            ['action' => 'create', 'subject' => 'business', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'business', 'conditions' => null],
            ['action' => 'view', 'subject' => 'business', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'business', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'business', 'conditions' => null],
            ['action' => 'create', 'subject' => 'file', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'file', 'conditions' => null],
            ['action' => 'view', 'subject' => 'file', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'file', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'file', 'conditions' => null],
            ['action' => 'create', 'subject' => 'hrm', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'hrm', 'conditions' => null],
            ['action' => 'view', 'subject' => 'hrm', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'hrm', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'hrm', 'conditions' => null],
            ['action' => 'create', 'subject' => 'task', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'task', 'conditions' => null],
            ['action' => 'view', 'subject' => 'task', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'task', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'task', 'conditions' => null],
            ['action' => 'create', 'subject' => 'fee_note', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'fee_note', 'conditions' => null],
            ['action' => 'view', 'subject' => 'fee_note', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'fee_note', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'fee_note', 'conditions' => null],
            ['action' => 'create', 'subject' => 'payment', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'payment', 'conditions' => null],
            ['action' => 'view', 'subject' => 'payment', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'payment', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'payment', 'conditions' => null],
            ['action' => 'create', 'subject' => 'employee', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'employee', 'conditions' => null],
            ['action' => 'view', 'subject' => 'employee', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'employee', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'employee', 'conditions' => null],
            ['action' => 'create', 'subject' => 'service', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'service', 'conditions' => null],
            ['action' => 'view', 'subject' => 'service', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'service', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'service', 'conditions' => null],
            ['action' => 'create', 'subject' => 'invoice', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'invoice', 'conditions' => null],
            ['action' => 'view', 'subject' => 'invoice', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'invoice', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'invoice', 'conditions' => null],
            ['action' => 'create', 'subject' => 'obligation', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'obligation', 'conditions' => null],
            ['action' => 'view', 'subject' => 'obligation', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'obligation', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'obligation', 'conditions' => null],
            ['action' => 'create', 'subject' => 'user', 'conditions' => null],
            ['action' => 'edit', 'subject' => 'user', 'conditions' => null],
            ['action' => 'view', 'subject' => 'user', 'conditions' => null],
            ['action' => 'delete', 'subject' => 'user', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'user', 'conditions' => null],
            ['action' => 'approve', 'subject' => 'payroll', 'conditions' => null],
            ['action' => 'generate', 'subject' => 'payroll', 'conditions' => null],
            ['action' => 'email', 'subject' => 'payroll', 'conditions' => null],
            ['action' => 'view', 'subject' => 'client_dash', 'conditions' => null],
            ['action' => 'view', 'subject' => 'admin_dash', 'conditions' => null],
            ['action' => 'view', 'subject' => 'employee_dash', 'conditions' => null],
            ['action' => 'manage', 'subject' => 'all', 'conditions' => null],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
