<?php

use App\Http\Controllers\ShutdownCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/shutdown-check', ShutdownCheckController::class);
