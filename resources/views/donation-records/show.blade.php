@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Donation record</div><h1 class="cbis-page-title mb-0">{{ $donationRecord->donation_no }}</h1></div><a href="{{ route('donation-records.index') }}" class="btn btn-outline-secondary">Back to records</a></div>
<section class="card"><div class="card-body"><dl class="cbis-record-details mb-0">
<div><dt>Donor</dt><dd>{{ $donationRecord->donor->full_name ?? 'Not recorded' }}</dd></div>
<div><dt>Blood type</dt><dd>{{ $donationRecord->blood_type }}</dd></div>
<div><dt>Volume</dt><dd>{{ $donationRecord->volume_ml.' ml' }}</dd></div>
<div><dt>Donation date</dt><dd>{{ $donationRecord->donated_at?->format('M j, Y · g:i A') }}</dd></div>
<div><dt>Expiration date</dt><dd>{{ $donationRecord->expiration_date?->format('M j, Y') }}</dd></div>
</dl></div></section>
@endsection
