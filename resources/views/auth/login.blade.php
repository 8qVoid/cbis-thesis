@extends('layouts.app')

@section('content')
<div class="row justify-content-center cbis-login-page">
    <div class="col-md-7 col-lg-5">
        <div class="text-center mb-4">
            <span class="cbis-eyebrow">CBIS · Red Cross</span>
            <h1 class="cbis-page-title mt-2">Welcome back</h1>
            <p class="cbis-page-subtitle">Sign in to your blood services account.</p>
        </div>
        <div class="card cbis-login-card">
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
                        <div class="cbis-password-field">
                            <input id="password" name="password" type="password" autocomplete="current-password" class="form-control" required>
                            <button type="button" class="cbis-password-toggle" aria-label="Show password" aria-controls="password" aria-pressed="false" title="Password hidden — Show password" data-password-toggle>
                                <svg class="cbis-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="cbis-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 6.1A10.9 10.9 0 0 1 12 6c6.4 0 10 6 10 6a15.9 15.9 0 0 1-3.1 3.5M6.6 6.6C3.7 8.3 2 12 2 12s3.6 6 10 6c1.4 0 2.7-.3 3.8-.8"/><path d="M10.5 10.5a3 3 0 0 0 4 4"/></svg>
                            </button>
                        </div>
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

@push('scripts')
<script>
document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
    const field = document.getElementById('password');
    const visible = field.type === 'password';
    const selectionStart = field.selectionStart;
    const selectionEnd = field.selectionEnd;
    const selectionDirection = field.selectionDirection;
    field.type = visible ? 'text' : 'password';
    this.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    this.setAttribute('aria-pressed', String(visible));
    this.title = visible ? 'Password visible — Hide password' : 'Password hidden — Show password';
    this.querySelector('.cbis-eye-show').hidden = !visible;
    this.querySelector('.cbis-eye-hide').hidden = visible;
    field.focus({ preventScroll: true });
    field.setSelectionRange(selectionStart, selectionEnd, selectionDirection);
});
</script>
@endpush
