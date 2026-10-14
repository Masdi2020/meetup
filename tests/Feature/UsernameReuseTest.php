<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

function usernameReuseUser(string $username): User
{
    return User::create([
        'name' => 'Username test',
        'username' => $username,
        'password' => 'password',
        'role' => 'user',
    ]);
}

it('allows repeated reuse after soft deletion while preserving old usernames', function () {
    $first = usernameReuseUser('reusable');
    expect($first->fresh()->getAttribute('active_username'))->toBe('reusable');
    $first->delete();

    $second = usernameReuseUser('reusable');
    $second->delete();
    $active = usernameReuseUser('reusable');

    expect(User::withTrashed()->where('username', 'reusable')->count())->toBe(3)
        ->and($first->fresh()->getAttribute('active_username'))->toBeNull()
        ->and($second->fresh()->getAttribute('active_username'))->toBeNull()
        ->and($active->fresh()->getAttribute('active_username'))->toBe('reusable')
        ->and($active->fresh()->toArray())->not->toHaveKey('active_username');
});

it('rejects duplicate usernames for active users at the database level', function () {
    usernameReuseUser('duplicate');

    expect(fn () => usernameReuseUser('duplicate'))->toThrow(QueryException::class);
});

it('updates the generated username when an active user is renamed', function () {
    $user = usernameReuseUser('old-name');
    $user->update(['username' => 'new-name']);

    expect($user->fresh()->getAttribute('active_username'))->toBe('new-name');
    usernameReuseUser('old-name');

    expect(fn () => usernameReuseUser('new-name'))->toThrow(QueryException::class);
});

it('rejects restoring a deleted user whose username has been reused', function () {
    $deleted = usernameReuseUser('restored');
    $deleted->delete();
    usernameReuseUser('restored');

    expect(fn () => $deleted->restore())->toThrow(QueryException::class);
});

it('rolls back and reapplies the username migration without losing users', function () {
    $deleted = usernameReuseUser('historical');
    $deleted->delete();
    $migration = require database_path('migrations/2026_10_14_000000_add_active_username_to_users_table.php');

    $migration->down();

    expect(Schema::hasColumn('users', 'active_username'))->toBeFalse()
        ->and($deleted->fresh()->username)->toBe('historical');

    $migration->up();
    usernameReuseUser('historical');

    expect(User::withTrashed()->where('username', 'historical')->count())->toBe(2);
});
