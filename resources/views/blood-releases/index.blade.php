@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageBloodReleases = ! ($currentUser?->isCentralAdmin() ?? false) && ($currentUser?->can('manage blood releases') ?? false);
@endphp
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Blood Releases</h1>
        <p class="cbis-page-subtitle">Track blood usage and decrement inventory accurately.</p>
    </div>
    @if($canManageBloodReleases)
        <a href="{{ route('blood-releases.create') }}" class="btn btn-danger">Record Release</a>
    @endif
</div>
<div class="table-responsive">
<table class="table table-striped bg-white"><thead><tr><th>Blood Type</th><th>Units</th><th>Date</th><th>Patient</th><th>Action</th></tr></thead><tbody>
@foreach($releases as $release)
<tr><td>{{ $release->inventory->blood_type ?? '-' }}</td><td>{{ $release->units_released }}</td><td>{{ $release->released_at?->format('Y-m-d H:i') }}</td><td>{{ $release->patient_name }}</td><td><a href="{{ route('blood-releases.show',$release) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@endforeach
</tbody></table></div>{{ $releases->links() }}
@endsection
