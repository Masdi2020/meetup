<?php

namespace App\Services\Admin;

use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function __construct(private AuditService $audits) {}

    /** @return array{appName: mixed, sessionTimeout: mixed} */
    public function formData(): array
    {
        return [
            'appName' => Setting::get('app_name', config('app.name')),
            'sessionTimeout' => Setting::get('session_timeout', 60),
        ];
    }

    /** @param array<string, mixed> $data */
    public function update(array $data, int $actorId): void
    {
        DB::transaction(function () use ($data, $actorId) {
            $oldValues = [
                'app_name' => Setting::get('app_name', config('app.name')),
                'session_timeout' => Setting::get('session_timeout', 60),
            ];

            Setting::set('app_name', $data['appName'], 'string');
            Setting::set('session_timeout', $data['sessionTimeout'], 'integer');

            $setting = Setting::query()->where('key', 'app_name')->firstOrFail();

            $this->audits->record(
                'Setting',
                $setting->id,
                'updated',
                $oldValues,
                [
                    'app_name' => $data['appName'],
                    'session_timeout' => $data['sessionTimeout'],
                ],
                $actorId,
                'pengaturan aplikasi diperbarui oleh admin',
            );
        });

        config([
            'app.name' => $data['appName'],
            'session.lifetime' => $data['sessionTimeout'],
        ]);
    }
}
