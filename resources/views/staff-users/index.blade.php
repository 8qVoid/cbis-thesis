@extends('layouts.app')

@section('content')
@php
    $currentUser = auth('web')->user();
    $canCreateStaff = ! ($currentUser?->isCentralAdmin() ?? false) && ($currentUser?->can('manage users') ?? false);
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
    <thead><tr><th>Name</th><th>Email</th><th>Facility</th><th>Role</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->facility->name ?? '-' }}</td>
            <td>{{ $user->getRoleNames()->implode(', ') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
{{ $users->links() }}
@endsection
