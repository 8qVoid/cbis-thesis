<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\PhilippinePhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $login = trim($data['login']);
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $normalizedMobile = PhilippinePhone::normalizeMobile($login);
        $isPhilippineMobile = $normalizedMobile !== null;

        if (! $isEmail && ! $isPhilippineMobile) {
            return back()->withErrors([
                'login' => 'Use a valid email or Philippine mobile number.',
            ])->onlyInput('login');
        }

        $remember = $request->boolean('remember');
        $loginValue = $isEmail ? $login : $normalizedMobile;

        // Try staff account first.
        $webCredentials = [
            ($isEmail ? 'email' : 'phone') => $loginValue,
            'password' => $data['password'],
            'is_active' => true,
        ];

        if (Auth::guard('web')->attempt($webCredentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Fallback to donor account.
        $donorCredentials = [
            ($isEmail ? 'email' : 'contact_number') => $loginValue,
            'password' => $data['password'],
            'is_online_registered' => true,
        ];

        if (Auth::guard('donor')->attempt($donorCredentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('donor.portal.profile'));
        }

        return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('donor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
