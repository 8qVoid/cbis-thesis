@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Facilities</h1>
        <p class="cbis-page-subtitle">Managed centrally by Philippine Red Cross. No public facility self-registration.</p>
    </div>
    <a href="{{ route('facilities.create') }}" class="btn btn-danger">Add Facility</a>
</div>
<div class="table-responsive"><table class="table table-bordered bg-white">
<thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
@foreach($facilities as $facility)
<tr>
<td>{{ $facility->code }}</td><td>{{ $facility->name }}</td><td>{{ $facility->type }}</td><td><span class="badge {{ $facility->is_active ? 'cbis-status-active' : 'cbis-status-expired' }}">{{ $facility->is_active ? 'Active' : 'Inactive' }}</span></td>
<td><a href="{{ route('facilities.show',$facility) }}" class="btn btn-sm btn-outline-secondary">View</a> <a href="{{ route('facilities.edit',$facility) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
</tr>
@endforeach
</tbody></table></div>
{{ $facilities->links() }}
@endsection
