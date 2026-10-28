<?php

use App\Models\Audit;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Facility;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function auditAdmin(string $username = 'audit-admin', string $role = 'admin'): User
{
    return User::create([
        'name' => 'Audit Admin',
        'username' => $username,
        'password' => 'password',
        'role_id' => Role::where('code', $role)->firstOrFail()->id,
    ]);
}

it('audits facility create update and delete actions', function () {
    $admin = auditAdmin();

    $this->actingAs($admin)->post('/admin/facilities', [
        'name' => 'Proyektor',
    ])->assertRedirect();

    $facility = Facility::where('name', 'Proyektor')->firstOrFail();

    $this->actingAs($admin)->put("/admin/facilities/{$facility->id}", [
        'name' => 'Proyektor HD',
    ])->assertRedirect();

    $this->actingAs($admin)->delete("/admin/facilities/{$facility->id}")
        ->assertRedirect();

    expect(Audit::where('entity_type', 'Facility')->where('entity_id', $facility->id)
        ->orderBy('id')->pluck('action')->all())->toBe(['created', 'updated', 'deleted']);
});

it('audits room data facilities and availability changes', function () {
    $admin = auditAdmin();
    $facility = Facility::create(['name' => 'Televisi']);

    $this->actingAs($admin)->post('/admin/rooms', [
        'name' => 'Ruang Audit',
        'capacity' => 12,
        'location' => 'Lantai 2',
        'is_available' => true,
        'facilities' => [$facility->id],
    ])->assertRedirect();

    $room = Room::where('name', 'Ruang Audit')->firstOrFail();
    $createdAudit = Audit::where('entity_type', 'Room')
        ->where('entity_id', $room->id)
        ->where('action', 'created')
        ->firstOrFail();

    expect($createdAudit->new_values['facility_ids'])->toBe([$facility->id]);

    $this->actingAs($admin)
        ->patch("/admin/rooms/{$room->id}/toggle-availability")
        ->assertRedirect();

    $availabilityAudit = Audit::where('entity_type', 'Room')
        ->where('entity_id', $room->id)
        ->where('action', 'availability_changed')
        ->firstOrFail();

    expect($availabilityAudit->old_values['is_available'])->toBeTrue()
        ->and($availabilityAudit->new_values['is_available'])->toBeFalse();
});

it('audits user management without storing passwords', function () {
    $admin = auditAdmin('audit-super', 'super_admin');

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Pengguna Audit',
        'username' => 'pengguna-audit',
        'role_id' => Role::where('code', 'user')->firstOrFail()->id,
    ])->assertRedirect();

    $user = User::where('username', 'pengguna-audit')->firstOrFail();

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/reset-password")
        ->assertRedirect();

    $this->actingAs($admin)->delete("/admin/users/{$user->id}")
        ->assertRedirect();

    $audits = Audit::where('entity_type', 'User')
        ->where('entity_id', $user->id)
        ->orderBy('id')
        ->get();

    $auditedValueKeys = $audits
        ->flatMap(fn (Audit $audit) => array_merge(
            array_keys($audit->old_values ?? []),
            array_keys($audit->new_values ?? []),
        ));

    expect($audits->pluck('action')->all())->toBe([
        'created',
        'password_reset',
        'deleted',
    ])->and($auditedValueKeys)->not->toContain('password');
});

it('audits booking deletion with its previous values', function () {
    $admin = auditAdmin('audit-super', 'super_admin');
    $room = Room::create([
        'name' => 'Ruang Booking Audit',
        'capacity' => 10,
        'location' => 'Lantai 1',
        'is_available' => true,
    ]);
    $status = BookingStatus::create([
        'code' => 'PENDING',
        'label' => 'Pending',
    ]);
    $booking = Booking::create([
        'room_id' => $room->id,
        'user_id' => $admin->id,
        'date' => '2026-09-10',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'title' => 'Booking yang dihapus',
        'participants_count' => 5,
        'status_id' => $status->id,
    ]);

    $this->actingAs($admin)->delete("/admin/bookings/{$booking->id}")
        ->assertRedirect();

    $audit = Audit::where('entity_type', 'Booking')
        ->where('entity_id', $booking->id)
        ->where('action', 'deleted')
        ->firstOrFail();

    expect($audit->old_values['title'])->toBe('Booking yang dihapus')
        ->and($audit->new_values)->toBeNull();
});

it('audits login profile settings and logout actions', function () {
    $admin = auditAdmin('audit-super', 'super_admin');

    $this->post('/login', [
        'username' => $admin->username,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->post('/profile', [
        'name' => 'Admin Diperbarui',
        'username' => $admin->username,
    ])->assertRedirect();

    $this->put('/admin/settings', [
        'appName' => 'Meetup Audit',
        'sessionTimeout' => 90,
    ])->assertRedirect();

    $this->post('/logout')->assertRedirect(route('login'));

    $this->post('/login', [
        'username' => 'akun-tidak-ada',
        'password' => 'salah-total',
    ])->assertSessionHasErrors('username');

    expect(Audit::where('changed_by', $admin->id)->pluck('action')->all())
        ->toContain('logged_in', 'profile_updated', 'updated', 'logged_out')
        ->and(Audit::where('entity_type', 'Authentication')
            ->where('action', 'login_failed')->exists())->toBeTrue();
});

it('applies both room toggles from stale models and audits their actual values', function () {
    $actor = auditAdmin();
    $room = Room::create(['name' => 'Room', 'location' => 'Office', 'capacity' => 10, 'is_available' => true]);
    $first = $room->fresh();
    $second = $room->fresh();
    $service = app(RoomService::class);
    $service->toggleAvailability($first, $actor->id);
    $service->toggleAvailability($second, $actor->id);

    expect($room->fresh()->is_available)->toBeTrue();
    $audits = Audit::where('entity_type', 'Room')->where('entity_id', $room->id)
        ->where('action', 'availability_changed')->orderBy('id')->get();
    expect($audits)->toHaveCount(2);
    expect($audits[0]->old_values['is_available'])->toBeTrue()
        ->and($audits[0]->new_values['is_available'])->toBeFalse()
        ->and($audits[1]->old_values['is_available'])->toBeFalse()
        ->and($audits[1]->new_values['is_available'])->toBeTrue();
});
