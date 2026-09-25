@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageBloodReleases = $currentUser?->can('manage blood releases') ?? false;
@endphp
<div class="cbis-page-heading">
    <div>
        <h1 class="cbis-page-title mb-0">Blood Releases</h1>
        <p class="cbis-page-subtitle">Review blood released to patients and the corresponding stock deductions.</p>
    </div>
    @if($canManageBloodReleases)
        <a href="{{ route('blood-releases.create') }}" class="btn btn-danger">Record Release</a>
    @endif
</div>
<div class="card cbis-record-table"><div class="table-responsive">
<table class="table table-hover align-middle mb-0"><thead><tr><th>Blood Type</th><th>Units</th><th>Date</th><th>Patient</th><th>Action</th></tr></thead><tbody>
@forelse($releases as $release)
<tr><td><strong>{{ $release->inventory->blood_type ?? 'Not recorded' }}</strong><small class="d-block text-muted">{{ $release->inventory?->component_label }}</small></td><td>{{ $release->units_released }}</td><td>{{ $release->released_at?->format('M j, Y · g:i A') }}</td><td>{{ $release->patient_name }}</td><td><a href="{{ route('blood-releases.show',$release) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@empty
<tr><td colspan="5"><div class="cbis-empty-state"><strong>No blood releases yet</strong><span>Recorded releases will appear here with the patient, quantity, and date.</span></div></td></tr>
@endforelse
</tbody></table></div>@if($releases->hasPages())<div class="card-footer bg-white">{{ $releases->links() }}</div>@endif</div>
@endsection
