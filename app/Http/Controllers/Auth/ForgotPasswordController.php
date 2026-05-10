<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.passwords.email');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $status = Password::broker($this->brokerFor($data['account_type']))
            ->sendResetLink(['email' => $data['email']]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)])->onlyInput('email', 'account_type');
    }

    public function edit(string $accountType, string $token): View
    {
        abort_unless(in_array($accountType, ['staff', 'donor'], true), 404);

        return view('auth.passwords.reset', [
            'accountType' => $accountType,
            'token' => $token,
            'email' => request('email', ''),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $status = Password::broker($this->brokerFor($data['account_type']))->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)])->onlyInput('email', 'account_type');
    }

    private function brokerFor(string $accountType): string
    {
        return $accountType === 'donor' ? 'donors' : 'users';
    }
}
