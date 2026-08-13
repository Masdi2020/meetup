<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appName' => ['required', 'string', 'max:255'],
            'sessionTimeout' => [
                'required',
                'integer',
                'min:1',
                'max:43200',
            ],
        ]);

        Setting::set('app_name', $validated['appName'], 'string');
        Setting::set('session_timeout', $validated['sessionTimeout'], 'integer');

        return back()->with('success', 'Pengaturan berhasil diperbarui');
    }
}
