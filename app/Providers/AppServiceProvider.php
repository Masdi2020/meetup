<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DevCommands::except('queue');
        $this->configureDefaults();

        RateLimiter::for('login', function (Request $request): array {
            $username = $request->input('username');
            $username = is_string($username) ? Str::lower($username) : '';
            $ip = $request->ip();

            $blocked = function (Request $request, array $headers): Response {
                $message = 'Terlalu banyak percobaan login. Coba lagi dalam '.$headers['Retry-After'].' detik.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 429, $headers);
                }

                return to_route('login', status: 303, headers: $headers)
                    ->withErrors(['username' => $message])
                    ->withInput($request->only('username'));
            };

            return [
                Limit::perMinute(30)->by('ip:'.$ip)->response($blocked),
                Limit::perMinute(5)->by('username:'.$username.'|'.$ip)->response($blocked),
            ];
        });

        // Redirect authenticated users to appropriate dashboard based on role
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $user = auth()->user();

            if ($user && $user->isAdmin()) {
                return route('admin.dashboard');
            }

            if ($user && $user->role === 'display') {
                return route('display.index');
            }

            return '/';
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }
}
