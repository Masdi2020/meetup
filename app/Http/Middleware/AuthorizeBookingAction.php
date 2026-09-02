<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeBookingAction
{
    /** @var array<string, array<string, array<int, string>>> */
    private const PERMISSIONS = [
        'admin' => [
            'update' => ['APPROVED'],
            'cancel' => ['APPROVED'],
            'approve' => ['PENDING'],
            'reject' => ['PENDING'],
            'finish' => ['APPROVED'],
            'destroy' => ['*'],
        ],
        'user' => [
            'update' => ['PENDING', 'APPROVED'],
            'cancel' => ['PENDING', 'APPROVED'],
            'finish' => ['APPROVED'],
        ],
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $booking = $request->route('booking');

        if (! $booking instanceof Booking) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role === 'user' && $booking->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah booking ini.');
        }

        $booking->loadMissing('status');
        $allowedStatuses = self::PERMISSIONS[$user->role][$action] ?? [];
        $statusAllowed = in_array('*', $allowedStatuses, true)
            || in_array($booking->status->code, $allowedStatuses, true);

        if (! $statusAllowed) {
            abort(403, 'Aksi ini tidak diizinkan untuk role atau status booking tersebut.');
        }

        return $next($request);
    }
}
