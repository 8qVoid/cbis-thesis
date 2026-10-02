@extends('layouts.app')

@section('content')
@php
    $roleDescriptions = [
        'Event Facilitator' => 'Creates and manages donation activities. A QAO must approve a location before it becomes public.',
        'Blood Bank Staff' => 'Processes patient reservations, manages the facility inventory, and views detailed donor records.',
    ];
@endphp
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Staff access</div><h1 class="cbis-page-title mb-0">Create staff account</h1><p class="cbis-page-subtitle">Assign a facility role and temporary login password.</p></div><a href="{{ route('staff-users.index') }}" class="btn btn-outline-secondary">Back to staff</a></div>
<p class="alert alert-info">Blood Bank Staff must be assigned to the Bacolod main chapter. Branches have Event Facilitators for activity coordination.</p>
<form method="POST" action="{{ route('staff-users.store') }}" class="card card-body cbis-compact-form cbis-legacy-form">
    @csrf
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control js-person-name" maxlength="255" pattern="[\p{L}\s.'-]+" value="{{ old('name') }}" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
        <div class="col-md-4"><label class="form-label">Mobile Number <span class="text-muted">(Optional)</span></label><input name="phone" class="form-control js-mobile-local" value="{{ \App\Support\PhilippinePhone::mobileLocal(old('phone')) }}" inputmode="numeric" minlength="11" maxlength="11" pattern="09\d{9}" title="Enter exactly 11 digits starting with 09" placeholder="09171234567"><small class="text-muted">Enter all 11 digits starting with 09, or leave blank.</small></div>
        @if(auth('web')->user()?->isCentralAdmin())
            <div class="col-md-4"><label class="form-label">Facility</label><select name="facility_id" class="form-select" required>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string) old('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
        @else
            <input type="hidden" name="facility_id" value="{{ auth('web')->user()?->facility_id }}">
            <div class="col-md-4">
                <label class="form-label">Facility</label>
                <input class="form-control" value="{{ $facilities->first()?->name ?? 'Your Facility' }}" disabled>
                <small class="text-muted">You can create users only for your own facility.</small>
            </div>
        @endif
        <div class="col-md-4">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">
                @foreach($roles as $role)
                    <span class="d-block">{{ $role->name }}: {{ $roleDescriptions[$role->name] ?? 'Facility staff access.' }}</span>
                @endforeach
            </small>
        </div>
        <div class="col-12">
            <hr class="my-1">
            <h5 class="mb-1">Login password</h5>
            <p class="text-muted small mb-0">Set the temporary password the staff member will use for their first login.</p>
        </div>
        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
        <div class="col-12 cbis-form-actions"><a href="{{ route('staff-users.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Create staff user</button></div>
    </div>
</form>
@endsection
