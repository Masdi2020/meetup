<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/login', 'Login')->name('login');
Route::inertia('/calendar', 'Calendar')->name('calendar');
