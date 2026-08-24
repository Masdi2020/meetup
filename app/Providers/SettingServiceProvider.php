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
            $sessionLifetime = Setting::get('session_timeout', 60);
        } catch (QueryException) {
            // The database may not exist yet while Composer discovers packages
            // or before the application's initial migrations have run.
            $sessionLifetime = 60;
        }

        config(['session.lifetime' => $sessionLifetime]);
    }
}
