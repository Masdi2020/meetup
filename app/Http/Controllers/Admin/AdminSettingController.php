<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Services\Admin\SettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Setting', [
            'settings' => $this->settings->formData(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->update($validated, $request->user()->id);

        return back()->with('success', 'Pengaturan berhasil diperbarui');
    }
}
