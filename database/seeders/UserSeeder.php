<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. create a super admin
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@cs.com',
        ]);
        $superAdmin->syncRoles('Super Admin');

        // 2. create a admin unit
        $adminUnit = User::factory()->create([
            'name' => 'Admin Unit',
            'email' => 'adminunit@cs.com',
            'unit_id' => 1,
        ]);
        $adminUnit->syncRoles('Admin Unit');

        // 3. create a staff unit
        $staffUnit = User::factory()->create([
            'name' => 'Staff Unit',
            'email' => 'staffunit@cs.com',
            'unit_id' => 1,
        ]);
        $staffUnit->syncRoles('Staff Unit');

        // 4. create a user
        $staffUnit = User::factory()->create([
            'name' => 'User',
            'email' => 'user@cs.com',
        ]);
    }
}
