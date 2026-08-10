<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Room::insert([
            ['id' => 1, 'name' => 'Room 1', 'capacity' => 10],
            ['id' => 2, 'name' => 'Room 2', 'capacity' => 20],
            ['id' => 3, 'name' => 'Room 3', 'capacity' => 30],
        ]);
    }
}
