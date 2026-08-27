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
        collect([
            'Pengeras Suara',
            'Proyektor',
            'Smart TV',
            'AC',
            'Meja',
            'Kursi',
            'Sofa',
            'Wi-Fi'
        ])->each(fn ($facility) => Facility::create(['name' => $facility]));
    }
}
