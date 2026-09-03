<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingController extends Controller
{
    public function __construct(private AuditService $audits) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Setting', [
            'settings' => [
                'appName' => Setting::get('app_name', config('app.name')),
                'sessionTimeout' => Setting::get('session_timeout', 60),
            ],
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $oldValues = [
                'app_name' => Setting::get('app_name', config('app.name')),
                'session_timeout' => Setting::get('session_timeout', 60),
            ];

            Setting::set('app_name', $validated['appName'], 'string');
            Setting::set('session_timeout', $validated['sessionTimeout'], 'integer');

            $setting = Setting::query()->where('key', 'app_name')->firstOrFail();

            $this->audits->record(
                'Setting',
                $setting->id,
                'updated',
                $oldValues,
                [
                    'app_name' => $validated['appName'],
                    'session_timeout' => $validated['sessionTimeout'],
                ],
                $request->user()->id,
                'pengaturan aplikasi diperbarui oleh admin',
            );
        });

        config([
            'app.name' => $validated['appName'],
            'session.lifetime' => $validated['sessionTimeout'],
        ]);

        return back()->with('success', 'Pengaturan berhasil diperbarui');
    }
}
