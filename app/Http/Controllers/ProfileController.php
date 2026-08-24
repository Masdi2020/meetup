<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return inertia('Profile', [
            'user' => request()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update($validated);

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

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_change_password' => false,
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function updateForcedPassword(SetPasswordRequest $request): RedirectResponse
    {
        abort_unless($request->user()->force_change_password, 403);

        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_change_password' => false,
        ]);

        return back()->with('success', 'Password berhasil dibuat.');
    }
}
