<?php

use Illuminate\Support\Facades\Route;

Route::get('/shutdown-check', function () {
    $now = now();

    $canShutdown = $now->format('H:i') >= '16:00';

    return response()->json([
        'can_shutdown' => $canShutdown,
        'current_time' => $now->format('H:i:s'),
        'shutdown_time' => '16:00:00',
    ]);
});
