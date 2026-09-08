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

it('filters availability by multiple rooms and supports clearing the selection', function () {
    $user = filterUser('Pengguna', 'availability-user', 'user');
    $rooms = collect(['Anggrek', 'Melati', 'Mawar'])->map(fn ($name) => filterRoom($name));
    $status = BookingStatus::create(['code' => 'APPROVED', 'label' => 'Approved']);

    foreach ($rooms as $room) {
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'date' => '2026-09-08',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => $room->name,
            'participants_count' => 5,
            'status_id' => $status->id,
        ]);
    }

    foreach (['month', 'week', 'day'] as $view) {
        $query = http_build_query([
            'date' => '2026-09-08',
            'view' => $view,
            'room' => [$rooms[0]->id, $rooms[2]->id],
        ]);
        $this->actingAs($user)->get('/availability?'.$query)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Availability')
                ->where('selectedRoomIds', [$rooms[0]->id, $rooms[2]->id])
                ->has('events', 2)
                ->where('events', fn ($events) => collect($events)->pluck('room')->sort()->values()->all() === ['Anggrek', 'Mawar']));
    }

    foreach ([null, 0, $rooms[1]->id, [0]] as $filter) {
        $expected = is_array($filter) ? [] : ($filter ? [$filter] : $rooms->pluck('id')->all());
        $query = http_build_query(['date' => '2026-09-08', 'room' => $filter]);
        $this->actingAs($user)->get('/availability?'.$query)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedRoomIds', $expected)
                ->has('events', count($expected)));
    }
});

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

it('exports an automatic number column followed by the selected ID column', function () {
    $admin = filterUser('Admin', 'export-admin', 'admin');
    $owner = filterUser('Pemilik Booking', 'export-owner', 'user');
    $room = filterRoom('Ruang Ekspor');
    $approved = BookingStatus::create([
        'code' => 'APPROVED',
        'label' => 'Approved',
    ]);

    $bookings = collect(['Rapat Pertama', 'Rapat Kedua'])->map(fn (string $title, int $index) => Booking::create([
        'room_id' => $room->id,
        'user_id' => $owner->id,
        'date' => '2026-09-03',
        'start_time' => sprintf('%02d:00', 9 + $index),
        'end_time' => sprintf('%02d:00', 10 + $index),
        'title' => $title,
        'participants_count' => 5,
        'status_id' => $approved->id,
    ]));

    $query = http_build_query([
        'columns' => ['id', 'activity'],
        'period' => 'all',
    ]);

    $response = $this->actingAs($admin)
        ->getJson("/admin/bookings/export-data?{$query}")
        ->assertOk()
        ->assertJsonPath('columns.0.key', 'number')
        ->assertJsonPath('columns.0.label', 'No')
        ->assertJsonPath('columns.1.key', 'id')
        ->assertJsonPath('columns.1.label', 'ID')
        ->assertJsonPath('rows.0.number', 1)
        ->assertJsonPath('rows.1.number', 2);

    expect($response->json('rows.*.id'))
        ->toEqualCanonicalizing($bookings->pluck('id')->all());
});

it('rejects unsupported booking export options through its form request', function () {
    $admin = filterUser('Admin', 'invalid-export-admin', 'admin');

    $query = http_build_query([
        'columns' => ['password'],
        'statuses' => ['unknown'],
        'period' => 'invalid',
    ]);

    $this->actingAs($admin)
        ->getJson("/admin/bookings/export-data?{$query}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['columns.0', 'statuses.0', 'period']);
});
