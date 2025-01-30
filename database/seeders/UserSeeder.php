<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'first_name' => 'Shadow',
            'last_name' => 'Walker',
            'middle_name' => 'Knight',
            'email' => 'admin@example.com',
            'phone' => '0791925895',
//            'id_no' => '35254139',
            'password' => bcrypt('password'),
            'role_id' => UserRole::ADMIN->value,
        ]);

        // Assign Admin role to the user
//        $admin->roles()->attach(Role::where('name', 'Admin')->first());
        $admin->assignRole(Role::where('id', UserRole::ADMIN->value)->first());
    }
}
