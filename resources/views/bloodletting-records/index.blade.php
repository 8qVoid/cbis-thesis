@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageBloodlettingRecords = $currentUser?->can('manage bloodletting records') ?? false;
@endphp
<div class="cbis-page-heading">
    <div>
        <h1 class="cbis-page-title mb-0">Bloodletting Records</h1>
        <p class="cbis-page-subtitle">Review staff verification and recorded findings.</p>
    </div>
    @if($canManageBloodlettingRecords)
        <a href="{{ route('bloodletting-records.create') }}" class="btn btn-danger">Add Record</a>
    @endif
</div>
<div class="card cbis-record-table"><div class="table-responsive">
<table class="table table-hover align-middle mb-0"><thead><tr><th>Donation No.</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>{{ $record->donationRecord->donation_no ?? '-' }}</td><td>{{ $record->bloodletting_at?->format('M j, Y · g:i A') }}</td><td><span class="badge {{ $record->verification_status === 'verified' ? 'cbis-status-active' : ($record->verification_status === 'rejected' ? 'cbis-status-expired' : 'cbis-status-low') }}">{{ ucfirst($record->verification_status) }}</span></td><td><a href="{{ route('bloodletting-records.show',$record) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@empty
<tr><td colspan="4"><div class="cbis-empty-state"><strong>No bloodletting records yet</strong><span>Staff verification records and findings will appear here.</span></div></td></tr>
@endforelse
</tbody></table></div>@if($records->hasPages())<div class="card-footer bg-white">{{ $records->links() }}</div>@endif</div>
@endsection
