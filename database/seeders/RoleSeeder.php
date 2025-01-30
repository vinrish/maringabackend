<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id'    => UserRole::ADMIN->value,
                'name'  => 'Admin',
                'label' => 'Admin',
                'description' => 'Admin',
            ],
            [
                'id'    => UserRole::CLIENT->value,
                'name'  => 'Client',
                'label' => 'Client',
                'description' => 'Client',
            ],
            [
                'id'    => UserRole::EMPLOYEE->value,
                'name'  => 'Employee',
                'label' => 'Employee',
                'description' => 'Employee',
            ]
        ]);
    }
}
