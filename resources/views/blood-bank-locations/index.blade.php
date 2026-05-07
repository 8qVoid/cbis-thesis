@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-3"><div><h1 class="cbis-page-title mb-0">Blood Bank Locations</h1><p class="cbis-page-subtitle">Map point for your facility and related donation activities.</p></div>@if($existingLocation)<a href="{{ route('blood-bank-locations.edit',$existingLocation) }}" class="btn btn-danger">Edit Location</a>@else<a href="{{ route('blood-bank-locations.create') }}" class="btn btn-danger">Add Location</a>@endif</div>
<div class="table-responsive">
<table class="table table-striped bg-white"><thead><tr><th>Facility</th><th>Address</th><th>Coordinates</th><th>Action</th></tr></thead><tbody>
@forelse($locations as $location)
<tr><td>{{ $location->facility->name ?? '-' }}</td><td>{{ $location->address }}</td><td>{{ $location->latitude }}, {{ $location->longitude }}</td><td class="d-flex gap-2 flex-wrap"><a href="{{ route('blood-bank-locations.show',$location) }}" class="btn btn-sm btn-outline-secondary">View</a><a href="{{ route('blood-bank-locations.edit',$location) }}" class="btn btn-sm btn-outline-danger">Edit</a><form method="POST" action="{{ route('blood-bank-locations.destroy',$location) }}" onsubmit="return confirm('Delete this facility location?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Delete</button></form></td></tr>
@empty
<tr><td colspan="4" class="text-center text-muted">No facility location set yet.</td></tr>
@endforelse
</tbody></table></div>{{ $locations->links() }}
@endsection
