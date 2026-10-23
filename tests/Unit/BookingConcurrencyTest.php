<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
