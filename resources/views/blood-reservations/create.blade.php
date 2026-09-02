@extends('layouts.app')
@section('content')
<h1 class="cbis-page-title">Request Blood</h1><p class="text-muted">Upload clear copies. Blood Bank Staff may require the physical originals during processing or collection.</p>
<form method="POST" action="{{ route('reservations.store') }}" enctype="multipart/form-data" class="card"><div class="card-body"><div class="row g-3">@csrf
<div class="col-md-6"><label class="form-label">Red Cross Branch</label><select name="facility_id" class="form-select" required><option value="">Select branch</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected(old('facility_id')==$facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select" required>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option @selected(old('blood_type')===$type)>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units</label><input type="number" min="1" max="20" name="units_requested" value="{{ old('units_requested',1) }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Blood Component</label><select name="component" class="form-select" required>@foreach(\App\Models\BloodInventory::COMPONENTS as $value=>$label)<option value="{{ $value }}" @selected(old('component')===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Date Needed</label><input type="date" name="needed_on" min="{{ now()->toDateString() }}" value="{{ old('needed_on') }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Clinical Purpose (optional)</label><textarea name="clinical_purpose" class="form-control" rows="2">{{ old('clinical_purpose') }}</textarea></div>
@foreach(['blood_request'=>'Blood request','prescription'=>"Doctor's prescription",'identification'=>'Government or student ID','referral_letter'=>'Referral letter (if applicable)'] as $field=>$label)<div class="col-md-6"><label class="form-label">{{ $label }}</label><input type="file" name="{{ $field }}" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @required($field!=='referral_letter')><small class="text-muted">PDF, JPG or PNG; maximum 5 MB.</small></div>@endforeach
<div class="col-12"><button class="btn btn-danger">Submit Reservation</button></div></div></div></form>
@endsection
