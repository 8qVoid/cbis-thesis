@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">Reset Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.reset.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="account_type" value="{{ $accountType }}">
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <input class="form-control" value="{{ $accountType === 'donor' ? 'Donor Account' : 'Staff Account' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input name="email" type="email" value="{{ old('email', $email) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input name="password_confirmation" type="password" class="form-control" required>
                    </div>
                    <button class="btn btn-danger w-100" type="submit">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
