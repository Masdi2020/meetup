<?php

use App\Enums\BookingAttachmentType;
use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingWorkflowService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('deletes meeting results only when the attachment is eligible', function (string $role, BookingAttachmentType $type, bool $differentBooking, int $expectedStatus) {
    $this->withoutMiddleware(PreventRequestForgery::class);
    Storage::fake('public');
    Storage::fake('local');
    $owner = bookingUser('owner');
    $actor = $role === 'user' ? $owner : bookingUser('admin', $role);
    $booking = bookingRecord($owner, bookingRoom(), 'FINISHED');
    $attachmentBooking = $differentBooking
        ? bookingRecord($owner, $booking->room, 'FINISHED')
        : $booking;
    $path = 'booking-results/result.pdf';
    Storage::disk('local')->put($path, 'result content');
    $attachment = $attachmentBooking->attachments()->create([
        'original_filename' => 'result.pdf',
        'filename' => 'result.pdf',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 14,
        'type' => $type,
        'uploaded_by' => $owner->id,
    ]);
    $baseUrl = $actor->isAdmin() ? '/admin/bookings' : '/booking';

    $this->actingAs($actor)
        ->delete("{$baseUrl}/{$booking->id}/attachments/{$attachment->id}")
        ->assertStatus($expectedStatus);

    if ($expectedStatus === 302) {
        $this->assertSoftDeleted($attachment);
        Storage::disk('local')->assertMissing($path);
    } else {
        $this->assertNotSoftDeleted($attachment);
        Storage::disk('local')->assertExists($path);
    }
})->with([
    'owner documentation' => ['user', BookingAttachmentType::Documentation, false, 302],
    'owner minutes' => ['user', BookingAttachmentType::MeetingMinutes, false, 302],
    'admin documentation' => ['admin', BookingAttachmentType::Documentation, false, 302],
    'admin minutes' => ['admin', BookingAttachmentType::MeetingMinutes, false, 302],
    'super admin documentation' => ['super_admin', BookingAttachmentType::Documentation, false, 302],
    'super admin minutes' => ['super_admin', BookingAttachmentType::MeetingMinutes, false, 302],
    'super admin another booking' => ['super_admin', BookingAttachmentType::Documentation, true, 404],
    'another booking' => ['user', BookingAttachmentType::Documentation, true, 404],
    'banner' => ['admin', BookingAttachmentType::Banner, false, 404],
]);

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
        'role_id' => Role::where('code', $role)->firstOrFail()->id,
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

it('returns only active intervals for the requested room and date', function () {
    $user = bookingUser();
    $room = bookingRoom();
    bookingRecord($user, $room, 'APPROVED', ['start_time' => '11:00', 'end_time' => '12:00']);
    bookingRecord($user, $room, 'PENDING');

    foreach (['REJECTED', 'CANCELLED', 'FINISHED'] as $status) {
        bookingRecord($user, $room, $status);
    }

    bookingRecord($user, $room, 'APPROVED')->delete();
    bookingRecord($user, $room, 'APPROVED', ['date' => '2026-09-03']);
    bookingRecord($user, bookingRoom(), 'APPROVED');

    $this->actingAs($user)
        ->getJson(route('booking.availability', ['room_id' => $room->id, 'date' => '2026-09-02']))
        ->assertOk()
        ->assertExactJson(['booked_intervals' => [
            ['start_time' => '09:00', 'end_time' => '10:00'],
            ['start_time' => '11:00', 'end_time' => '12:00'],
        ]]);
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

it('uses the same overlap boundaries when creating and updating bookings', function (bool $editing, string $start, string $end, string $existingStatus, bool $conflict) {
    $user = bookingUser();
    $room = bookingRoom();
    bookingRecord($user, $room, $existingStatus);
    $booking = $editing ? bookingRecord($user, $room, 'APPROVED', ['start_time' => '13:00', 'end_time' => '14:00']) : null;
    $data = [
        'room_id' => $room->id, 'date' => '2026-09-02',
        'start_time' => $start, 'end_time' => $end,
        'title' => 'Boundary booking', 'participants' => 3,
    ];

    $this->actingAs($user);
    $response = $booking
        ? $this->put(route('booking.update', $booking), $data)
        : $this->post(route('booking.store'), $data);

    if ($conflict) {
        $response->assertSessionHasErrors($editing ? 'date' : 'room_id');
        $this->assertDatabaseMissing('bookings', ['title' => 'Boundary booking']);
    } else {
        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['title' => 'Boundary booking']);
    }
})->with([false, true])->with([
    'pending overlap' => ['09:15', '10:15', 'PENDING', true],
    'approved overlap' => ['08:45', '09:15', 'APPROVED', true],
    'ends at next start' => ['08:00', '09:00', 'APPROVED', false],
    'starts at previous end' => ['10:00', '11:00', 'APPROVED', false],
    'same booking time during edit' => ['13:00', '14:00', 'APPROVED', false],
    'cancelled overlap' => ['09:00', '10:00', 'CANCELLED', false],
]);

it('rejects invalid time intervals in both booking forms', function (bool $editing, string $start, string $end, string $field) {
    $user = bookingUser();
    $room = bookingRoom();
    $booking = $editing ? bookingRecord($user, $room, 'APPROVED') : null;
    $data = [
        'room_id' => $room->id, 'date' => '2026-09-03', 'start_time' => $start,
        'end_time' => $end, 'title' => 'Invalid time', 'participants' => 3,
    ];

    $this->actingAs($user);
    $response = $booking
        ? $this->put(route('booking.update', $booking), $data)
        : $this->post(route('booking.store'), $data);

    $response->assertSessionHasErrors($field);
    $this->assertDatabaseMissing('bookings', ['title' => 'Invalid time']);
})->with([false, true])->with([
    ['09:10', '10:00', 'start_time'],
    ['09:00', '10:10', 'end_time'],
    ['10:00', '09:00', 'end_time'],
]);

it('validates new passwords consistently for normal and forced changes', function (bool $forced, string $password, string $confirmation, bool $valid) {
    $user = bookingUser();
    $user->update(['force_change_password' => $forced]);
    $originalPassword = $user->password;

    $response = $this->actingAs($user)->put(route($forced ? 'profile.forced-password' : 'profile.password'), [
        'current_password' => 'password', 'password' => $password,
        'password_confirmation' => $confirmation,
    ]);

    if ($valid) {
        $response->assertRedirect()->assertSessionHasNoErrors();
        expect(Hash::check($password, $user->refresh()->password))->toBeTrue();
        expect($user->force_change_password)->toBeFalse();
    } else {
        $response->assertSessionHasErrors('password');
        expect($user->refresh()->password)->toBe($originalPassword);
    }
})->with([false, true])->with([
    ['new-password', 'new-password', true],
    ['short', 'short', false],
    ['new password', 'new password', false],
    ['new-password', 'different-password', false],
]);

it('stores banner and documentation metadata through the shared attachment storage', function (bool $banner) {
    Storage::fake('public');
    Storage::fake('local');
    $user = bookingUser();
    $room = bookingRoom();
    $room->update(['has_display' => true]);
    $file = UploadedFile::fake()->createWithContent('meeting.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a7xoAAAAASUVORK5CYII='));
    $this->actingAs($user);

    if ($banner) {
        $this->post(route('booking.store'), [
            'room_id' => $room->id, 'date' => '2026-09-02', 'start_time' => '09:00',
            'end_time' => '10:00', 'title' => 'Banner booking', 'participants' => 3, 'banner' => $file,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $booking = Booking::where('title', 'Banner booking')->firstOrFail();
    } else {
        $booking = bookingRecord($user, $room, 'FINISHED');
        $this->post(route('booking.results.documentation', $booking), ['documentation' => [$file]])
            ->assertRedirect()->assertSessionHasNoErrors();
    }

    $attachment = $booking->attachments()->sole();
    expect($attachment->type)->toBe($banner ? BookingAttachmentType::Banner : BookingAttachmentType::Documentation);
    expect($attachment->original_filename)->toBe('meeting.png');
    expect($attachment->uploaded_by)->toBe($user->id);
    Storage::disk($banner ? 'public' : 'local')->assertExists($attachment->path);
    Storage::disk($banner ? 'local' : 'public')->assertMissing($attachment->path);

    if ($banner) {
        $this->assertDatabaseHas('audits', ['entity_type' => 'BookingAttachment', 'entity_id' => $attachment->id, 'action' => 'created']);
    }
})->with([false, true]);

it('keeps the previous minutes when storing the replacement fails', function () {
    $disk = Storage::fake('local');
    $user = bookingUser();
    $booking = bookingRecord($user, bookingRoom(), 'FINISHED');
    $path = 'booking-results/previous.pdf';
    $disk->put($path, 'previous minutes');
    $previous = $booking->attachments()->create([
        'filename' => 'previous.pdf', 'original_filename' => 'previous.pdf', 'path' => $path,
        'mime_type' => 'application/pdf', 'size' => 16, 'type' => BookingAttachmentType::MeetingMinutes,
        'uploaded_by' => $user->id,
    ]);
    $failedDisk = Mockery::mock(Filesystem::class);
    $failedDisk->shouldReceive('writeStream')->once()->andReturn(false);
    Storage::shouldReceive('disk')->with('local')->once()->andReturn($failedDisk);

    $this->actingAs($user)->post(route('booking.results.meeting-minutes', $booking), [
        'meeting_minutes' => UploadedFile::fake()->create('new.pdf', 1, 'application/pdf'),
    ])->assertStatus(500);

    $this->assertNotSoftDeleted($previous);
    $disk->assertExists($path);
    expect($booking->attachments()->count())->toBe(1);
});

it('replaces minutes after the new file is stored', function () {
    Storage::fake('public');
    Storage::fake('local');
    $user = bookingUser();
    $booking = bookingRecord($user, bookingRoom(), 'FINISHED');
    $path = 'booking-results/previous.pdf';
    Storage::disk('local')->put($path, 'previous minutes');
    $previous = $booking->attachments()->create([
        'filename' => 'previous.pdf', 'original_filename' => 'previous.pdf', 'path' => $path,
        'mime_type' => 'application/pdf', 'size' => 16, 'type' => BookingAttachmentType::MeetingMinutes,
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)->post(route('booking.results.meeting-minutes', $booking), [
        'meeting_minutes' => UploadedFile::fake()->create('new.pdf', 1, 'application/pdf'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertSoftDeleted($previous);
    Storage::disk('local')->assertMissing($path);
    $current = $booking->attachments()->sole();
    expect($current->original_filename)->toBe('new.pdf');
    Storage::disk('local')->assertExists($current->path);
});

it('rechecks the stored status before applying a stale edit or transition', function (bool $editing, bool $deleted) {
    $user = bookingUser();
    $booking = bookingRecord($user, bookingRoom(), 'APPROVED');
    $booking->load('status');

    if ($deleted) {
        Booking::findOrFail($booking->id)->delete();
    } else {
        Booking::whereKey($booking->id)->update(['status_id' => bookingStatus('CANCELLED')->id]);
    }

    $service = app(BookingWorkflowService::class);
    $action = fn () => $editing
        ? $service->update($booking, ['title' => 'Stale edit', 'date' => '2026-09-03', 'start_time' => '09:00', 'end_time' => '10:00'], $user->id)
        : $service->changeStatus($booking, 'FINISHED', $user->id, 'Stale transition');

    expect($action)->toThrow(ValidationException::class);
    expect(Booking::withTrashed()->findOrFail($booking->id)->title)->toBe('Rapat awal');
})->with([false, true])->with([false, true]);

it('does not approve a legacy pending booking that overlaps another active booking', function () {
    $admin = bookingUser('admin', 'admin');
    $room = bookingRoom();
    $booking = bookingRecord($admin, $room, 'PENDING');
    bookingRecord($admin, $room, 'APPROVED');

    $this->actingAs($admin)->patch(route('admin.bookings.approve', $booking))
        ->assertSessionHasErrors('booking');

    expect($booking->refresh()->status->code)->toBe('PENDING');
});

it('does not finish a booking rescheduled after the scheduler selected it', function () {
    $user = bookingUser();
    $booking = bookingRecord($user, bookingRoom(), 'APPROVED', ['date' => '2026-08-31']);
    $rescheduled = false;
    Booking::retrieved(function (Booking $selected) use ($booking, &$rescheduled): void {
        if (! $rescheduled && $selected->id === $booking->id && ! array_key_exists('date', $selected->getAttributes())) {
            $rescheduled = true;
            DB::table('bookings')->where('id', $booking->id)->update(['date' => '2026-09-03']);
        }
    });

    $this->artisan('bookings:finish-expired')->expectsOutput('0 booking diubah menjadi finished.')->assertSuccessful();
    expect($rescheduled)->toBeTrue();
    expect($booking->refresh()->status->code)->toBe('APPROVED');
});

it('excludes only an owned booking from user availability checks', function (bool $ownBooking) {
    $owner = bookingUser('owner');
    $actor = $ownBooking ? $owner : bookingUser('visitor');
    $room = bookingRoom();
    $booking = bookingRecord($owner, $room, 'APPROVED');

    $this->actingAs($actor)->getJson(route('booking.availability', [
        'room_id' => $room->id,
        'date' => $booking->date->format('Y-m-d'),
        'ignore_booking_id' => $booking->id,
    ]))->assertOk()->assertJsonCount($ownBooking ? 0 : 1, 'booked_intervals');
})->with([true, false]);
