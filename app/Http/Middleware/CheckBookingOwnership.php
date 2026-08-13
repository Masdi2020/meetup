<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBookingOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $booking = $request->route('booking');

        if (! $booking instanceof Booking) {
            abort(404, 'Booking tidak ditemukan');
        }

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        if ($booking->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah booking ini');
        }

        return $next($request);
    }
}
