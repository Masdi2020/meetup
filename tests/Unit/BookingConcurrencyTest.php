<?php

use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

it('allows only one overlapping booking across separate database connections', function (bool $firstEdits, bool $secondEdits) {
    $database = tempnam(sys_get_temp_dir(), 'booking-concurrency-');
    $defaultConnection = DB::getDefaultConnection();
    config(['database.connections.concurrency' => [
        ...config('database.connections.sqlite'),
        'database' => $database, 'url' => null, 'busy_timeout' => 10000,
    ]]);
    DB::setDefaultConnection('concurrency');
    $workers = [];

    try {
        $this->artisan('migrate', ['--database' => 'concurrency', '--no-interaction' => true])->assertSuccessful();
        $user = User::create([
            'name' => 'Borrower', 'username' => 'concurrent-user', 'password' => 'password',
            'role_id' => Role::where('code', 'user')->firstOrFail()->id,
        ]);
        $room = Room::create(['name' => 'Concurrent room', 'location' => 'Office', 'capacity' => 10, 'is_available' => true]);
        BookingStatus::create(['code' => 'PENDING', 'label' => 'Pending']);
        $status = BookingStatus::create(['code' => 'APPROVED', 'label' => 'Approved']);
        $code = <<<'WORKER'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => $argv[1],
    'database.connections.sqlite.url' => null, 'database.connections.sqlite.busy_timeout' => 10000,
    'broadcasting.default' => 'null', 'cache.default' => 'array',
]);
Illuminate\Support\Facades\DB::purge('sqlite');
$service = $app->make(App\Services\BookingWorkflowService::class);
$booking = $argv[4] ? App\Models\Booking::findOrFail($argv[4]) : null;
echo "READY\n";
fflush(STDOUT);
fgets(STDIN);
try {
    $data = ['room_id' => (int) $argv[2], 'date' => '2027-01-10', 'start_time' => '09:00',
        'end_time' => '10:00', 'title' => 'Concurrent meeting', 'participants' => 2];
    if ($booking) {
        $service->update($booking, array_intersect_key($data, array_flip(['date', 'start_time', 'end_time', 'title'])), (int) $argv[3]);
    } else {
        $service->create($data, (int) $argv[3], false);
    }
    echo "SAVED\n";
} catch (Illuminate\Validation\ValidationException $exception) {
    echo "CONFLICT\n";
}
WORKER;

        foreach ([$firstEdits, $secondEdits] as $index => $editing) {
            $booking = $editing ? Booking::create([
                'room_id' => $room->id, 'user_id' => $user->id, 'status_id' => $status->id,
                'title' => 'Original meeting', 'date' => '2027-01-10',
                'start_time' => ($index + 13).':00', 'end_time' => ($index + 14).':00', 'participants_count' => 2,
            ]) : null;
            $input = new InputStream;
            $worker = new Process([PHP_BINARY, '-r', $code, $database, (string) $room->id, (string) $user->id, (string) ($booking?->id ?? '')], base_path(), ['APP_ENV' => 'testing'], $input, 30);
            $worker->start();
            $workers[] = [$worker, $input];
            expect($worker->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'READY')))->toBeTrue();
        }

        foreach ($workers as [$worker, $input]) {
            $input->write("go\n");
            $input->close();
        }

        $outputs = [];

        foreach ($workers as [$worker]) {
            $worker->wait();
            expect($worker->isSuccessful())->toBeTrue($worker->getErrorOutput());
            $outputs[] = trim(str_replace('READY', '', $worker->getOutput()));
        }

        sort($outputs);
        expect($outputs)->toBe(['CONFLICT', 'SAVED']);
        expect(Booking::where('title', 'Concurrent meeting')->count())->toBe(1);
    } finally {
        foreach ($workers as [$worker]) {
            $worker->stop();
        }

        DB::purge('concurrency');
        DB::setDefaultConnection($defaultConnection);
        unlink($database);
    }
})->with([
    'two creations' => [false, false],
    'creation and edit' => [false, true],
    'two edits' => [true, true],
]);

it('serializes concurrent result replacements and room toggles', function (string $operation) {
    $database = tempnam(sys_get_temp_dir(), 'result-concurrency-');
    $storage = $database.'-files';
    $defaultConnection = DB::getDefaultConnection();
    config(['database.connections.concurrency' => [
        ...config('database.connections.sqlite'),
        'database' => $database, 'url' => null, 'busy_timeout' => 10000,
    ]]);
    DB::setDefaultConnection('concurrency');
    $workers = [];

    try {
        $this->artisan('migrate', ['--database' => 'concurrency', '--no-interaction' => true])->assertSuccessful();
        $user = User::create([
            'name' => 'Owner', 'username' => 'owner', 'password' => 'password',
            'role_id' => Role::where('code', 'admin')->firstOrFail()->id,
        ]);
        $room = Room::create(['name' => 'Room', 'location' => 'Office', 'capacity' => 10, 'is_available' => true]);
        $status = BookingStatus::create(['code' => 'FINISHED', 'label' => 'Finished']);
        $booking = Booking::create([
            'room_id' => $room->id, 'user_id' => $user->id, 'status_id' => $status->id,
            'title' => 'Meeting', 'date' => '2027-01-10', 'start_time' => '09:00',
            'end_time' => '10:00', 'participants_count' => 2,
        ]);
        $code = <<<'WORKER'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => $argv[1],
    'database.connections.sqlite.url' => null, 'database.connections.sqlite.busy_timeout' => 10000,
    'filesystems.disks.local.root' => $argv[2], 'cache.default' => 'array',
]);
Illuminate\Support\Facades\DB::purge('sqlite');
$booking = App\Models\Booking::with('meetingMinutes')->findOrFail($argv[3]);
$room = $booking->room;
$user = $booking->user;
echo "READY\n";
fflush(STDOUT);
fgets(STDIN);
if ($argv[4] === 'minutes') {
    $file = Illuminate\Http\UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf');
    $app->make(App\Services\BookingResultService::class)->replaceMeetingMinutes($booking, $user, $file);
} else {
    $app->make(App\Services\RoomService::class)->toggleAvailability($room, $user->id);
}
echo "SAVED\n";
WORKER;

        for ($index = 0; $index < 2; $index++) {
            $input = new InputStream;
            $worker = new Process([PHP_BINARY, '-r', $code, $database, $storage, (string) $booking->id, $operation], base_path(), ['APP_ENV' => 'testing'], $input, 30);
            $worker->start();
            $workers[] = [$worker, $input];
            expect($worker->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'READY')))->toBeTrue();
        }

        foreach ($workers as [$worker, $input]) {
            $input->write("go\n");
            $input->close();
        }

        foreach ($workers as [$worker]) {
            $worker->wait();
            expect($worker->isSuccessful())->toBeTrue($worker->getErrorOutput());
            expect($worker->getOutput())->toContain('SAVED');
        }

        if ($operation === 'minutes') {
            expect($booking->attachments()->where('type', 'meeting_minutes')->count())->toBe(1);
            $disk = Storage::build(['driver' => 'local', 'root' => $storage]);
            expect($disk->allFiles())->toBe([$booking->fresh()->meetingMinutes->path]);
        } else {
            expect($room->fresh()->is_available)->toBeTrue();
            $audits = Audit::where('action', 'availability_changed')->orderBy('id')->get();
            expect($audits)->toHaveCount(2);
            expect($audits[0]->new_values['is_available'])->toBeFalse()
                ->and($audits[1]->new_values['is_available'])->toBeTrue();
        }
    } finally {
        foreach ($workers as [$worker]) {
            $worker->stop();
        }

        DB::purge('concurrency');
        DB::setDefaultConnection($defaultConnection);
        unlink($database);
        File::deleteDirectory($storage);
    }
})->with(['minutes', 'toggle']);
