<?php

namespace App\Console\Commands;

use App\Services\BookingWorkflowService;
use Illuminate\Console\Command;

class FinishExpiredBookings extends Command
{
    protected $signature = 'bookings:finish-expired';

    protected $description = 'Mengubah booking approved yang waktunya telah berakhir menjadi finished';

    public function handle(BookingWorkflowService $bookingWorkflow): int
    {
        $count = $bookingWorkflow->finishExpired();

        $this->info("{$count} booking diubah menjadi finished.");

        return self::SUCCESS;
    }
}
