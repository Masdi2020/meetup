<?php

use App\Http\Controllers\Display\BannerController;
use App\Http\Controllers\Display\DisplayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:display'])
    ->prefix('display')
    ->name('display.')
    ->group(function () {
        Route::get('/', [DisplayController::class, 'index'])->name('index');
        Route::get('/{room}', [BannerController::class, 'index'])->name('banner');
    });
