@extends('layouts.app')
@section('content')
<h1 class="cbis-page-title">Request Blood</h1><p class="text-muted">Upload clear copies. Blood Bank Staff may require the physical originals during processing or collection.</p>
<p class="alert alert-info">All blood requests are processed by the Bacolod main chapter. Branches organize activities only.</p>
<form method="POST" action="{{ route('reservations.store') }}" enctype="multipart/form-data" class="card"><div class="card-body"><div class="row g-3">@csrf
<div class="col-md-6"><label class="form-label">Red Cross Branch</label><select name="facility_id" class="form-select" required><option value="">Select branch</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected(old('facility_id')==$facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select" required>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option @selected(old('blood_type')===$type)>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units</label><input type="number" min="1" max="20" name="units_requested" value="{{ old('units_requested',1) }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Blood Component</label><select name="component" class="form-select" required>@foreach(\App\Models\BloodInventory::COMPONENTS as $value=>$label)<option value="{{ $value }}" @selected(old('component')===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Date Needed</label><input type="date" name="needed_on" min="{{ now()->toDateString() }}" value="{{ old('needed_on') }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Clinical Purpose (optional)</label><textarea name="clinical_purpose" class="form-control" rows="2">{{ old('clinical_purpose') }}</textarea></div>
<div class="col-12"><h2 class="h5">Required documents</h2><p class="text-muted mb-0">Your doctor’s blood request is required every time. Your ID can be reused from your profile or replaced with a new upload.</p></div>
<div class="col-md-6">
    <label for="identification" class="form-label">1. Government or student ID</label>
    @if($identityDocument)
        <div class="form-check border rounded p-3 ps-5 mb-2 bg-light">
            <input class="form-check-input" type="checkbox" name="use_saved_identification" id="use_saved_identification" value="1" checked>
            <label class="form-check-label" for="use_saved_identification">Use saved ID: <span class="text-break">{{ $identityDocument->original_name }}</span></label>
        </div>
        <small class="text-muted d-block mb-2">Upload a new ID below only if you want to replace the saved copy for this request and future requests.</small>
    @else
        <small class="text-muted d-block mb-2">No saved ID yet. Upload one now; it will be saved to your profile for next time.</small>
    @endif
    <input id="identification" type="file" name="identification" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @required(! $identityDocument) aria-describedby="identification_help">
    <small id="identification_help" class="text-muted">Upload one clear photo or PDF; maximum 5 MB.</small>
    @error('identification')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="col-md-6"><label for="blood_request" class="form-label">2. Doctor's blood request / prescription</label><input id="blood_request" type="file" name="blood_request" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required aria-describedby="blood_request_help"><small id="blood_request_help" class="text-muted">Upload one clear photo or PDF; maximum 5 MB.</small>@error('blood_request')<div class="text-danger">{{ $message }}</div>@enderror</div>
<div class="col-12"><button class="btn btn-danger">Submit Reservation</button></div></div></div></form>
@endsection
