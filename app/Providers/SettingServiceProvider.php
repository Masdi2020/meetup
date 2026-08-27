<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            $appName = Setting::get('app_name', config('app.name'));
            $sessionLifetime = Setting::get('session_timeout', 60);
        } catch (QueryException) {
            // The database may not exist yet while Composer discovers packages
            // or before the application's initial migrations have run.
            $appName = config('app.name');
            $sessionLifetime = 60;
        }

        config([
            'app.name' => $appName,
            'session.lifetime' => $sessionLifetime,
        ]);
    }
}
