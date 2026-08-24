<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(string $entityType, int $entityId, string $action, ?array $oldValues, ?array $newValues, ?int $changedBy, string $comment): Audit
    {
        return Audit::create([
            'entity_type' => $entityType, 'entity_id' => $entityId, 'action' => $action,
            'old_values' => $oldValues, 'new_values' => $newValues, 'changed_by' => $changedBy,
            'ip_address' => $this->ipAddress(), 'comment' => $comment,
        ]);
    }

    private function ipAddress(): string
    {
        $forwarded = $this->request->header('X-Forwarded-For') ?: $this->request->header('Client-IP');
        $ip = $forwarded ? trim(explode(',', $forwarded)[0]) : $this->request->ip();

        return $ip === '::1' ? '127.0.0.1' : (string) $ip;
    }
}
