<?php

use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;

function bookingStatus(string $code): BookingStatus
{
    return BookingStatus::firstOrCreate(
        ['code' => $code],
        ['label' => ucfirst(strtolower($code))],
    );
}

function bookingUser(string $username = 'user', string $role = 'user'): User
{
    return User::create([
        'name' => ucfirst($username),
        'username' => $username,
        'password' => 'password',
        'role' => $role,
    ]);
}

function bookingRoom(): Room
{
    return Room::create([
        'name' => 'Ruang Rapat',
        'capacity' => 10,
        'location' => 'Lantai 1',
        'is_available' => true,
        'has_display' => false,
    ]);
}

function bookingRecord(User $user, Room $room, string $status, array $attributes = []): Booking
{
    return Booking::create(array_merge([
        'room_id' => $room->id,
        'user_id' => $user->id,
        'date' => '2026-09-02',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'title' => 'Rapat awal',
        'participants_count' => 5,
        'status_id' => bookingStatus($status)->id,
    ], $attributes));
}

beforeEach(function () {
    Carbon::setTestNow('2026-09-01 12:00:00');

    foreach (['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED', 'FINISHED'] as $status) {
        bookingStatus($status);
    }
});

afterEach(function () {
    Carbon::setTestNow();
});

it('allows an owner to edit an approved booking', function () {
    $user = bookingUser();
    $booking = bookingRecord($user, bookingRoom(), 'APPROVED');

    $response = $this->actingAs($user)->put("/booking/{$booking->id}", [
        'title' => 'Rapat yang diperbarui',
        'date' => '2026-09-03',
        'start_time' => '13:00',
        'end_time' => '14:00',
    ]);

    $response->assertRedirect();
    $booking->refresh();

    expect($booking->title)->toBe('Rapat yang diperbarui')
        ->and($booking->date->format('Y-m-d'))->toBe('2026-09-03')
        ->and($booking->start_time->format('H:i'))->toBe('13:00')
        ->and($booking->end_time->format('H:i'))->toBe('14:00')
        ->and($booking->status->code)->toBe('APPROVED');

    $this->assertDatabaseHas('audits', [
        'entity_type' => 'Booking',
        'entity_id' => $booking->id,
        'action' => 'updated',
        'changed_by' => $user->id,
    ]);
});

it('allows an owner to cancel an approved booking', function () {
    $user = bookingUser();
    $booking = bookingRecord($user, bookingRoom(), 'APPROVED');

    $this->actingAs($user)
        ->put("/booking/{$booking->id}/cancel")
        ->assertRedirect();

    expect($booking->refresh()->status->code)->toBe('CANCELLED');
});

it('prevents another user from changing a booking', function () {
    $owner = bookingUser('owner');
    $otherUser = bookingUser('other');
    $booking = bookingRecord($owner, bookingRoom(), 'APPROVED');

    $this->actingAs($otherUser)
        ->put("/booking/{$booking->id}", [
            'title' => 'Diubah pengguna lain',
            'date' => '2026-09-03',
            'start_time' => '13:00',
            'end_time' => '14:00',
        ])
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->put("/booking/{$booking->id}/cancel")
        ->assertForbidden();

    expect($booking->refresh()->title)->toBe('Rapat awal')
        ->and($booking->status->code)->toBe('APPROVED');
});

it('lets an admin update an approved booking owned by another user', function () {
    $admin = bookingUser('admin', 'admin');
    $owner = bookingUser('owner');
    $booking = bookingRecord($owner, bookingRoom(), 'APPROVED');

    $this->actingAs($admin)
        ->put("/admin/bookings/{$booking->id}", [
            'title' => 'Diubah oleh admin',
            'date' => '2026-09-03',
            'start_time' => '13:00',
            'end_time' => '14:00',
        ])
        ->assertRedirect();

    expect($booking->refresh()->title)->toBe('Diubah oleh admin')
        ->and($booking->user_id)->toBe($owner->id)
        ->and($booking->status->code)->toBe('APPROVED');

    $this->assertDatabaseHas('audits', [
        'entity_type' => 'Booking',
        'entity_id' => $booking->id,
        'action' => 'updated',
        'changed_by' => $admin->id,
        'comment' => 'booking diperbarui oleh admin',
    ]);
});

it('lets an admin cancel an approved booking owned by another user', function () {
    $admin = bookingUser('admin', 'admin');
    $owner = bookingUser('owner');
    $booking = bookingRecord($owner, bookingRoom(), 'APPROVED');

    $this->actingAs($admin)
        ->put("/admin/bookings/{$booking->id}/cancel")
        ->assertRedirect();

    expect($booking->refresh()->user_id)->toBe($owner->id)
        ->and($booking->status->code)->toBe('CANCELLED')
        ->and($booking->processed_by)->toBe($admin->id)
        ->and($booking->processed_notes)->toBe('Dibatalkan oleh admin');
});

it('prevents a display account from changing any booking', function () {
    $display = bookingUser('display', 'display');
    $owner = bookingUser('owner');
    $booking = bookingRecord($owner, bookingRoom(), 'APPROVED');
    $payload = [
        'title' => 'Tidak boleh berubah',
        'date' => '2026-09-03',
        'start_time' => '13:00',
        'end_time' => '14:00',
    ];

    $this->actingAs($display)
        ->put("/admin/bookings/{$booking->id}", $payload)
        ->assertForbidden();

    $this->actingAs($display)
        ->put("/booking/{$booking->id}", $payload)
        ->assertForbidden();

    expect($booking->refresh()->title)->toBe('Rapat awal')
        ->and($booking->status->code)->toBe('APPROVED');
});

it('enforces action and status permissions through booking middleware', function () {
    $admin = bookingUser('admin', 'admin');
    $owner = bookingUser('owner');
    $room = bookingRoom();
    $pending = bookingRecord($owner, $room, 'PENDING');
    $approved = bookingRecord($owner, $room, 'APPROVED', [
        'start_time' => '11:00',
        'end_time' => '12:00',
    ]);

    $this->actingAs($admin)
        ->put("/admin/bookings/{$pending->id}", [
            'title' => 'Tidak boleh diubah',
            'date' => '2026-09-03',
            'start_time' => '13:00',
            'end_time' => '14:00',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch("/admin/bookings/{$approved->id}/approve")
        ->assertForbidden();

    $this->actingAs($owner)
        ->patch("/booking/{$pending->id}/finish")
        ->assertForbidden();

    expect($pending->refresh()->title)->toBe('Rapat awal')
        ->and($pending->status->code)->toBe('PENDING')
        ->and($approved->refresh()->status->code)->toBe('APPROVED');
});

it('rejects an edit that overlaps another active booking', function () {
    $user = bookingUser();
    $room = bookingRoom();
    $booking = bookingRecord($user, $room, 'APPROVED');
    bookingRecord($user, $room, 'PENDING', [
        'date' => '2026-09-03',
        'start_time' => '13:30',
        'end_time' => '15:00',
    ]);

    $this->actingAs($user)
        ->from('/riwayat')
        ->put("/booking/{$booking->id}", [
            'title' => 'Rapat bentrok',
            'date' => '2026-09-03',
            'start_time' => '13:00',
            'end_time' => '14:00',
        ])
        ->assertRedirect('/riwayat')
        ->assertSessionHasErrors('date');

    expect($booking->refresh()->title)->toBe('Rapat awal');
});

it('lets an admin select the borrower and any database status when creating a booking', function (string $status) {
    $admin = bookingUser('admin', 'admin');
    $borrower = bookingUser('borrower');
    $room = bookingRoom();

    $this->actingAs($admin)
        ->post('/admin/bookings', [
            'user_id' => $borrower->id,
            'room_id' => $room->id,
            'date' => '2026-08-31',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => "Booking {$status}",
            'participants' => 4,
            'request' => null,
            'status' => strtolower($status),
        ])
        ->assertRedirect();

    $booking = Booking::where('title', "Booking {$status}")->firstOrFail();

    expect($booking->user_id)->toBe($borrower->id)
        ->and($booking->status->code)->toBe($status)
        ->and($booking->processed_by)->toBe($status === 'PENDING' ? null : $admin->id);

    $this->assertDatabaseHas('audits', [
        'entity_type' => 'Booking',
        'entity_id' => $booking->id,
        'action' => 'created',
        'changed_by' => $admin->id,
    ]);
})->with(['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED', 'FINISHED']);

it('lets a regular user create their own pending booking', function () {
    $user = bookingUser();
    $room = bookingRoom();

    $this->actingAs($user)
        ->post('/booking', [
            'room_id' => $room->id,
            'date' => '2026-09-02',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Booking pengguna',
            'participants' => 3,
            'request' => null,
        ])
        ->assertRedirect();

    $booking = Booking::where('title', 'Booking pengguna')->firstOrFail();

    expect($booking->user_id)->toBe($user->id)
        ->and($booking->status->code)->toBe('PENDING')
        ->and($booking->processed_by)->toBeNull();
});

it('lets an admin select an admin account as the borrower', function () {
    $admin = bookingUser('admin', 'admin');
    $room = bookingRoom();

    $this->actingAs($admin)
        ->post('/admin/bookings', [
            'user_id' => $admin->id,
            'room_id' => $room->id,
            'date' => '2026-09-02',
            'start_time' => '13:00',
            'end_time' => '14:00',
            'title' => 'Booking oleh admin',
            'participants' => 2,
            'request' => null,
            'status' => 'pending',
        ])
        ->assertRedirect();

    $booking = Booking::where('title', 'Booking oleh admin')->firstOrFail();

    expect($booking->user_id)->toBe($admin->id)
        ->and($booking->status->code)->toBe('PENDING');
});

it('automatically finishes only expired approved bookings', function () {
    $user = bookingUser();
    $room = bookingRoom();
    $expired = bookingRecord($user, $room, 'APPROVED', [
        'date' => '2026-09-01',
        'start_time' => '10:00',
        'end_time' => '11:59',
    ]);
    $atBoundary = bookingRecord($user, $room, 'APPROVED', [
        'date' => '2026-09-01',
        'start_time' => '11:00',
        'end_time' => '12:00',
    ]);
    $future = bookingRecord($user, $room, 'APPROVED', [
        'date' => '2026-09-01',
        'start_time' => '12:00',
        'end_time' => '13:00',
    ]);
    $pending = bookingRecord($user, $room, 'PENDING', [
        'date' => '2026-08-31',
    ]);

    $this->artisan('bookings:finish-expired')
        ->expectsOutput('2 booking diubah menjadi finished.')
        ->assertSuccessful();

    expect($expired->refresh()->status->code)->toBe('FINISHED')
        ->and($atBoundary->refresh()->status->code)->toBe('FINISHED')
        ->and($future->refresh()->status->code)->toBe('APPROVED')
        ->and($pending->refresh()->status->code)->toBe('PENDING')
        ->and(Audit::where('comment', 'booking otomatis diakhiri oleh sistem')->count())->toBe(2);
});
