@extends('layouts.app')

@section('content')
@php
    $currentUser = auth('web')->user();
    $canCreateStaff = ($currentUser?->isCentralAdmin() ?? false) || ($currentUser?->can('manage users') ?? false);
    $canEditStaff = ! ($currentUser?->isCentralAdmin() ?? false) && ($currentUser?->can('manage users') ?? false);
@endphp
<div class="d-flex justify-content-between mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Staff Accounts</h1>
        <p class="cbis-page-subtitle">Create and monitor authorized facility users.</p>
    </div>
    @if($canCreateStaff)
        <a href="{{ route('staff-users.create') }}" class="btn btn-danger">Create Staff Account</a>
    @endif
</div>
<div class="table-responsive">
<table class="table table-striped bg-white">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Facility</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone ?? '-' }}</td>
            <td>{{ $user->facility->name ?? '-' }}</td>
            <td>{{ $user->getRoleNames()->implode(', ') }}</td>
            <td>
                <span class="badge {{ $user->is_active ? 'cbis-status-active' : 'cbis-status-expired' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td class="text-nowrap">
                @if($canEditStaff)
                    <a href="{{ route('staff-users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    @if(! $user->is($currentUser))
                        <form
                            method="POST"
                            action="{{ route('staff-users.status', $user) }}"
                            class="d-inline js-confirm-action"
                            data-confirm-title="{{ $user->is_active ? 'Deactivate staff account?' : 'Reactivate staff account?' }}"
                            data-confirm-message="{{ $user->is_active ? 'This will prevent '.$user->name.' from logging in, but their old records will stay in the system.' : 'This will allow '.$user->name.' to log in again using their existing account.' }}"
                            data-confirm-button="{{ $user->is_active ? 'Deactivate Staff' : 'Reactivate Staff' }}"
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
    @endforeach
    </tbody>
</table>
</div>
{{ $users->links() }}
@endsection
