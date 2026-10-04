@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="cbis-page-heading">
            <div>
                <h1 class="cbis-page-title mb-0">Reset Password</h1>
                <p class="cbis-page-subtitle">Choose a new password for your account.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('password.reset.update') }}" class="cbis-compact-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="account_type" value="{{ $accountType }}">
                    <div class="mb-3">
                        <label for="reset-account-type" class="form-label">Account Type</label>
                        <input id="reset-account-type" class="form-control" value="{{ $accountType === 'donor' ? 'Donor Account' : 'Staff Account' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="reset-email" class="form-label">Email Address</label>
                        <input id="reset-email" name="email" type="email" value="{{ old('email', $email) }}" class="form-control" autocomplete="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="reset-password" class="form-label">New Password</label>
                        <input id="reset-password" name="password" type="password" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="reset-password-confirmation" class="form-label">Confirm New Password</label>
                        <input id="reset-password-confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="cbis-form-actions">
                        <button class="btn btn-danger" type="submit">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
