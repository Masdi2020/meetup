<?php

use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserService;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function securityUser(string $role = 'user', bool $forced = false): User
{
    return User::create([
        'name' => 'Security test', 'username' => $role, 'password' => 'old-password',
        'role_id' => Role::where('code', $role)->firstOrFail()->id,
        'force_change_password' => $forced,
    ]);
}

it('requires a password change before accessing pages or sending mutations', function (string $role, string $path) {
    $user = securityUser($role, true);

    $this->actingAs($user)->get($path)->assertRedirect(route('profile.edit'));
    $this->postJson('/profile', ['name' => 'Bypassed', 'username' => $role])->assertForbidden();
    $this->postJson('/broadcasting/auth', ['channel_name' => 'private-bookings', 'socket_id' => '1.1'])->assertForbidden();
    expect($user->fresh()->name)->toBe('Security test');

    $this->get('/profile')->assertOk();
    $this->from('/profile')->put('/profile/forced-password', [
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertRedirect('/profile')->assertSessionHasNoErrors();
    expect($user->fresh()->force_change_password)->toBeFalse();
    $this->get($path)->assertOk();
})->with([
    ['user', '/availability'], ['admin', '/admin'],
    ['super_admin', '/admin'], ['display', '/display'],
]);

it('allows logout while a password change is required', function () {
    $this->actingAs(securityUser(forced: true))->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('invalidates an existing session and remember token after an administrative reset', function () {
    $user = securityUser();
    $admin = securityUser('admin');
    $user->forceFill(['remember_token' => 'previous-token'])->save();
    $this->post('/login', ['username' => $user->username, 'password' => 'old-password'])->assertRedirect();

    $password = app(UserService::class)->resetPassword($user, $admin->id);
    expect($user->fresh()->remember_token)->not->toBe('previous-token');
    Auth::forgetGuards();

    $this->getJson('/profile')->assertUnauthorized();
    $this->putJson('/profile/forced-password', [
        'password' => 'attacker-password', 'password_confirmation' => 'attacker-password',
    ])->assertUnauthorized();
    expect(Hash::check($password, $user->fresh()->password))->toBeTrue();
});

it('keeps the current session but rejects other sessions after a password change', function (bool $forced) {
    $user = securityUser(forced: $forced);
    $user->forceFill(['remember_token' => 'previous-token'])->save();
    $this->post('/login', ['username' => $user->username, 'password' => 'old-password'])->assertRedirect();
    $oldSessionHash = session('password_hash_web');

    $this->from('/profile')->put($forced ? '/profile/forced-password' : '/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');
    expect($user->fresh()->remember_token)->not->toBe('previous-token');
    Auth::forgetGuards();
    $this->get('/profile')->assertOk();

    Auth::forgetGuards();
    $this->withSession(['password_hash_web' => $oldSessionHash])->getJson('/profile')->assertUnauthorized();
})->with([false, true]);

it('retains only the username when login fails', function () {
    securityUser();
    $this->post('/login', ['username' => 'user', 'password' => 'wrong-password'])
        ->assertSessionHasErrors('username')
        ->assertSessionHas('_old_input.username', 'user')
        ->assertSessionMissing('_old_input.password');
});

it('rejects the old display remember cookie after a password reset', function () {
    $display = securityUser('display');
    $admin = securityUser('admin');
    $response = $this->post('/login', ['username' => 'display', 'password' => 'old-password'])->assertRedirect();
    $cookieName = Auth::guard()->getRecallerName();
    $cookie = $response->getCookie($cookieName);
    expect($cookie)->not->toBeNull();

    app(UserService::class)->resetPassword($display, $admin->id);
    session()->flush();
    Auth::forgetGuards();

    $this->withCookie($cookieName, $cookie->getValue())->getJson('/profile')->assertUnauthorized();
});

it('renews display auto login after changing its own password', function (bool $forced) {
    securityUser('display', $forced);
    $login = $this->post('/login', ['username' => 'display', 'password' => 'old-password']);
    $cookieName = Auth::guard()->getRecallerName();
    $oldCookie = $login->getCookie($cookieName)->getValue();

    $changed = $this->put($forced ? '/profile/forced-password' : '/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertRedirect()->assertSessionHasNoErrors();
    $newCookie = $changed->getCookie($cookieName);
    expect($newCookie)->not->toBeNull();
    expect($newCookie->getValue())->not->toBe($oldCookie);

    session()->flush();
    Auth::forgetGuards();
    $this->withCookie($cookieName, $oldCookie)->getJson('/profile')->assertUnauthorized();

    session()->flush();
    Auth::forgetGuards();
    $this->withCookie($cookieName, $newCookie->getValue())->get('/display')->assertOk();
    expect(Auth::viaRemember())->toBeTrue();
})->with([false, true]);

it('protects the final active super admin from deletion or demotion', function (string $action, bool $anotherActive) {
    $admin = securityUser('super_admin');
    $other = User::create([
        'name' => 'Other admin', 'username' => 'other-admin', 'password' => 'password', 'role_id' => $admin->role_id,
    ]);
    if (! $anotherActive) {
        $other->delete();
    }

    $this->actingAs($admin);
    $response = $action === 'delete'
        ? $this->delete(route('admin.users.destroy', $admin))
        : $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'username' => $admin->username,
            'role_id' => Role::where('code', 'user')->firstOrFail()->id,
        ]);

    if ($anotherActive) {
        $response->assertRedirect()->assertSessionHasNoErrors();
        if ($action === 'delete') {
            $this->assertSoftDeleted($admin);
        } else {
            expect($admin->fresh()->role)->toBe('user');
        }
    } else {
        $response->assertSessionHasErrors('role_id');
        $this->assertNotSoftDeleted($admin);
        expect($admin->fresh()->role)->toBe('super_admin');
        $this->assertDatabaseMissing('audits', ['entity_type' => 'User', 'entity_id' => $admin->id, 'action' => $action === 'delete' ? 'deleted' : 'updated']);
    }
})->with(['delete', 'demote'])->with([false, true]);

it('allows the final super admin to edit their name without changing role', function () {
    $admin = securityUser('super_admin');
    $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => 'Updated admin', 'username' => $admin->username, 'role_id' => $admin->role_id,
    ])->assertSessionHasNoErrors();
    expect($admin->fresh()->name)->toBe('Updated admin');
});

it('allows a username accepted by admin management to be saved in the profile', function () {
    $admin = securityUser('super_admin');
    $username = str_repeat('u', 255);
    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Long username', 'username' => $username,
        'role_id' => Role::where('code', 'user')->firstOrFail()->id,
    ])->assertSessionHasNoErrors();
    $user = User::where('username', $username)->firstOrFail();
    $user->update(['force_change_password' => false]);
    session()->flush();

    $this->actingAs($user)->post('/profile', ['name' => 'Updated name', 'username' => $username])
        ->assertSessionHasNoErrors();
    expect($user->fresh()->name)->toBe('Updated name');
    $this->post('/profile', ['name' => 'Invalid', 'username' => $username.'x'])
        ->assertSessionHasErrors('username');
});

it('does not expose authentication secrets in page props', function () {
    $user = securityUser('display');
    $user->forceFill(['remember_token' => 'private-token'])->save();
    $this->actingAs($user)->get('/profile')->assertInertia(fn (Assert $page) => $page
        ->missing('auth.user.password')->missing('auth.user.remember_token')
        ->missing('user.password')->missing('user.remember_token'));
});

it('seeds all accounts with the configured password and requires replacement', function () {
    config(['auth.default_password' => 'configured-password']);
    $this->seed(UserSeeder::class);

    foreach (User::all() as $user) {
        expect(Hash::check('configured-password', $user->password))->toBeTrue();
        expect($user->force_change_password)->toBeTrue();
    }
});

it('refuses to seed accounts without an initial password', function (?string $password) {
    config(['auth.default_password' => $password]);
    expect(fn () => $this->seed(UserSeeder::class))->toThrow(RuntimeException::class, 'DEFAULT_PASSWORD');
    $this->assertDatabaseCount('users', 0);
})->with([null, '', '   ']);
