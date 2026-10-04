@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="cbis-page-heading">
            <div>
                <h1 class="cbis-page-title mb-0">Forgot Password</h1>
                <p class="cbis-page-subtitle">Enter your account email to receive a password reset link.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('password.email') }}" class="cbis-compact-form">
                    @csrf
                    <div class="mb-3">
                        <label for="reset-account-type" class="form-label">Account Type</label>
                        <select id="reset-account-type" name="account_type" class="form-select" required>
                            <option value="staff" @selected(old('account_type', 'staff') === 'staff')>Staff Account</option>
                            <option value="donor" @selected(old('account_type') === 'donor')>Donor Account</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reset-email" class="form-label">Email Address</label>
                        <input id="reset-email" name="email" type="email" value="{{ old('email') }}" class="form-control" autocomplete="email" required>
                    </div>
                    <div class="cbis-form-actions">
                        <button class="btn btn-danger" type="submit">Send Reset Link</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-danger text-decoration-none">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
