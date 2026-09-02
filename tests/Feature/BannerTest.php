<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

function bannerUser(string $username, string $role): User
{
    return User::create([
        'name' => ucfirst($username),
        'username' => $username,
        'password' => 'password',
        'role' => $role,
    ]);
}

function bannerRoom(string $name, bool $hasDisplay): Room
{
    return Room::create([
        'name' => $name,
        'capacity' => 10,
        'location' => 'Lantai 1',
        'is_available' => true,
        'has_display' => $hasDisplay,
    ]);
}

function bannerBooking(User $owner, Room $room, BookingStatus $status, string $title): Booking
{
    return Booking::create([
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'date' => '2026-09-02',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'title' => $title,
        'participants_count' => 5,
        'status_id' => $status->id,
    ]);
}

beforeEach(function () {
    Carbon::setTestNow('2026-09-02 09:30:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('shows the active booking for the selected display room', function () {
    $display = bannerUser('display', 'display');
    $owner = bannerUser('owner', 'user');
    $approved = BookingStatus::create([
        'code' => 'APPROVED',
        'label' => 'Approved',
    ]);
    $firstRoom = bannerRoom('Ruang Pertama', true);
    $secondRoom = bannerRoom('Ruang Kedua', true);

    bannerBooking($owner, $firstRoom, $approved, 'Rapat ruang pertama');
    $selectedBooking = bannerBooking(
        $owner,
        $secondRoom,
        $approved,
        'Rapat ruang kedua',
    );

    $this->actingAs($display)
        ->get(route('display.banner', ['room' => $secondRoom]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Banner')
            ->where('booking.id', $selectedBooking->id)
            ->where('booking.room_id', $secondRoom->id)
            ->where('booking.title', 'Rapat ruang kedua'));
});

it('does not expose a banner route for a room without a display', function () {
    $display = bannerUser('display', 'display');
    $room = bannerRoom('Ruang Tanpa Display', false);

    $this->actingAs($display)
        ->get(route('display.banner', ['room' => $room]))
        ->assertNotFound();
});
