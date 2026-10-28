<?php

use App\Enums\BookingAttachmentType;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingAttachmentService;
use App\Services\BookingResultService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->owner = User::create([
        'name' => 'Owner', 'username' => 'result-owner', 'password' => 'password',
        'role_id' => Role::where('code', 'user')->firstOrFail()->id,
    ]);
    $room = Room::create(['name' => 'Meeting room', 'location' => 'Office', 'capacity' => 10, 'is_available' => true]);
    $status = BookingStatus::create(['code' => 'FINISHED', 'label' => 'Finished']);
    $this->booking = Booking::create([
        'room_id' => $room->id, 'user_id' => $this->owner->id, 'status_id' => $status->id,
        'title' => 'Private meeting', 'date' => '2026-09-01', 'start_time' => '09:00',
        'end_time' => '10:00', 'participants_count' => 2,
    ]);
});

it('serves meeting results only to the owner and administrators', function (string $role, int $status, string $type) {
    $actor = $role === 'owner' ? $this->owner : User::create([
        'name' => 'Visitor', 'username' => 'result-visitor', 'password' => 'password',
        'role_id' => Role::where('code', $role)->firstOrFail()->id,
    ]);
    $path = 'booking-results/private.pdf';
    Storage::disk('local')->put($path, 'private content');
    $attachment = $this->booking->attachments()->create([
        'original_filename' => 'meeting.pdf', 'filename' => 'private.pdf', 'path' => $path,
        'type' => $type, 'mime_type' => 'application/pdf', 'size' => 15, 'uploaded_by' => $this->owner->id,
    ]);
    $route = $actor->isAdmin() ? 'admin.bookings.results.show' : 'booking.results.show';
    $response = $this->actingAs($actor)->get(route($route, [$this->booking, $attachment]));
    $response->assertStatus($status);

    if ($status === 200) {
        $response->assertStreamedContent('private content')
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
    }

    Storage::disk('public')->assertMissing($path);
})->with([
    'owner' => ['owner', 200],
    'other user' => ['user', 403],
    'admin' => ['admin', 200],
    'super admin' => ['super_admin', 200],
    'display' => ['display', 403],
])->with(['documentation', 'meeting_minutes']);

it('rejects unauthenticated access and attachments outside the requested booking', function (string $case) {
    $attachment = $this->booking->attachments()->create([
        'original_filename' => 'meeting.pdf', 'filename' => 'private.pdf', 'path' => 'booking-results/private.pdf',
        'type' => BookingAttachmentType::MeetingMinutes, 'mime_type' => 'application/pdf',
        'size' => 15, 'uploaded_by' => $this->owner->id,
    ]);

    if ($case === 'guest') {
        $this->get(route('booking.results.show', [$this->booking, $attachment]))->assertRedirect(route('login'));

        return;
    }

    if ($case === 'another booking') {
        $other = $this->booking->replicate();
        $other->save();
        $attachment->update(['booking_id' => $other->id]);
    } elseif ($case === 'banner') {
        $attachment->update(['type' => BookingAttachmentType::Banner]);
    } elseif ($case === 'deleted') {
        $attachment->delete();
    }

    $this->actingAs($this->owner)->get(route('booking.results.show', [$this->booking, $attachment]))->assertNotFound();
})->with(['guest', 'another booking', 'banner', 'deleted', 'missing file']);

it('moves old public results including orphan files while preserving banners and contents', function (bool $alreadyCopied) {
    Storage::disk('public')->put('booking-results/legacy.pdf', 'legacy contents');
    Storage::disk('public')->put('banners/banner.png', 'public banner');

    if ($alreadyCopied) {
        Storage::disk('local')->put('booking-results/legacy.pdf', 'legacy contents');
    }

    $this->artisan('booking-results:privatize')->assertSuccessful();
    Storage::disk('public')->assertMissing('booking-results/legacy.pdf');
    Storage::disk('public')->assertExists('banners/banner.png');
    expect(Storage::disk('local')->get('booking-results/legacy.pdf'))->toBe('legacy contents');
    $this->artisan('booking-results:privatize')->assertSuccessful();
})->with([false, true]);

it('does not remove or overwrite conflicting copies when moving old results', function () {
    Storage::disk('public')->put('booking-results/legacy.pdf', 'original contents');
    Storage::disk('local')->put('booking-results/legacy.pdf', 'different contents');

    expect(fn () => $this->artisan('booking-results:privatize')->run())->toThrow(RuntimeException::class);
    expect(Storage::disk('public')->get('booking-results/legacy.pdf'))->toBe('original contents');
    expect(Storage::disk('local')->get('booking-results/legacy.pdf'))->toBe('different contents');
});

it('reports failed file cleanup while keeping deleted metadata for tracing', function () {
    $attachment = $this->booking->attachments()->create([
        'original_filename' => 'meeting.pdf', 'filename' => 'private.pdf', 'path' => 'booking-results/private.pdf',
        'type' => BookingAttachmentType::MeetingMinutes, 'mime_type' => 'application/pdf',
        'size' => 15, 'uploaded_by' => $this->owner->id,
    ]);
    Exceptions::fake();
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('delete')->with($attachment->path)->once()->andReturn(false);
    Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

    $this->actingAs($this->owner)->delete(route('booking.results.destroy', [$this->booking, $attachment]))->assertRedirect();
    $this->assertSoftDeleted($attachment);
    Exceptions::assertReported(RuntimeException::class);
});

it('replaces the current minutes even when both requests loaded an older relation', function () {
    $first = $this->booking->fresh()->load('meetingMinutes');
    $second = $this->booking->fresh()->load('meetingMinutes');
    $service = app(BookingResultService::class);
    $service->replaceMeetingMinutes($first, $this->owner, UploadedFile::fake()->create('first.pdf', 10, 'application/pdf'));
    $previous = $this->booking->fresh()->meetingMinutes;
    $service->replaceMeetingMinutes($second, $this->owner, UploadedFile::fake()->create('second.pdf', 10, 'application/pdf'));

    expect($this->booking->attachments()->where('type', 'meeting_minutes')->count())->toBe(1);
    $current = $this->booking->fresh()->meetingMinutes;
    expect($current->original_filename)->toBe('second.pdf');
    $this->assertSoftDeleted($previous);
    Storage::disk('local')->assertMissing($previous->path);
    Storage::disk('local')->assertExists($current->path);
});

it('removes the uploaded file when attachment metadata cannot be saved', function () {
    expect(fn () => app(BookingAttachmentService::class)->store(
        $this->booking,
        $this->owner->id + 1000,
        UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf'),
        BookingAttachmentType::MeetingMinutes,
    ))->toThrow(QueryException::class);

    expect(Storage::disk('local')->allFiles())->toBe([]);
    expect($this->booking->attachments()->count())->toBe(0);
});

it('preserves the previous minutes and removes the replacement when the transaction fails', function () {
    $service = app(BookingResultService::class);
    $service->replaceMeetingMinutes($this->booking, $this->owner, UploadedFile::fake()->create('original.pdf', 10, 'application/pdf'));
    $previous = $this->booking->fresh()->meetingMinutes;
    BookingAttachment::deleting(function () {
        throw new RuntimeException('Metadata delete failed');
    });

    try {
        expect(fn () => $service->replaceMeetingMinutes(
            $this->booking, $this->owner, UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
        ))->toThrow(RuntimeException::class, 'Metadata delete failed');
    } finally {
        Event::forget('eloquent.deleting: '.BookingAttachment::class);
    }

    $this->assertNotSoftDeleted($previous);
    expect($this->booking->attachments()->count())->toBe(1);
    expect(Storage::disk('local')->allFiles())->toBe([$previous->path]);
});

it('rolls back the entire documentation batch when a later upload fails', function () {
    $invalid = new UploadedFile('/missing-upload', 'missing.pdf', null, UPLOAD_ERR_NO_FILE, true);
    expect(fn () => app(BookingResultService::class)->uploadDocumentation($this->booking, $this->owner, [
        UploadedFile::fake()->create('first.pdf', 10, 'application/pdf'), $invalid,
    ]))->toThrow(ValidationException::class);
    expect($this->booking->attachments()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('keeps the file if deleting its metadata fails', function () {
    $service = app(BookingResultService::class);
    $service->uploadDocumentation($this->booking, $this->owner, [UploadedFile::fake()->create('original.pdf', 10, 'application/pdf')]);
    $attachment = $this->booking->attachments()->firstOrFail();
    BookingAttachment::deleting(fn () => throw new RuntimeException('Delete failed'));

    try {
        expect(fn () => $service->delete($this->booking, $attachment, $this->owner))->toThrow(RuntimeException::class, 'Delete failed');
    } finally {
        Event::forget('eloquent.deleting: '.BookingAttachment::class);
    }
    $this->assertNotSoftDeleted($attachment);
    Storage::disk('local')->assertExists($attachment->path);
});

it('cleans partial writes when storage fails without saving metadata', function () {
    $file = UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf');
    $path = "booking-results/{$this->booking->id}/meeting_minutes/{$file->hashName()}";
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('writeStream')->once()->andReturn(false);
    $disk->shouldReceive('delete')->with($path)->once()->andReturn(true);
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);

    expect(fn () => app(BookingAttachmentService::class)->store($this->booking, $this->owner->id, $file, BookingAttachmentType::MeetingMinutes))
        ->toThrow(RuntimeException::class, 'Gagal menyimpan file hasil rapat.');
    expect($this->booking->attachments()->count())->toBe(0);
});
