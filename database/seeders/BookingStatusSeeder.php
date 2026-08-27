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
        collect([
            ['code' => 'PENDING', 'label' => 'Pending'],
            ['code' => 'APPROVED', 'label' => 'Approved'],
            ['code' => 'REJECTED', 'label' => 'Rejected'],
            ['code' => 'CANCELLED', 'label' => 'Cancelled'],
        ])->each(fn ($status) => BookingStatus::create($status));
    }
}
