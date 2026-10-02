@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Blood bank location</div><h1 class="cbis-page-title mb-0">{{ $bloodBankLocation->facility?->name ?? 'Facility location' }}</h1><p class="cbis-page-subtitle">Public location information and map coordinates.</p></div><a href="{{ route('blood-bank-locations.index') }}" class="btn btn-outline-secondary">Back to locations</a></div>
<div class="card card-body">
@if($bloodBankLocation->photo_path)
<img src="{{ asset('storage/'.$bloodBankLocation->photo_path) }}" alt="{{ $bloodBankLocation->facility?->name ?? 'Location photo' }}" class="cbis-detail-photo mb-3">
@endif
<dl class="cbis-record-details mb-3"><div><dt>Facility</dt><dd>{{ $bloodBankLocation->facility->name ?? 'Not assigned' }}</dd></div><div><dt>Contact number</dt><dd>{{ $bloodBankLocation->contact_number ?: 'Not recorded' }}</dd></div><div class="cbis-detail-span"><dt>Address</dt><dd>{{ $bloodBankLocation->address }}</dd></div><div class="cbis-detail-span"><dt>Coordinates</dt><dd>{{ $bloodBankLocation->latitude }}, {{ $bloodBankLocation->longitude }}</dd></div></dl>
<div class="cbis-form-actions"><a href="{{ route('blood-bank-locations.edit',$bloodBankLocation) }}" class="btn btn-danger">Edit location</a><form method="POST" action="{{ route('blood-bank-locations.destroy',$bloodBankLocation) }}" class="js-confirm-action" data-confirm-title="Delete location?" data-confirm-message="This will remove the facility location from the system." data-confirm-button="Delete location" data-confirm-variant="danger">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button></form></div>
</div>
@endsection
