@extends('layouts.app')

@section('content')
@php
    $currentUser = auth('web')->user();
    $canCreateStaff = ($currentUser?->isCentralAdmin() ?? false) || ($currentUser?->can('manage users') ?? false);
    $canEditStaff = $currentUser?->can('manage users') ?? false;
@endphp
<div class="d-flex flex-wrap gap-3 justify-content-between align-items-start mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">User Management</h1>
        <p class="cbis-page-subtitle">Manage staff and public accounts. Donors and patients are public users.</p>
    </div>
    @if($canCreateStaff)
        <a href="{{ route('staff-users.create') }}" class="btn btn-danger">Create Staff Account</a>
    @endif
</div>
<nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Account category">
    @foreach(['all' => 'All users', 'staff' => 'Staff', 'public' => 'Donor/Patient'] as $value => $label)
        <a class="btn {{ $category === $value ? 'btn-danger' : 'btn-outline-secondary' }}" @if($category === $value) aria-current="page" @endif
           href="{{ route('staff-users.index', [...request()->except(['page', 'category', 'role']), 'category' => $value]) }}">{{ $label }}</a>
    @endforeach
</nav>
<form method="GET" class="card card-body mb-3">
    <input type="hidden" name="category" value="{{ $category }}">
    <div class="row g-3 align-items-end">
        <div class="col-lg-4 col-md-6"><label for="user-search" class="form-label">Search users</label><input id="user-search" name="q" value="{{ request('q') }}" maxlength="100" class="form-control" placeholder="Name, email or phone"></div>
        <div class="col-lg-3 col-md-6"><label for="user-role" class="form-label">Role / services</label><select id="user-role" name="role" class="form-select"><option value="">All roles</option>
            @foreach(['Quality Assurance Officer'=>'QAO', 'Event Facilitator'=>'Event Facilitator', 'Blood Bank Staff'=>'Blood Bank Staff', 'donor'=>'Donor only', 'patient'=>'Patient only', 'both'=>'Patient/Donor'] as $value=>$label)
                @if($category === 'all' || ($category === 'staff' && !in_array($value, ['donor','patient','both'])) || ($category === 'public' && in_array($value, ['donor','patient','both'])))<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endif
            @endforeach
        </select></div>
        <div class="col-lg-3 col-md-6"><label for="user-facility" class="form-label">Facility</label><select id="user-facility" name="facility_id" class="form-select"><option value="">All facilities</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string)request('facility_id') === (string)$facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label for="user-status" class="form-label">Status</label><select id="user-status" name="status" class="form-select"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-danger">Apply filters</button><a class="btn btn-outline-secondary" href="{{ route('staff-users.index', ['category'=>$category]) }}">Reset</a></div>
    </div>
</form>
<p class="small text-muted">{{ $users->total() }} matching {{ str('account')->plural($users->total()) }} · Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</p>
<div class="table-responsive cbis-users-table">
<table class="table bg-white align-middle">
    <thead><tr><th>Account</th><th>Contact</th><th>Facility</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    @forelse($users as $user)
        <tr>
            <td><div class="fw-semibold">{{ $user->name }}</div><small class="text-muted">{{ $user->hasAnyRole(['Quality Assurance Officer', 'Event Facilitator', 'Blood Bank Staff']) ? 'Staff account' : 'Public account' }}</small></td>
            <td><div class="text-break">{{ $user->email }}</div><small class="text-muted">{{ $user->phone ?: 'No phone recorded' }}</small></td>
            <td>{{ $user->facility->name ?? 'Not assigned' }}</td>
            <td>{{ $user->hasAllRoles(['Donor','Patient']) ? $user->getRoleNames()->reject(fn ($role) => in_array($role, ['Donor','Patient']))->push('Patient/Donor')->implode(', ') : $user->getRoleNames()->implode(', ') }}</td>
            <td>
                <span class="badge {{ $user->is_active ? 'cbis-status-active' : 'cbis-status-expired' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td class="cbis-account-actions">
                @if($canEditStaff)
                    <a href="{{ route('staff-users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    @if(! $user->is($currentUser))
                        <form
                            method="POST"
                            action="{{ route('staff-users.status', $user) }}"
                            class="d-inline js-confirm-action"
                            data-confirm-title="{{ $user->is_active ? 'Deactivate account?' : 'Reactivate account?' }}"
                            data-confirm-message="{{ $user->is_active ? 'This will prevent '.$user->name.' from logging in, but their old records will stay in the system.' : 'This will allow '.$user->name.' to log in again using their existing account.' }}"
                            data-confirm-button="{{ $user->is_active ? 'Deactivate Account' : 'Reactivate Account' }}"
                            data-confirm-variant="{{ $user->is_active ? 'danger' : 'success' }}"
                        >
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                            <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                            </button>
                        </form>
                    @endif
                @else
                    <span class="text-muted small">View only</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center py-5 text-muted">No users match these filters. Try another search or reset the filters.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $users->links() }}
@endsection
