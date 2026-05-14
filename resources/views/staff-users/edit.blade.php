@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Edit Staff Contact</h1>
        <p class="cbis-page-subtitle">Update staff display name and mobile contact number.</p>
    </div>
    <a href="{{ route('staff-users.index') }}" class="btn btn-outline-secondary">Back to Staff</a>
</div>

<form
    method="POST"
    action="{{ route('staff-users.update', $staffUser) }}"
    class="card card-body js-confirm-action"
    data-confirm-title="Update staff contact?"
    data-confirm-message="Are you sure you want to update this staff account's contact details?"
    data-confirm-button="Update Staff"
    data-confirm-variant="danger"
>
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" value="{{ old('name', $staffUser->name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" value="{{ $staffUser->email }}" disabled>
            <small class="text-muted">Email is used for login and cannot be edited here.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Mobile Number <span class="text-muted">(Optional)</span></label>
            <div class="input-group">
                <span class="input-group-text">09</span>
                <input
                    name="phone"
                    class="form-control js-mobile-suffix"
                    value="{{ \App\Support\PhilippinePhone::mobileSuffix(old('phone', $staffUser->phone)) }}"
                    inputmode="numeric"
                    maxlength="9"
                    pattern="\d{9}"
                    placeholder="123456789"
                >
            </div>
            <small class="text-muted">Leave blank if the staff account has no mobile number.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Facility</label>
            <input class="form-control" value="{{ $staffUser->facility?->name ?? '-' }}" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">Role</label>
            <input class="form-control" value="{{ $staffUser->getRoleNames()->implode(', ') }}" disabled>
            <small class="text-muted">Create a separate staff account if a different role is needed.</small>
        </div>
        <div class="col-12">
            <button class="btn btn-danger">Update Staff</button>
        </div>
    </div>
</form>
@endsection
