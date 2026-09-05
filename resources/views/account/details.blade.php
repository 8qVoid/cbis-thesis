@extends('layouts.app')
@section('content')
<div class="cbis-dashboard-heading"><div><div class="cbis-eyebrow">Your account</div><h1 class="cbis-page-title">My Profile</h1><p class="cbis-page-subtitle">Your personal information, shared across your selected services.</p></div><a href="{{ route('account.dashboard') }}" class="btn btn-outline-secondary">Back to Home</a></div>
<div class="row g-4"><div class="col-lg-8"><form method="POST" action="{{ route('account.details.update') }}" class="card">@csrf @method('PUT')
<div class="card-header cbis-card-title"><span>Personal Information</span></div><div class="card-body"><div class="row g-3">
@foreach(['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name'] as $field => $label)<div class="col-md-4"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" class="form-control" maxlength="80" value="{{ old($field, $user->$field) }}" @required($field !== 'middle_name')></div>@endforeach
<div class="col-12"><label for="address" class="form-label">Address</label><textarea id="address" name="address" class="form-control" maxlength="500" rows="2" required>{{ old('address', $user->address) }}</textarea></div>
<div class="col-md-6"><label class="form-label" for="profileEmail">Email</label><input id="profileEmail" class="form-control" value="{{ $user->email }}" readonly></div><div class="col-md-6"><label class="form-label" for="profilePhone">Mobile number</label><input id="profilePhone" class="form-control" value="{{ $user->phone }}" readonly></div>
<div class="col-12"><small class="text-muted">Contact staff to correct your sign-in contact details.</small></div>
</div><button class="btn btn-danger mt-4" type="submit">Save Profile</button></div></form></div>
<div class="col-lg-4"><section class="card"><div class="card-header cbis-card-title"><span>My Services</span></div><div class="card-body"><p>{{ $user->hasDonorAccess() && $user->hasPatientAccess() ? 'Donor and Patient services are enabled.' : ($user->hasDonorAccess() ? 'Donor services are enabled.' : 'Patient services are enabled.') }}</p><a class="btn btn-outline-danger" href="{{ route('account.profile.edit') }}">Manage Services</a></div></section>
@if($user->donorProfile)<section class="card mt-3"><div class="card-body"><small class="text-muted">Recorded blood type</small><h2 class="h4 mt-2">{{ $user->donorProfile->blood_type }}</h2><p class="small text-muted mb-0">Blood Bank Staff confirms your donation eligibility.</p></div></section>@endif
</div></div>
@endsection
