@extends('layouts.app')
@section('content')
<h4>Add Bloodletting Record</h4>
<form method="POST" action="{{ route('bloodletting-records.store') }}" class="card card-body">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Donation Record</label><select name="donation_record_id" class="form-select">@foreach($donationRecords as $record)<option value="{{ $record->id }}">{{ $record->donation_no }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Date Time</label><input type="datetime-local" name="bloodletting_at" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="verification_status" class="form-select"><option>pending</option><option>verified</option><option>rejected</option></select></div>
<div class="col-12"><label class="form-label">Findings</label><textarea name="findings" class="form-control"></textarea></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
</div></form>
@endsection
