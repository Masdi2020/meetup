<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    public function edit(): Response
    {
        return inertia('Profile', [
            'user' => request()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->accounts->updateProfile($request->user(), $validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->accounts->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['password'],
        );

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function updateForcedPassword(SetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->accounts->setInitialPassword($request->user(), $validated['password']);

        return back()->with('success', 'Password berhasil dibuat.');
    }
}
