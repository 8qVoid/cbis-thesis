@extends('layouts.app')
@section('content')
<div class="card card-body">
    <p><strong>Blood Type:</strong> {{ $bloodInventory->blood_type }}</p>
    <p><strong>Units:</strong> {{ $bloodInventory->units_available }}</p>
    <p><strong>Expiration:</strong> {{ $bloodInventory->expiration_date?->toDateString() }}</p>
    <p><strong>Source:</strong> {{ $bloodInventory->donationRecord ? 'Donation record '.$bloodInventory->donationRecord->donation_no : 'Manual adjustment' }}</p>
    <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $bloodInventory->status)) }}</p>
</div>
@endsection
