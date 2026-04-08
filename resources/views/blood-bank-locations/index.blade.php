@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-3"><div><h1 class="cbis-page-title mb-0">Blood Bank Locations</h1><p class="cbis-page-subtitle">Map points for facilities and related donation activities.</p></div><a href="{{ route('blood-bank-locations.create') }}" class="btn btn-danger">Add Location</a></div>
<div class="table-responsive">
<table class="table table-striped bg-white"><thead><tr><th>Facility</th><th>Address</th><th>Coordinates</th><th>Action</th></tr></thead><tbody>
@foreach($locations as $location)
<tr><td>{{ $location->facility->name ?? '-' }}</td><td>{{ $location->address }}</td><td>{{ $location->latitude }}, {{ $location->longitude }}</td><td><a href="{{ route('blood-bank-locations.show',$location) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@endforeach
</tbody></table></div>{{ $locations->links() }}
@endsection
