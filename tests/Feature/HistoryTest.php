<?php

use App\Models\BookingStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('provides booking status options from the database', function () {
    $user = User::create([
        'name' => 'History User',
        'username' => 'history-user',
        'password' => 'password',
        'role' => 'user',
    ]);

    BookingStatus::create([
        'code' => 'REJECTED',
        'label' => 'Ditolak',
    ]);
    BookingStatus::create([
        'code' => 'FINISHED',
        'label' => 'Selesai',
    ]);

    $this->actingAs($user)
        ->get('/riwayat')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('History')
            ->has('statuses', 2)
            ->where('statuses.0.value', 'Rejected')
            ->where('statuses.0.label', 'Ditolak')
            ->where('statuses.1.value', 'Finished')
            ->where('statuses.1.label', 'Selesai'));
});
