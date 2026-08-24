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
            [
                'id' => 1,
                'name' => 'Ruang Rapat Besar',
                'capacity' => 50,
                'location' => 'Lantai 2',
                'has_display' => true,
            ],
            [
                'id' => 2,
                'name' => 'Ruang Rapat Kecil',
                'capacity' => 10,
                'location' => 'Lantai 2',
                'has_display' => false,
            ],
            [
                'id' => 3,
                'name' => 'Ruang Kepala',
                'capacity' => 20,
                'location' => 'Lantai 2',
                'has_display' => false,
            ],
        ]);
    }
}
