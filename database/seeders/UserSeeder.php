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
        $users = [
            [
                'name' => 'Dimas',
                'username' => 'masdi2005',
                'password' => bcrypt('Kiwandim'),
                'role' => 'user',
            ],
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
