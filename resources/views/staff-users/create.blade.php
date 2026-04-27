@extends('layouts.app')

@section('content')
@php
    $roleDescriptions = [
        'Facilitator' => 'Approved facility front desk access for staff, donor records, donation records, bloodletting records, and event scheduling.',
        'Medical Staff / Nurse' => 'Inventory-only access for blood stock updates.',
    ];
@endphp
<h4>Create Staff Account</h4>
<form method="POST" action="{{ route('staff-users.store') }}" class="card card-body">
    @csrf
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+63 917 123 4567 or 09171234567"></div>
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
        <div class="col-md-4"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
        <div class="col-12"><button class="btn btn-danger">Create Staff User</button></div>
    </div>
</form>
@endsection
