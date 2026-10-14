<?php

use App\Models\Setting;
use App\Providers\SettingServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;

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
