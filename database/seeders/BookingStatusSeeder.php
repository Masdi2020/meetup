<?php

namespace Database\Seeders;

use App\Models\BookingStatus;
use Illuminate\Database\Seeder;

class BookingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BookingStatus::insert([
            ['id' => 1, 'code' => 'PENDING', 'label' => 'Pending'],
            ['id' => 2, 'code' => 'APPROVED', 'label' => 'Approved'],
            ['id' => 3, 'code' => 'REJECTED', 'label' => 'Rejected'],
            ['id' => 4, 'code' => 'CANCELLED', 'label' => 'Cancelled'],
        ]);
    }
}
