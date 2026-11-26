<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\BookingStatusSeeder;

it('returns the closing time before shutdown checks begin', function (string $time, string $closingTime) {
    $this->travelTo(now()->parse($time));

    $this->get('/shutdown-check')
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'))
        ->assertExactJson([
            'action' => 'wait',
            'reason' => 'before_closing_time',
            'check_at' => $closingTime,
        ]);
})->with([
    ['2026-11-26 15:59:00', '2026-11-26 16:00:00'],
    ['2026-11-27 16:29:00', '2026-11-27 16:30:00'],
]);

it('waits until the latest approved booking ends after closing time', function () {
    $this->travelTo(now()->parse('2026-11-26 16:00:00'));
    $this->seed(BookingStatusSeeder::class);
    $room = Room::create(['name' => 'Meeting', 'capacity' => 10, 'location' => 'Office', 'is_available' => true]);
    $user = User::create([
        'name' => 'Employee',
        'username' => 'employee',
        'password' => 'password',
        'role_id' => Role::where('code', 'user')->firstOrFail()->id,
    ]);

    foreach ([['APPROVED', '16:30'], ['APPROVED', '17:00'], ['PENDING', '18:00']] as [$status, $endTime]) {
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-11-26',
            'start_time' => '15:00',
            'end_time' => $endTime,
            'title' => 'Meeting',
            'participants_count' => 3,
            'status_id' => BookingStatus::where('code', $status)->firstOrFail()->id,
        ]);
    }

    $this->get('/shutdown-check')
        ->assertOk()
        ->assertExactJson([
            'action' => 'wait',
            'reason' => 'active_booking',
            'check_at' => '2026-11-26 17:00:00',
        ]);

    $this->travelTo(now()->parse('2026-11-26 17:00:00'));

    $this->get('/shutdown-check')
        ->assertOk()
        ->assertExactJson(['action' => 'shutdown']);
});
