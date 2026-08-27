<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Ruang Rapat Besar',
                'capacity' => 45,
                'has_display' => true,
                'facilities' => [
                    'Pengeras Suara', 'Proyektor', 'AC',
                    'Meja', 'Kursi', 'Wi-Fi',
                ],
            ],
            [
                'name' => 'Ruang Rapat Kecil',
                'capacity' => 10,
                'facilities' => [
                    'Proyektor', 'AC',
                    'Meja', 'Kursi', 'Wi-Fi',
                ],
            ],
            [
                'name' => 'Ruang Kepala',
                'capacity' => 5,
                'facilities' => [
                    'Smart TV', 'AC', 'Meja', 'Sofa', 'Wi-Fi',
                ],
            ],
        ];

        foreach ($rooms as $data) {
            $facilityNames = $data['facilities'];
            unset($data['facilities']);

            $room = Room::create([
                ...$data,
                'location' => 'Lantai 2',
                'has_display' => $data['has_display'] ?? false,
            ]);

            $facilityIds = Facility::whereIn('name', $facilityNames)
                ->pluck('id');

            $room->facilities()->sync($facilityIds);
        }
    }
}
