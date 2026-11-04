@extends('layouts.app')

@section('content')
<div class="cbis-page-heading">
    <div>
        <div class="cbis-eyebrow">User management</div>
        <h1 class="cbis-page-title mb-0">{{ $staffUser->name }}</h1>
        <p class="cbis-page-subtitle">Account details and access status.</p>
    </div>
    <a href="{{ route('staff-users.index') }}" class="btn btn-outline-secondary">Back to users</a>
</div>

<div class="card card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9 text-break">{{ $staffUser->email }}</dd>
        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $staffUser->phone ?: 'Not recorded' }}</dd>
        <dt class="col-sm-3">Facility</dt><dd class="col-sm-9">{{ $staffUser->facility->name ?? 'Not assigned' }}</dd>
        <dt class="col-sm-3">Role / services</dt><dd class="col-sm-9">{{ $staffUser->hasAllRoles(['Donor', 'Patient']) ? $staffUser->getRoleNames()->reject(fn ($role) => in_array($role, ['Donor', 'Patient']))->push('Patient/Donor')->implode(', ') : $staffUser->getRoleNames()->implode(', ') }}</dd>
        <dt class="col-sm-3">Status</dt>
        <dd class="col-sm-9"><span class="badge {{ $staffUser->is_active ? 'cbis-status-active' : 'cbis-status-expired' }}">{{ $staffUser->is_active ? 'Active' : 'Inactive' }}</span></dd>
        <dt class="col-sm-3">Account created</dt><dd class="col-sm-9">{{ $staffUser->created_at?->format('M j, Y g:i A') ?? 'Unknown' }}</dd>
    </dl>
</div>
@endsection
