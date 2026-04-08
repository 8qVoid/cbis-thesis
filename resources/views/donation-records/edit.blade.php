@extends('layouts.app')
@section('content')
<h4>Edit Donation Record</h4>
<form method="POST" action="{{ route('donation-records.update',$donationRecord) }}" class="card card-body">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Donation No</label><input name="donation_no" class="form-control" value="{{ old('donation_no',$donationRecord->donation_no) }}" required></div>
<div class="col-md-4"><label class="form-label">Donated At</label><input name="donated_at" type="datetime-local" class="form-control" value="{{ old('donated_at',$donationRecord->donated_at?->format('Y-m-d\TH:i')) }}" required></div>
<div class="col-md-4"><label class="form-label">Volume (ml)</label><input name="volume_ml" type="number" class="form-control" value="{{ old('volume_ml',$donationRecord->volume_ml) }}" required></div>
<div class="col-12"><button class="btn btn-danger">Update</button></div>
</div></form>
@endsection
