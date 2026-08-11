<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminAuditController extends Controller
{
    public function index(Request $request): InertiaResponse {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();

        $audits = Audit::query()
            ->with([
                'user:id,name,role'
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('comment','like', "%{$search}%")
                        ->orWhere("entity_type","like", "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $role !== '',
                fn ($query) => $query->whereHas(
                    'user',
                    fn ($query) => $query->where('role', $role)
                )
            )
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $today = now()->toDateString();

        $stats = [
            'total' => Audit::count(),

            'today' => Audit::query()
                ->whereDate('created_at', $today)
                ->count(),

            'admin' => Audit::query()
                ->whereHas('user', function ($query) {
                    $query->where('role', 'admin');
                })->count(),

            'user' => Audit::query()
                ->whereHas('user', function ($query) {
                    $query->where('role', 'user');
                })->count(),
        ];

        return Inertia::render('Admin/Audit', [
            'audits' => $audits,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'role'=> $role,
            ],
        ]);
    }
}
