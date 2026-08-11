<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Facility::insert([
            [
                'id' => 1,
                'name' => 'Proyektor',
            ],
            [
                'id' => 2,
                'name' => 'Whiteboard',
            ],
            [
                'id' => 3,
                'name' => 'Speaker',
            ],
            [
                'id' => 4,
                'name' => 'Microphone',
            ],
        ]);
    }
}
