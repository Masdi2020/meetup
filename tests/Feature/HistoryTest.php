<?php

use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('provides booking status options from the database', function () {
    $user = User::create([
        'name' => 'History User',
        'username' => 'history-user',
        'password' => 'password',
        'role_id' => Role::where('code', 'user')->firstOrFail()->id,
    ]);

    BookingStatus::create([
        'code' => 'REJECTED',
        'label' => 'Ditolak',
    ]);
    BookingStatus::create([
        'code' => 'FINISHED',
        'label' => 'Selesai',
    ]);

    $this->actingAs($user)
        ->get('/riwayat')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/History')
            ->has('statuses', 2)
            ->where('statuses.0.value', 'Rejected')
            ->where('statuses.0.label', 'Ditolak')
            ->where('statuses.1.value', 'Finished')
            ->where('statuses.1.label', 'Selesai'));
});

function historyBooking(User $owner, string $status, string $title): Booking
{
    $room = Room::firstOrCreate(['name' => 'History Room'], ['location' => 'Office', 'capacity' => 10, 'is_available' => true]);
    $bookingStatus = BookingStatus::firstOrCreate(['code' => $status], ['label' => ucfirst(strtolower($status))]);

    return Booking::create([
        'room_id' => $room->id, 'user_id' => $owner->id, 'status_id' => $bookingStatus->id,
        'date' => '2026-09-02', 'start_time' => '09:00', 'end_time' => '10:00',
        'title' => $title, 'participants_count' => 2,
    ]);
}

function historyOwner(string $username): User
{
    return User::create([
        'name' => 'History Owner', 'username' => $username, 'password' => 'password',
        'role_id' => Role::where('code', 'user')->firstOrFail()->id,
    ]);
}

it('paginates only the owners bookings and loads attachments only for the current page', function () {
    $owner = historyOwner('history-owner');
    $other = historyOwner('other-owner');
    historyBooking($other, 'FINISHED', 'Other users meeting');
    $bookings = collect(range(1, 21))->map(function (int $index) use ($owner) {
        $booking = historyBooking($owner, 'FINISHED', "Meeting {$index}");
        $booking->attachments()->create([
            'original_filename' => 'photo.png', 'filename' => 'photo.png',
            'path' => "booking-results/{$booking->id}/photo.png", 'mime_type' => 'image/png',
            'size' => 10, 'type' => 'documentation', 'uploaded_by' => $owner->id,
        ]);

        return $booking;
    });
    $loadedAttachments = 0;
    BookingAttachment::retrieved(function () use (&$loadedAttachments): void {
        $loadedAttachments++;
    });

    $this->actingAs($owner)->get('/riwayat?page=2')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('User/History')
        ->has('histories.data', 10)
        ->where('histories.total', 21)
        ->where('histories.current_page', 2)
        ->where('histories.last_page', 3)
        ->where('histories.data.0.id', $bookings[10]->id)
        ->where('histories.data.0.room_id', $bookings[10]->room_id)
        ->has('histories.data.0.documentations', 1)
        ->where('histories.data.0.meeting_minutes', null));

    expect($loadedAttachments)->toBe(10);
});

it('filters the entire history before pagination and retains the filter in page links', function () {
    $owner = historyOwner('filtered-owner');
    historyBooking($owner, 'PENDING', 'Hidden pending meeting');

    foreach (range(1, 12) as $index) {
        historyBooking($owner, 'FINISHED', "Finished {$index}");
    }

    $this->actingAs($owner)->get('/riwayat?status=Finished&page=2')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('histories.data', 2)
        ->where('histories.total', 12)
        ->where('histories.data.0.status', 'Finished')
        ->where('filters.status', 'Finished')
        ->where('histories.prev_page_url', fn ($url) => str_contains($url, 'status=Finished')));

    $this->get('/riwayat')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('histories.total', 13)->where('histories.current_page', 1)->where('filters.status', ''));
});

it('validates history filters and page numbers', function (array $query, string $field) {
    $owner = historyOwner('invalid-history-owner');
    $this->actingAs($owner)->getJson('/riwayat?'.http_build_query($query))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'unknown status' => [['status' => 'Unknown'], 'status'],
    'status array' => [['status' => ['Finished']], 'status'],
    'negative page' => [['page' => -1], 'page'],
]);
