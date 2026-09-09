<?php

namespace App\Services\Admin;

use App\Models\Audit;

class AuditQueryService
{
    /** @return array<string, mixed> */
    public function pageData(string $search, string $role): array
    {
        $audits = Audit::query()
            ->with(['user:id,name,role'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(
                $role !== '',
                fn ($query) => $query->whereHas('user', fn ($query) => $query->where('role', $role))
            )
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $today = now()->toDateString();

        return [
            'audits' => $audits,
            'stats' => [
                'total' => Audit::count(),
                'today' => Audit::whereDate('created_at', $today)->count(),
                'admin' => Audit::whereHas('user', fn ($query) => $query->where('role', 'admin'))->count(),
                'user' => Audit::whereHas('user', fn ($query) => $query->where('role', 'user'))->count(),
            ],
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ];
    }
}
