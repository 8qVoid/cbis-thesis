@extends('layouts.app')
@section('content')
<div class="card card-body">
@if($bloodBankLocation->photo_path)
<img src="{{ Storage::disk('public')->url($bloodBankLocation->photo_path) }}" alt="{{ $bloodBankLocation->facility?->name ?? 'Location photo' }}" class="img-fluid rounded border mb-3" style="max-height: 280px; object-fit: cover;">
@endif
<p><strong>Facility:</strong> {{ $bloodBankLocation->facility->name ?? '-' }}</p><p><strong>Address:</strong> {{ $bloodBankLocation->address }}</p><p><strong>Coordinates:</strong> {{ $bloodBankLocation->latitude }}, {{ $bloodBankLocation->longitude }}</p>
</div>
@endsection
