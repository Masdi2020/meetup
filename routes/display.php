<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:display'])
    ->prefix('display')
    ->name('display.')
    ->group(function () {
        Route::get('/', [Controllers\DisplayController::class, 'index'])->name('index');
        Route::get('/{room}', [Controllers\BannerController::class, 'index'])->name('banner');
    });

