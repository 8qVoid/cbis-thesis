@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Bloodletting</div><h1 class="cbis-page-title mb-0">Add bloodletting record</h1><p class="cbis-page-subtitle">Link the collection assessment to an existing donation.</p></div><a href="{{ route('bloodletting-records.index') }}" class="btn btn-outline-secondary">Back to records</a></div>
<form method="POST" action="{{ route('bloodletting-records.store') }}" class="card card-body cbis-compact-form cbis-legacy-form">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Donation Record</label><select name="donation_record_id" class="form-select">@foreach($donationRecords as $record)<option value="{{ $record->id }}">{{ $record->donation_no }} @if(auth('web')->user()?->isCentralAdmin()) - {{ $record->facility->name ?? 'No facility' }} @endif</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Date Time</label><input type="datetime-local" name="bloodletting_at" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="verification_status" class="form-select"><option>pending</option><option>verified</option><option>rejected</option></select></div>
<div class="col-12"><label class="form-label">Findings</label><textarea name="findings" class="form-control"></textarea></div>
<div class="col-12 cbis-form-actions"><a href="{{ route('bloodletting-records.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Save record</button></div>
</div></form>
@endsection
