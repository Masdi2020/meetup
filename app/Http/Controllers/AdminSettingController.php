<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Setting', [
            'settings' => [
                'appName' => Setting::get('app_name', 'Metting Room'),
                'sessionTimeout' => Setting::get('session_timeout', 60),
            ],
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Setting::set('app_name', $validated['appName'], 'string');
        Setting::set('session_timeout', $validated['sessionTimeout'], 'integer');

        return back()->with('success', 'Pengaturan berhasil diperbarui');
    }
}
