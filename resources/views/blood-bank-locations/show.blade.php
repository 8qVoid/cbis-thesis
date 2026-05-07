@extends('layouts.app')
@section('content')
<div class="card card-body">
@if($bloodBankLocation->photo_path)
<img src="{{ asset('storage/'.$bloodBankLocation->photo_path) }}" alt="{{ $bloodBankLocation->facility?->name ?? 'Location photo' }}" class="img-fluid rounded border mb-3" style="max-height: 280px; object-fit: cover;">
@endif
<p><strong>Facility:</strong> {{ $bloodBankLocation->facility->name ?? '-' }}</p><p><strong>Address:</strong> {{ $bloodBankLocation->address }}</p><p><strong>Coordinates:</strong> {{ $bloodBankLocation->latitude }}, {{ $bloodBankLocation->longitude }}</p>
<div class="d-flex gap-2 flex-wrap"><a href="{{ route('blood-bank-locations.edit',$bloodBankLocation) }}" class="btn btn-danger">Edit Location</a><form method="POST" action="{{ route('blood-bank-locations.destroy',$bloodBankLocation) }}" onsubmit="return confirm('Delete this facility location?');">@csrf @method('DELETE')<button class="btn btn-outline-secondary">Delete</button></form><a href="{{ route('blood-bank-locations.index') }}" class="btn btn-outline-secondary">Back</a></div>
</div>
@endsection
