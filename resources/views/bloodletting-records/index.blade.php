@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-3"><div><h1 class="cbis-page-title mb-0">Bloodletting Records</h1><p class="cbis-page-subtitle">Facility staff verification and findings records.</p></div><a href="{{ route('bloodletting-records.create') }}" class="btn btn-danger">Add Record</a></div>
<div class="table-responsive">
<table class="table table-striped bg-white"><thead><tr><th>Donation No.</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($records as $record)
<tr><td>{{ $record->donationRecord->donation_no ?? '-' }}</td><td>{{ $record->bloodletting_at?->format('Y-m-d H:i') }}</td><td><span class="badge {{ $record->verification_status === 'verified' ? 'cbis-status-active' : ($record->verification_status === 'rejected' ? 'cbis-status-expired' : 'cbis-status-low') }}">{{ ucfirst($record->verification_status) }}</span></td><td><a href="{{ route('bloodletting-records.show',$record) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@endforeach
</tbody></table></div>{{ $records->links() }}
@endsection
