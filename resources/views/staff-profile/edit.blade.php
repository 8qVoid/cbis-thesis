@extends('layouts.app')

@section('content')
<div class="cbis-dashboard-heading">
    <div>
        <div class="cbis-eyebrow">Your account</div>
        <h1 class="cbis-page-title">My Profile</h1>
        <p class="cbis-page-subtitle">Update your own contact details or correct your name. Changes are recorded in the audit log.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('staff-profile.update') }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-header cbis-card-title"><span>Account Information</span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="staffName" class="form-label">Full name</label>
                        <input id="staffName" name="name" class="form-control js-person-name" maxlength="255" pattern="[\p{L}\s.'-]+" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="staffEmail" class="form-label">Email</label>
                        <input id="staffEmail" name="email" type="email" class="form-control" maxlength="255" value="{{ old('email', $user->email) }}" required>
                        <small class="text-muted">Use the new email to sign in after saving.</small>
                        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="staffPhone" class="form-label">Mobile number <span class="text-muted">(optional)</span></label>
                        <input id="staffPhone" name="phone" class="form-control js-mobile-local" inputmode="numeric" minlength="11" maxlength="11" pattern="09\d{9}" title="Enter exactly 11 digits starting with 09" value="{{ \App\Support\PhilippinePhone::mobileLocal(old('phone', $user->phone)) }}" placeholder="09171234567">
                        <small class="text-muted">Enter all 11 digits, starting with 09.</small>
                        @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button class="btn btn-danger mt-4" type="submit">Save Profile</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <section class="card">
            <div class="card-header cbis-card-title"><span>Staff Assignment</span></div>
            <div class="card-body">
                <p class="mb-2"><strong>Role:</strong> Blood Bank Staff</p>
                <p class="mb-0"><strong>Facility:</strong> {{ $user->facility?->name ?? 'Not assigned' }}</p>
                <small class="text-muted d-block mt-3">Your role and facility cannot be changed from this profile.</small>
            </div>
        </section>
    </div>
</div>
@endsection
