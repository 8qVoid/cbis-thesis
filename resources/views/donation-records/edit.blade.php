@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Donation {{ $donationRecord->donation_no }}</div><h1 class="cbis-page-title mb-0">Edit donation record</h1><p class="cbis-page-subtitle">Update collection time and recorded volume.</p></div><a href="{{ route('donation-records.index') }}" class="btn btn-outline-secondary">Back to donations</a></div>
<form method="POST" action="{{ route('donation-records.update',$donationRecord) }}" class="card card-body cbis-compact-form cbis-legacy-form">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Donation No</label><input name="donation_no" class="form-control" value="{{ old('donation_no',$donationRecord->donation_no) }}" required></div>
<div class="col-md-4"><label class="form-label">Donated At</label><input name="donated_at" type="datetime-local" class="form-control" value="{{ old('donated_at',$donationRecord->donated_at?->format('Y-m-d\TH:i')) }}" required></div>
<div class="col-md-4"><label class="form-label">Volume (ml)</label><input name="volume_ml" type="number" class="form-control" value="{{ old('volume_ml',$donationRecord->volume_ml) }}" required></div>
<div class="col-12 cbis-form-actions"><a href="{{ route('donation-records.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Update record</button></div>
</div></form>
@endsection
