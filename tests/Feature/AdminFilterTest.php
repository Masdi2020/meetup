<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Facility;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function filterUser(string $name, string $username, string $role): User
{
    return User::create([
        'name' => $name,
        'username' => $username,
        'password' => 'password',
        'role' => $role,
    ]);
}

function filterRoom(string $name, string $location = 'Lantai 1'): Room
{
    return Room::create([
        'name' => $name,
        'capacity' => 10,
        'location' => $location,
        'is_available' => true,
        'has_display' => false,
    ]);
}

it('filters rooms on the server', function () {
    $admin = filterUser('Admin', 'filter-admin', 'admin');
    filterRoom('Ruang Anggrek', 'Lantai 2');
    filterRoom('Ruang Melati', 'Lantai 3');

    $this->actingAs($admin)
        ->get('/admin/rooms?search=Anggrek')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Room')
            ->has('rooms', 1)
            ->where('rooms.0.name', 'Ruang Anggrek')
            ->where('filters.search', 'Anggrek'));
});

it('filters facilities on the server', function () {
    $admin = filterUser('Admin', 'filter-admin', 'admin');
    Facility::create(['name' => 'Proyektor']);
    Facility::create(['name' => 'Papan Tulis']);

    $this->actingAs($admin)
        ->get('/admin/facilities?search=Proyektor')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Facility')
            ->has('facilities', 1)
            ->where('facilities.0.name', 'Proyektor')
            ->where('filters.search', 'Proyektor'));
});

it('filters users by search and role on the server', function () {
    $admin = filterUser('Admin', 'filter-admin', 'admin');
    filterUser('Alya User', 'alya', 'user');
    filterUser('Alya Display', 'alya-display', 'display');
    filterUser('Bima User', 'bima', 'user');

    $this->actingAs($admin)
        ->get('/admin/users?search=Alya&role=user')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/User')
            ->has('users', 1)
            ->where('users.0.username', 'alya')
            ->where('filters.search', 'Alya')
            ->where('filters.role', 'user'));
});

it('filters bookings by search status and room on the server', function () {
    $admin = filterUser('Admin', 'filter-admin', 'admin');
    $owner = filterUser('Pemilik Booking', 'booking-owner', 'user');
    $firstRoom = filterRoom('Ruang Utama');
    $secondRoom = filterRoom('Ruang Cadangan');
    $approved = BookingStatus::create([
        'code' => 'APPROVED',
        'label' => 'Approved',
    ]);
    $pending = BookingStatus::create([
        'code' => 'PENDING',
        'label' => 'Pending',
    ]);

    foreach ([
        [$firstRoom, $approved, 'Rapat Mingguan'],
        [$secondRoom, $approved, 'Rapat Mingguan'],
        [$secondRoom, $pending, 'Rapat Mingguan'],
    ] as [$room, $status, $title]) {
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'date' => '2026-09-03',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => $title,
            'participants_count' => 5,
            'status_id' => $status->id,
        ]);
    }

    $this->actingAs($admin)
        ->get("/admin/bookings?search=Mingguan&status=approved&room={$secondRoom->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Booking')
            ->has('bookings.data', 1)
            ->where('bookings.data.0.room', 'Ruang Cadangan')
            ->where('bookings.data.0.status', 'approved')
            ->where('statuses.0.code', 'approved')
            ->where('statuses.0.label', 'Approved')
            ->where('filters.search', 'Mingguan')
            ->where('filters.status', 'approved')
            ->where('filters.room', (string) $secondRoom->id));
});
