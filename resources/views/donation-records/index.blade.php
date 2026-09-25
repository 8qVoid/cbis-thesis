@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageDonationRecords = $currentUser?->can('manage donation records') ?? false;
@endphp
<div class="cbis-page-heading">
    <div>
        <h1 class="cbis-page-title mb-0">Donation Records</h1>
        <p class="cbis-page-subtitle">Track donations and their linked inventory records.</p>
    </div>
    @if($canManageDonationRecords)
        <a href="{{ route('donation-records.create') }}" class="btn btn-danger">Add Donation Record</a>
    @endif
</div>
<div class="card cbis-record-table"><div class="table-responsive">
<table class="table table-hover align-middle mb-0"><thead><tr><th>Donation reference</th><th>Donor</th><th>Blood type</th><th>Volume</th><th>Date</th><th>Action</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>{{ $record->donation_no }}</td><td>{{ $record->donor->full_name ?? '-' }}</td><td>{{ $record->blood_type }}</td><td>{{ $record->volume_ml }} ml</td><td>{{ $record->donated_at?->format('M j, Y · g:i A') }}</td><td class="cbis-table-actions"><a href="{{ route('donation-records.show',$record) }}" class="btn btn-sm btn-outline-secondary">View</a> @if($canManageDonationRecords)<a href="{{ route('donation-records.edit',$record) }}" class="btn btn-sm btn-outline-primary">Edit</a>@endif</td></tr>
@empty
<tr><td colspan="6"><div class="cbis-empty-state"><strong>No donation records yet</strong><span>Verified donations will appear here and add stock to inventory.</span></div></td></tr>
@endforelse
</tbody></table></div>@if($records->hasPages())<div class="card-footer bg-white">{{ $records->links() }}</div>@endif</div>
@endsection
