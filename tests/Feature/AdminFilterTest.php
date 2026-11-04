<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Facility;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

function filterUser(string $name, string $username, string $role): User
{
    return User::create([
        'name' => $name,
        'username' => $username,
        'password' => 'password',
        'role_id' => Role::where('code', $role)->firstOrFail()->id,
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

it('filters availability by multiple rooms and supports clearing the selection', function (bool $commaSeparated) {
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
            'room' => $commaSeparated ? "{$rooms[0]->id},{$rooms[2]->id}" : [$rooms[0]->id, $rooms[2]->id],
        ]);
        $this->actingAs($user)->get('/availability?'.$query)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Availability')
                ->where('selectedRoomIds', [$rooms[0]->id, $rooms[2]->id])
                ->has('events', 2)
                ->where('events', fn ($events) => collect($events)->pluck('room')->sort()->values()->all() === ['Anggrek', 'Mawar']));
    }

    foreach ([
        [null, $rooms->pluck('id')->all()],
        ['', []],
        ['all', $rooms->pluck('id')->all()],
        [0, $rooms->pluck('id')->all()],
        [$rooms[1]->id, [$rooms[1]->id]],
        [[0], []],
        ['none', []],
        ['unknown,999999', []],
        ["{$rooms[1]->id},{$rooms[1]->id},999999", [$rooms[1]->id]],
    ] as [$filter, $expected]) {
        $query = http_build_query(['date' => '2026-09-08', 'room' => $filter]);
        $this->actingAs($user)->get('/availability?'.$query)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedRoomIds', $expected)
                ->has('events', count($expected)));
    }
})->with(['array' => false, 'comma-separated' => true]);

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
    $secondRoom->update(['has_display' => true]);
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
            ->where('rooms', fn ($rooms) => collect($rooms)->pluck('has_display', 'id')->sortKeys()->all() === [
                $firstRoom->id => false,
                $secondRoom->id => true,
            ])
            ->where('statuses.0.code', 'approved')
            ->where('statuses.0.label', 'Approved')
            ->where('stats', ['total' => 3, 'pending' => 1, 'approved' => 2, 'finished' => 0])
            ->where('filters.search', 'Mingguan')
            ->where('filters.status', 'approved')
            ->where('filters.room', (string) $secondRoom->id));
});

it('loads booking statuses and their counts in a fixed number of queries', function (int $bookingCount) {
    $admin = filterUser('Admin', 'status-admin', 'admin');
    $room = filterRoom('Ruang Status');
    $approved = BookingStatus::create(['code' => 'APPROVED', 'label' => 'Approved']);
    $cancelled = BookingStatus::create(['code' => 'CANCELLED', 'label' => 'Cancelled']);
    BookingStatus::create(['code' => 'PENDING', 'label' => 'Pending']);
    $attributes = [
        'room_id' => $room->id,
        'user_id' => $admin->id,
        'date' => '2026-09-03',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'title' => 'Rapat Status',
        'participants_count' => 5,
        'status_id' => $approved->id,
    ];

    for ($index = 0; $index < $bookingCount; $index++) {
        Booking::create($attributes);
    }

    Booking::create($attributes)->delete();
    Booking::create([...$attributes, 'status_id' => $cancelled->id]);
    $statusQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$statusQueries) {
        if (str_contains($query->sql, 'booking_statuses')) {
            $statusQueries++;
        }
    });

    $this->actingAs($admin)->get('/admin/bookings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', [
                'total' => $bookingCount + 1,
                'pending' => 0,
                'approved' => $bookingCount,
                'finished' => 0,
            ])
            ->has('statuses', 3));

    expect($statusQueries)->toBe(2);
})->with([1, 10]);

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

    $query = [
        'columns' => ['id', 'activity'],
        'period' => 'all',
    ];

    $attachmentQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$attachmentQueries): void {
        if (str_contains($query->sql, 'booking_attachments')) {
            $attachmentQueries++;
        }
    });

    $response = $this->actingAs($admin)
        ->json('QUERY', '/admin/bookings/export-data', $query)
        ->assertOk()
        ->assertJsonPath('columns.0.key', 'number')
        ->assertJsonPath('columns.0.label', 'No')
        ->assertJsonPath('columns.1.key', 'id')
        ->assertJsonPath('columns.1.label', 'ID')
        ->assertJsonPath('rows.0.number', 1)
        ->assertJsonPath('rows.1.number', 2);

    expect($attachmentQueries)->toBe(0);
    expect($response->json('rows.*.id'))
        ->toEqualCanonicalizing($bookings->pluck('id')->all());
});

it('rejects unsupported booking export options through its form request', function () {
    $admin = filterUser('Admin', 'invalid-export-admin', 'admin');

    $query = [
        'columns' => ['password'],
        'statuses' => ['unknown'],
        'period' => 'invalid',
    ];

    $this->actingAs($admin)
        ->json('QUERY', '/admin/bookings/export-data', $query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['columns.0', 'statuses.0', 'period']);
});

it('restricts booking export queries to administrators', function () {
    $query = ['columns' => ['id'], 'period' => 'all'];

    $this->json('QUERY', '/admin/bookings/export-data', $query)->assertUnauthorized();

    $user = filterUser('Pengguna', 'export-forbidden-user', 'user');
    $this->actingAs($user)->json('QUERY', '/admin/bookings/export-data', $query)->assertForbidden();
});

it('requires a csrf token for booking export queries without a trusted origin', function () {
    $admin = filterUser('Admin', 'export-csrf-admin', 'admin');
    $query = ['columns' => ['id'], 'period' => 'all'];
    $token = 'export-csrf-token';
    $this->app->instance('env', 'local');

    $this->actingAs($admin)->withSession(['_token' => $token])
        ->json('QUERY', '/admin/bookings/export-data', $query)
        ->assertStatus(419);

    $this->withSession(['_token' => $token])
        ->json('QUERY', '/admin/bookings/export-data', $query, ['X-CSRF-TOKEN' => $token])
        ->assertOk()->assertJsonCount(0, 'rows');
});

it('provides export choices that the export endpoint accepts', function () {
    $admin = filterUser('Admin', 'export-options-admin', 'admin');
    $response = $this->actingAs($admin)->get('/admin/bookings')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('exportOptions.columns', 13)
            ->has('exportOptions.statuses', 5)
            ->where('exportOptions.columns.0.key', 'id')
            ->where('exportOptions.columns.0.label', 'ID'));
    $options = $response->inertiaProps('exportOptions');

    $this->json('QUERY', '/admin/bookings/export-data', [
        'columns' => collect($options['columns'])->pluck('key')->all(),
        'statuses' => collect($options['statuses'])->pluck('value')->all(),
    ])->assertOk()->assertJsonCount(14, 'columns');
});

it('loads only twenty preview bookings but downloads all matching rows', function () {
    $admin = filterUser('Admin', 'preview-admin', 'admin');
    $room = filterRoom('Export room');
    $status = BookingStatus::create(['code' => 'APPROVED', 'label' => 'Approved']);
    for ($index = 0; $index < 25; $index++) {
        Booking::create([
            'room_id' => $room->id, 'user_id' => $admin->id, 'status_id' => $status->id,
            'date' => '2026-09-03', 'start_time' => '09:00', 'end_time' => '10:00',
            'title' => 'Export meeting', 'participants_count' => 2,
        ]);
    }
    $loaded = 0;
    Booking::retrieved(function () use (&$loaded) {
        $loaded++;
    });
    $query = ['columns' => ['id', 'activity'], 'room' => $room->id, 'statuses' => ['APPROVED'], 'period' => 'date', 'date' => '2026-09-03'];

    try {
        $this->actingAs($admin)->json('QUERY', '/admin/bookings/export-data', [...$query, 'preview' => 1])
            ->assertOk()->assertJsonCount(20, 'rows')->assertJsonPath('meta.total', 25);
        expect($loaded)->toBe(20);
        $loaded = 0;
        $this->json('QUERY', '/admin/bookings/export-data', [...$query, 'preview' => 0])
            ->assertOk()->assertJsonCount(25, 'rows')->assertJsonPath('meta.total', 25);
        expect($loaded)->toBe(25);
        $this->json('QUERY', '/admin/bookings/export-data', [...$query, 'preview' => 1, 'date' => '2026-09-04'])
            ->assertOk()->assertJsonCount(0, 'rows')->assertJsonPath('meta.total', 0);
    } finally {
        Event::forget('eloquent.retrieved: '.Booking::class);
    }
});
