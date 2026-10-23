<?php

use App\Models\Setting;
use App\Providers\SettingServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('boots console tooling without reading settings from cache', function () {
    $app = Mockery::mock(Application::class);
    $app->shouldReceive('runningInConsole')->once()->andReturnTrue();
    Cache::shouldReceive('rememberForever')->never();

    (new SettingServiceProvider($app))->boot();
});

it('applies stored application settings to HTTP requests', function () {
    Setting::set('app_name', 'Meeting Rooms');
    Setting::set('session_timeout', 120, 'integer');

    $app = Mockery::mock(Application::class);
    $app->shouldReceive('runningInConsole')->once()->andReturnFalse();

    (new SettingServiceProvider($app))->boot();

    expect(config('app.name'))->toBe('Meeting Rooms')
        ->and(config('session.lifetime'))->toBe(120);
});

it('reads committed settings instead of an obsolete cached value', function () {
    Setting::set('app_name', 'Old name');
    expect(Setting::get('app_name'))->toBe('Old name');
    Cache::forever('setting:app_name', 'Old name');

    DB::transaction(fn () => Setting::set('app_name', 'New name'));

    expect(Setting::get('app_name'))->toBe('New name');
});

it('does not retain settings read from a rolled back transaction', function () {
    Setting::set('app_name', 'Original');
    try {
        DB::transaction(function () {
            Setting::set('app_name', 'Uncommitted');
            expect(Setting::get('app_name'))->toBe('Uncommitted');
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(Setting::get('app_name'))->toBe('Original');
});
