@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Bloodletting record</div><h1 class="cbis-page-title mb-0">{{ $bloodlettingRecord->donationRecord->donation_no ?? 'Record #'.$bloodlettingRecord->id }}</h1></div><a href="{{ route('bloodletting-records.index') }}" class="btn btn-outline-secondary">Back to records</a></div>
<section class="card"><div class="card-body"><dl class="cbis-record-details mb-0">
<div><dt>Verification status</dt><dd>{{ str($bloodlettingRecord->verification_status)->headline() }}</dd></div>
<div><dt>Date</dt><dd>{{ $bloodlettingRecord->bloodletting_at?->format('M j, Y · g:i A') }}</dd></div>
<div><dt>Findings</dt><dd>{{ $bloodlettingRecord->findings ?: 'No findings recorded.' }}</dd></div>
</dl></div></section>
@endsection
