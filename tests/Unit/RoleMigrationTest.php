<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['database.connections.role_migration_test' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
    ]]);
    $this->connection = DB::connection('role_migration_test');
    $this->originalSchema = Schema::getFacadeRoot();
    Schema::swap($this->connection->getSchemaBuilder());

    (require database_path('migrations/2026_07_22_063906_create_users_table.php'))->up();
    (require database_path('migrations/2026_10_14_000000_add_active_username_to_users_table.php'))->up();
    $this->migration = require database_path('migrations/2026_10_22_081653_replace_user_role_with_role_id.php');
});

afterEach(function () {
    Schema::swap($this->originalSchema);
    DB::purge('role_migration_test');
});

it('preserves existing users and promotes only the active admin account during upgrade and rollback', function () {
    foreach ([
        [1, 'admin', 'admin', null],
        [2, 'other-admin', 'admin', null],
        [3, 'borrower', 'user', null],
        [4, 'screen', 'display', null],
        [5, 'admin', 'admin', '2026-10-01 00:00:00'],
    ] as [$id, $username, $role, $deletedAt]) {
        $this->connection->table('users')->insert([
            'id' => $id, 'username' => $username, 'role' => $role,
            'name' => 'Existing user '.$id, 'password' => 'existing-password-hash',
            'remember_token' => 'existing-session-token', 'deleted_at' => $deletedAt,
        ]);
    }
    $before = $this->connection->table('users')->orderBy('id')->get()->map(fn ($user) => (array) $user);

    $this->migration->up();

    expect(Schema::hasColumn('users', 'role'))->toBeFalse();
    $roles = $this->connection->table('users')->join('roles', 'roles.id', '=', 'users.role_id')
        ->orderBy('users.id')->pluck('roles.code')->all();
    expect($roles)->toBe(['super_admin', 'admin', 'user', 'display', 'admin']);

    $after = $this->connection->table('users')->orderBy('id')->get()->map(fn ($user) => (array) $user);
    expect($after->map(fn ($user) => collect($user)->except('role_id')->all())->all())
        ->toBe($before->map(fn ($user) => collect($user)->except('role')->all())->all());

    $this->migration->down();

    expect(Schema::hasColumn('users', 'role_id'))->toBeFalse()
        ->and(Schema::hasTable('roles'))->toBeFalse()
        ->and($this->connection->table('users')->orderBy('id')->get()->map(fn ($user) => (array) $user)->all())
        ->toEqual($before->all());
});

it('stops before changing the schema when an unmapped legacy role exists', function () {
    $this->connection->table('users')->insert([
        'username' => 'custom', 'name' => 'Custom', 'password' => 'hash', 'role' => 'custom-role',
    ]);

    expect(fn () => $this->migration->up())->toThrow(RuntimeException::class, 'Unknown user roles')
        ->and(Schema::hasTable('roles'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'role'))->toBeTrue()
        ->and($this->connection->table('users')->value('role'))->toBe('custom-role');
});

it('does not promote a non-admin account that happens to use the admin username', function () {
    $this->connection->table('users')->insert([
        'username' => 'admin', 'name' => 'Regular user', 'password' => 'hash', 'role' => 'user',
    ]);

    $this->migration->up();

    expect($this->connection->table('users')->join('roles', 'roles.id', '=', 'users.role_id')->value('roles.code'))
        ->toBe('user');
});
