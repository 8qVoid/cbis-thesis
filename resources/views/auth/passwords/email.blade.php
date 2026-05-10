@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">Forgot Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select" required>
                            <option value="staff" @selected(old('account_type', 'staff') === 'staff')>Staff Account</option>
                            <option value="donor" @selected(old('account_type') === 'donor')>Donor Account</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="form-control" required>
                    </div>
                    <button class="btn btn-danger w-100" type="submit">Send Reset Link</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-danger text-decoration-none">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
