@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="cbis-page-heading">
            <div>
                <h1 class="cbis-page-title mb-0">Change Password</h1>
                <p class="cbis-page-subtitle">Confirm your current password to update your account security.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}" class="cbis-compact-form">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="current-password" class="form-label">Current Password</label>
                        <input id="current-password" type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new-password" class="form-label">New Password</label>
                        <input id="new-password" type="password" name="password" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm-new-password" class="form-label">Confirm New Password</label>
                        <input id="confirm-new-password" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="cbis-form-actions">
                        <button class="btn btn-danger" type="submit">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
