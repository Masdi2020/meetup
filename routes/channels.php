<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('bookings', function ($user) {
    return $user->isAdmin() || $user->role === 'user';
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
