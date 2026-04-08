<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.passwords.change');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $guard = Auth::guard('web')->check() ? 'web' : 'donor';
        $user = Auth::guard($guard)->user();

        if (! $user || ! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        return redirect()
            ->route($guard === 'web' ? 'dashboard' : 'donor.portal.profile')
            ->with('success', 'Password updated successfully.');
    }
}
