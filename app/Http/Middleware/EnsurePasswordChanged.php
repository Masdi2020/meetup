<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->force_change_password
            && ! $request->routeIs('profile.edit', 'profile.forced-password', 'logout')) {
            if ($request->expectsJson()) {
                abort(403, 'Ganti password Anda sebelum melanjutkan.');
            }

            return to_route('profile.edit');
        }

        return $next($request);
    }
}
