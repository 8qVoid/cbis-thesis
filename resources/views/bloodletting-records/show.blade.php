@extends('layouts.app')
@section('content')
<div class="card card-body"><p><strong>Donation:</strong> {{ $bloodlettingRecord->donationRecord->donation_no ?? '-' }}</p><p><strong>Status:</strong> {{ $bloodlettingRecord->verification_status }}</p><p><strong>Findings:</strong> {{ $bloodlettingRecord->findings }}</p></div>
@endsection
