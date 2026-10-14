<?php

use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('compiles all migrations and rollbacks for supported database drivers', function (string $driver, string $connectionClass) {
    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)
        ->andReturn(match ($driver) {
            'mariadb' => '10.11.0-MariaDB',
            'sqlite' => '3.45.0',
            default => '8.0.0',
        });
    $connection = new $connectionClass($pdo, 'migration_test', '', [
        'driver' => $driver,
        'name' => 'migration_test',
    ]);
    $schema = Schema::getFacadeRoot();
    Schema::swap($connection->getSchemaBuilder());

    try {
        $files = glob(database_path('migrations/*.php'));
        sort($files);

        $queries = $connection->pretend(function () use ($files) {
            foreach ($files as $file) {
                (require $file)->up();
            }

            foreach (array_reverse($files) as $file) {
                (require $file)->down();
            }
        });

        $sql = implode("\n", array_column($queries, 'query'));

        expect($sql)->toContain('create table', 'users', 'bookings', 'drop table')
            ->not->toContain('active_username');

        if ($driver === 'sqlsrv') {
            expect($sql)->not->toContain('on delete restrict')
                ->toContain('foreign key ("processed_by") references "users" ("id") on delete no action');
        }
    } finally {
        Schema::swap($schema);
    }
})->with([
    'mysql' => ['mysql', MySqlConnection::class],
    'mariadb' => ['mariadb', MariaDbConnection::class],
    'sqlite' => ['sqlite', SQLiteConnection::class],
    'pgsql' => ['pgsql', PostgresConnection::class],
    'sqlsrv' => ['sqlsrv', SqlServerConnection::class],
]);
