@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-3"><div><h1 class="cbis-page-title mb-0">Donation Records</h1><p class="cbis-page-subtitle">Real-time entries that add to blood inventory.</p></div><a href="{{ route('donation-records.create') }}" class="btn btn-danger">Add Donation</a></div>
<div class="table-responsive">
<table class="table table-striped bg-white"><thead><tr><th>No.</th><th>Donor</th><th>Blood</th><th>Volume</th><th>Date</th><th>Action</th></tr></thead><tbody>
@foreach($records as $record)
<tr><td>{{ $record->donation_no }}</td><td>{{ $record->donor->full_name ?? '-' }}</td><td>{{ $record->blood_type }}</td><td>{{ $record->volume_ml }} ml</td><td>{{ $record->donated_at?->format('Y-m-d H:i') }}</td><td><a href="{{ route('donation-records.show',$record) }}" class="btn btn-sm btn-outline-secondary">View</a> <a href="{{ route('donation-records.edit',$record) }}" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>
@endforeach
</tbody></table></div>{{ $records->links() }}
@endsection
