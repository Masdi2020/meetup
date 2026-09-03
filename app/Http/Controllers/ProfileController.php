<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private AuditService $audits) {}

    public function edit(): Response
    {
        return inertia('Profile', [
            'user' => request()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $oldValues = $user->only(['name', 'username']);
            $user->update($validated);

            $this->audits->record(
                'User',
                $user->id,
                'profile_updated',
                $oldValues,
                $user->only(['name', 'username']),
                $user->id,
                'profil diperbarui oleh pengguna',
            );
        });

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $user->update([
                'password' => Hash::make($validated['password']),
                'force_change_password' => false,
            ]);

            $this->audits->record(
                'User',
                $user->id,
                'password_changed',
                null,
                ['force_change_password' => false],
                $user->id,
                'password diubah oleh pengguna',
            );
        });

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function updateForcedPassword(SetPasswordRequest $request): RedirectResponse
    {
        abort_unless($request->user()->force_change_password, 403);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $user->update([
                'password' => Hash::make($validated['password']),
                'force_change_password' => false,
            ]);

            $this->audits->record(
                'User',
                $user->id,
                'password_changed',
                ['force_change_password' => true],
                ['force_change_password' => false],
                $user->id,
                'password awal dibuat oleh pengguna',
            );
        });

        return back()->with('success', 'Password berhasil dibuat.');
    }
}
