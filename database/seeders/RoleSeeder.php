<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['user' => 'User', 'admin' => 'Admin', 'display' => 'Display', 'super_admin' => 'Super Admin'] as $code => $name) {
            Role::firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
