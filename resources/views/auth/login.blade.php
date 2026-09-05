@extends('layouts.app')

@section('content')
<div class="row justify-content-center cbis-login-page">
    <div class="col-md-7 col-lg-5">
        <div class="text-center mb-4"><span class="cbis-eyebrow">CBIS · Red Cross</span><h1 class="cbis-page-title mt-2">Welcome back</h1><p class="cbis-page-subtitle">Sign in to your blood services account.</p></div>
        <div class="card">
            <div class="card-header cbis-card-title"><span>Unified Login</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="login">Email or Philippine Mobile Number</label>
                        <input
                            name="login"
                            id="login"
                            autocomplete="username"
                            type="text"
                            value="{{ old('login') }}"
                            class="form-control"
                            placeholder="name@example.com or +63 917 123 4567"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button class="btn btn-danger w-100" type="submit">Sign in</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="text-danger text-decoration-none">Forgot password?</a>
                </div>
                <hr>
                <a href="{{ route('donor.register') }}" class="btn btn-outline-secondary w-100">Register as Donor or Patient</a>
            </div>
        </div>
    </div>
</div>
@endsection
