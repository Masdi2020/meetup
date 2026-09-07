<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('bookings', function ($user) {
    return in_array($user->role, ['user', 'admin'], true);
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
