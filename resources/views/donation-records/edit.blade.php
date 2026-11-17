@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Donation {{ $donationRecord->donation_no }}</div><h1 class="cbis-page-title mb-0">Edit donation record</h1><p class="cbis-page-subtitle">Update collection time and recorded volume.</p></div><a href="{{ route('donation-records.index') }}" class="btn btn-outline-secondary">Back to donations</a></div>
<form method="POST" action="{{ route('donation-records.update',$donationRecord) }}" class="card card-body cbis-compact-form cbis-legacy-form">@csrf @method('PUT')
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<input type="hidden" name="facility_id" value="{{ $donationRecord->facility_id }}">
<input type="hidden" name="donor_id" value="{{ $donationRecord->donor_id }}">
<input type="hidden" name="blood_type" value="{{ $donationRecord->blood_type }}">
<input type="hidden" name="expiration_date" value="{{ $donationRecord->expiration_date?->toDateString() }}">
<input type="hidden" name="status" value="{{ $donationRecord->status }}">
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Donation No</label><input name="donation_no" class="form-control" value="{{ old('donation_no',$donationRecord->donation_no) }}" required></div>
<div class="col-md-4"><label class="form-label">Donated At</label><input name="donated_at" type="datetime-local" class="form-control" value="{{ old('donated_at',$donationRecord->donated_at?->format('Y-m-d\TH:i')) }}" required></div>
<div class="col-md-4"><label class="form-label">Volume (ml)</label><input name="volume_ml" type="number" class="form-control" value="{{ old('volume_ml',$donationRecord->volume_ml) }}" required></div>
<div class="col-md-4"><label class="form-label">Expiration time (PHT)</label><input name="expiration_time" type="time" class="form-control" value="{{ old('expiration_time', substr($donationRecord->expiration_time ?: '23:59', 0, 5)) }}" required @readonly($donationRecord->inventory()->withTrashed()->exists())><small class="text-muted">{{ $donationRecord->inventory()->withTrashed()->exists() ? 'Linked inventory has already been created; edit its expiration there.' : 'Enter the time on the blood bag label.' }}</small></div>
<div class="col-12 cbis-form-actions"><a href="{{ route('donation-records.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Update record</button></div>
</div></form>
@endsection
