<?php

use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\Admin\UserService;
use Database\Seeders\BookingStatusSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;

function roleAccessUser(string $code, string $username = 'actor'): User
{
    return User::create([
        'name' => 'Role test', 'username' => $username, 'password' => 'password',
        'role_id' => Role::where('code', $code)->firstOrFail()->id,
    ]);
}

it('seeds the first super admin with the configured password and requires a password change', function () {
    config(['auth.default_password' => 'configured-test-password']);

    $this->seed(UserSeeder::class);

    $user = User::where('username', 'admin')->firstOrFail();
    expect($user->role)->toBe('super_admin')
        ->and($user->force_change_password)->toBeTrue()
        ->and(Hash::check('configured-test-password', $user->password))->toBeTrue();
});

it('allows administrators to access operational pages', function (string $code) {
    $user = roleAccessUser($code);

    foreach (['/admin', '/admin/bookings', '/admin/rooms', '/admin/facilities', '/admin/users'] as $url) {
        $this->actingAs($user)->get($url)->assertOk();
    }
})->with(['admin', 'super_admin']);

it('restricts audit and settings pages to super admins', function (string $code, int $status) {
    $actor = roleAccessUser($code);

    $this->actingAs($actor)->get('/admin/audits')->assertStatus($status);
    $this->get('/admin/settings')->assertStatus($status);
})->with([['admin', 403], ['super_admin', 200]]);

it('does not expose audit data through the admin dashboard', function () {
    $actor = roleAccessUser('admin');
    $this->post('/login', ['username' => $actor->username, 'password' => 'password'])->assertRedirect();

    $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('activities', null)
        ->where('stats.today_audits', null));
});

it('prevents admins from changing settings', function () {
    $this->actingAs(roleAccessUser('admin'))->put('/admin/settings', [
        'appName' => 'Unauthorized change', 'sessionTimeout' => 90,
    ])->assertForbidden();

    $this->assertDatabaseMissing('settings', ['value' => 'Unauthorized change']);
});

it('limits account management to viewing and password resets for admins', function () {
    $actor = roleAccessUser('admin');
    $target = roleAccessUser('user', 'target');
    $before = $target->fresh()->getAttributes();

    $this->actingAs($actor)->get('/admin/users')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canManageUsers', false));
    $this->post('/admin/users', [
        'name' => 'New user', 'username' => 'new-user', 'role_id' => $target->role_id,
    ])->assertForbidden();
    $this->put("/admin/users/{$target->id}", [
        'name' => 'Changed', 'username' => 'changed', 'role_id' => $actor->role_id,
    ])->assertForbidden();
    $this->delete("/admin/users/{$target->id}")->assertForbidden();

    $this->assertDatabaseMissing('users', ['username' => 'new-user']);
    expect($target->fresh()->getAttributes())->toBe($before);
});

it('allows admins to reset passwords for non-super-admin accounts without changing their roles', function (string $code) {
    $actor = roleAccessUser('admin');
    $target = roleAccessUser($code, 'target');
    $before = $target->password;

    $this->actingAs($actor)->post("/admin/users/{$target->id}/reset-password", [
        'role_id' => Role::where('code', 'super_admin')->firstOrFail()->id,
    ])->assertRedirect()->assertSessionHas('generated_password');

    expect($target->fresh()->password)->not->toBe($before)
        ->and($target->fresh()->role)->toBe($code)
        ->and($target->fresh()->force_change_password)->toBeTrue();
    $this->assertDatabaseHas('audits', [
        'entity_type' => 'User', 'entity_id' => $target->id, 'action' => 'password_reset', 'changed_by' => $actor->id,
    ]);
})->with(['user', 'admin', 'display']);

it('prevents admins from deleting bookings', function () {
    $this->seed(BookingStatusSeeder::class);
    $actor = roleAccessUser('admin');
    $room = Room::create(['name' => 'Meeting', 'capacity' => 10, 'location' => 'Office', 'is_available' => true]);
    $booking = Booking::create([
        'room_id' => $room->id, 'user_id' => $actor->id, 'date' => '2026-10-23',
        'start_time' => '09:00', 'end_time' => '10:00', 'title' => 'Meeting', 'participants_count' => 3,
        'status_id' => BookingStatus::where('code', 'PENDING')->firstOrFail()->id,
    ]);

    $this->actingAs($actor)->get('/admin/bookings')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canDeleteBookings', false));
    $this->delete("/admin/bookings/{$booking->id}")->assertForbidden();

    $this->assertNotSoftDeleted($booking);
    $this->assertDatabaseMissing('audits', ['entity_type' => 'Booking', 'entity_id' => $booking->id, 'action' => 'deleted']);
});

it('allows super admins to open user and display pages', function () {
    $user = roleAccessUser('super_admin');

    foreach (['/availability', '/booking', '/riwayat', '/display', '/profile'] as $url) {
        $this->actingAs($user)->get($url)->assertOk();
    }
});

it('redirects login and authenticated entry points using the assigned role', function (string $code, string $destination) {
    $user = roleAccessUser($code);

    $this->post('/login', ['username' => $user->username, 'password' => 'password'])
        ->assertRedirect($destination);
    $this->assertAuthenticatedAs($user);
    $this->get('/')->assertRedirect($destination);
    $this->get('/login')->assertRedirect($code === 'user' ? '/' : $destination);
})->with([
    ['super_admin', '/admin'], ['admin', '/admin'], ['user', '/availability'], ['display', '/display'],
]);

it('rejects access to admin pages for non-administrators', function (string $code) {
    $this->actingAs(roleAccessUser($code))->get('/admin/users')->assertForbidden();
})->with(['user', 'display']);

it('requires authentication for user management', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

it('provides role ids and labels while retaining the role code in page data', function () {
    $user = roleAccessUser('super_admin');

    $this->actingAs($user)->get('/admin/users?role=super_admin')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.role', 'super_admin')
        ->where('auth.user.role_id', $user->role_id)
        ->missing('auth.user.password')
        ->missing('auth.user.assigned_role')
        ->has('users', 1)
        ->where('users.0.role', 'super_admin')
        ->where('canManageUsers', true)
        ->has('roles', 4)
        ->where('roles.3.name', 'Super Admin'));
});

it('allows a super admin to assign a role id and records both role labels in the audit', function () {
    $actor = roleAccessUser('super_admin');
    $target = roleAccessUser('user', 'target');

    $this->actingAs($actor)->put("/admin/users/{$target->id}", [
        'name' => $target->name, 'username' => $target->username, 'role_id' => $actor->role_id,
    ])->assertRedirect('/admin/users');

    expect($target->fresh()->role)->toBe('super_admin');
    $audit = Audit::where('entity_type', 'User')->where('entity_id', $target->id)->firstOrFail();
    expect($audit->old_values['role'])->toBe('user')
        ->and($audit->new_values['role'])->toBe('super_admin')
        ->and($audit->new_values['role_id'])->toBe($actor->role_id);
});

it('allows a super admin to create another super admin', function () {
    $actor = roleAccessUser('super_admin');

    $this->actingAs($actor)->post('/admin/users', [
        'name' => 'New Super Admin', 'username' => 'new-super', 'role_id' => $actor->role_id,
    ])->assertRedirect('/admin/users')->assertSessionHas('generated_password');

    $created = User::where('username', 'new-super')->firstOrFail();
    expect($created->role)->toBe('super_admin')
        ->and($created->force_change_password)->toBeTrue();
});

it('rejects missing and unknown role ids', function (?int $roleId) {
    $this->actingAs(roleAccessUser('super_admin'))->post('/admin/users', [
        'name' => 'Invalid', 'username' => 'invalid-role', 'role_id' => $roleId,
    ])->assertSessionHasErrors('role_id');

    $this->assertDatabaseMissing('users', ['username' => 'invalid-role']);
})->with([null, 999999]);

it('prevents an admin from assigning super admin on create or update', function () {
    $actor = roleAccessUser('admin');
    $roleId = Role::where('code', 'super_admin')->firstOrFail()->id;

    $this->actingAs($actor)->post('/admin/users', [
        'name' => 'Escalated', 'username' => 'escalated', 'role_id' => $roleId,
    ])->assertForbidden();
    $this->put("/admin/users/{$actor->id}", [
        'name' => $actor->name, 'username' => $actor->username, 'role_id' => $roleId,
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['username' => 'escalated']);
    expect($actor->fresh()->role)->toBe('admin');
});

it('protects super admin accounts from changes by an admin', function (string $method, string $suffix) {
    $actor = roleAccessUser('admin');
    $target = roleAccessUser('super_admin', 'super');
    $before = $target->fresh()->getAttributes();

    $this->actingAs($actor)->call($method, "/admin/users/{$target->id}{$suffix}", [
        'name' => 'Changed', 'username' => $target->username, 'role_id' => $actor->role_id,
    ])->assertForbidden();

    expect($target->fresh()->getAttributes())->toBe($before);
})->with([['PUT', ''], ['POST', '/reset-password'], ['DELETE', '']]);

it('allows a super admin to reset another super admins password', function () {
    $actor = roleAccessUser('super_admin');
    $target = roleAccessUser('super_admin', 'super');
    $oldPassword = $target->password;

    $this->actingAs($actor)->post("/admin/users/{$target->id}/reset-password")
        ->assertRedirect()->assertSessionHas('generated_password');

    expect($target->fresh()->password)->not->toBe($oldPassword)
        ->and($target->fresh()->force_change_password)->toBeTrue();
});

it('authorizes booking broadcasts using the assigned role', function (string $code, bool $allowed) {
    $callback = Broadcast::getChannels()['bookings'];

    expect($callback(roleAccessUser($code)))->toBe($allowed);
})->with([['super_admin', true], ['admin', true], ['user', true], ['display', false]]);

it('supports super admin bookings in both admin and user forms', function (string $path, bool $adminForm) {
    $this->travelTo(now()->setDate(2026, 10, 22));
    $this->seed(BookingStatusSeeder::class);
    $actor = roleAccessUser('super_admin');
    $room = Room::create(['name' => 'Meeting', 'capacity' => 10, 'location' => 'Office', 'is_available' => true]);
    $data = [
        'room_id' => $room->id, 'date' => '2026-10-23', 'start_time' => '09:00', 'end_time' => '10:00',
        'title' => 'Super Admin meeting', 'participants' => 3,
    ];
    if ($adminForm) {
        $data += ['user_id' => $actor->id, 'status' => 'approved'];
    }

    $this->actingAs($actor)->post($path, $data)->assertRedirect()->assertSessionHasNoErrors();

    $booking = Booking::firstOrFail();
    expect($booking->user_id)->toBe($actor->id)
        ->and($booking->status->code)->toBe($adminForm ? 'APPROVED' : 'PENDING');
})->with([['/admin/bookings', true], ['/booking', false]]);

it('allows super admins to process other users bookings while preserving status rules', function (string $method, string $action, string $before, string $after, int $response) {
    $this->seed(BookingStatusSeeder::class);
    $actor = roleAccessUser('super_admin');
    $owner = roleAccessUser('user', 'owner');
    $room = Room::create(['name' => 'Meeting', 'capacity' => 10, 'location' => 'Office', 'is_available' => true]);
    $booking = Booking::create([
        'room_id' => $room->id, 'user_id' => $owner->id, 'date' => '2026-10-23',
        'start_time' => '09:00', 'end_time' => '10:00', 'title' => 'Meeting', 'participants_count' => 3,
        'status_id' => BookingStatus::where('code', $before)->firstOrFail()->id,
    ]);

    $this->actingAs($actor)->call($method, "/admin/bookings/{$booking->id}/{$action}", ['reason' => 'Unavailable'])
        ->assertStatus($response);

    expect($booking->fresh()->status->code)->toBe($after);
})->with([
    ['PATCH', 'approve', 'PENDING', 'APPROVED', 302],
    ['PATCH', 'reject', 'PENDING', 'REJECTED', 302],
    ['PUT', 'cancel', 'PENDING', 'CANCELLED', 302],
    ['PATCH', 'finish', 'APPROVED', 'FINISHED', 302],
    ['PATCH', 'approve', 'FINISHED', 'FINISHED', 403],
]);

it('filters audit entries by super admin role', function () {
    $actor = roleAccessUser('super_admin');
    $this->post('/login', ['username' => $actor->username, 'password' => 'password'])->assertRedirect();

    $this->get('/admin/audits?role=super_admin')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('audits.data', 1)
            ->where('audits.data.0.user.role', 'super_admin')
            ->where('stats.admin', 1));
});

it('checks the current target and actor roles before resetting a password', function (bool $promoteTarget) {
    $actor = roleAccessUser('admin');
    $target = roleAccessUser('user', 'target');
    $originalPassword = $target->password;
    User::whereKey($promoteTarget ? $target->id : $actor->id)->update([
        'role_id' => Role::where('code', $promoteTarget ? 'super_admin' : 'user')->firstOrFail()->id,
    ]);

    expect(fn () => app(UserService::class)->resetPassword($target, $actor->id))
        ->toThrow(HttpException::class);
    expect($target->fresh()->password)->toBe($originalPassword);
    expect(Audit::where('action', 'password_reset')->exists())->toBeFalse();
})->with([true, false]);
