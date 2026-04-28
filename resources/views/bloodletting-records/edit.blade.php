@extends('layouts.app')
@section('content')
<h4>Edit Bloodletting Record</h4>
<form method="POST" action="{{ route('bloodletting-records.update',$bloodlettingRecord) }}" class="card card-body">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Donation Record</label><select name="donation_record_id" class="form-select">@foreach($donationRecords as $record)<option value="{{ $record->id }}" @selected($bloodlettingRecord->donation_record_id==$record->id)>{{ $record->donation_no }} @if(auth('web')->user()?->isCentralAdmin()) - {{ $record->facility->name ?? 'No facility' }} @endif</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Date Time</label><input type="datetime-local" name="bloodletting_at" class="form-control" value="{{ old('bloodletting_at',$bloodlettingRecord->bloodletting_at?->format('Y-m-d\TH:i')) }}"></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="verification_status" class="form-select"><option value="pending" @selected($bloodlettingRecord->verification_status==='pending')>pending</option><option value="verified" @selected($bloodlettingRecord->verification_status==='verified')>verified</option><option value="rejected" @selected($bloodlettingRecord->verification_status==='rejected')>rejected</option></select></div>
<div class="col-12"><button class="btn btn-danger">Update</button></div>
</div></form>
@endsection
