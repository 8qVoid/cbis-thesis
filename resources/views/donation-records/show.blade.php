@extends('layouts.app')
@section('content')
<h4>Donation Record Detail</h4>
<div class="card card-body">
<p><strong>Donation No:</strong> {{ $donationRecord->donation_no }}</p>
<p><strong>Donor:</strong> {{ $donationRecord->donor->full_name ?? '-' }}</p>
<p><strong>Blood Type:</strong> {{ $donationRecord->blood_type }}</p>
<p><strong>Volume:</strong> {{ $donationRecord->volume_ml }} ml</p>
<p><strong>Expiration:</strong> {{ $donationRecord->expiration_date?->toDateString() }}</p>
</div>
@endsection
