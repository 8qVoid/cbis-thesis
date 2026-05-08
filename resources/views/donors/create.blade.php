@extends('layouts.app')
@section('content')
<h4>Add Donor</h4>
<form method="POST" action="{{ route('donors.store') }}" class="card card-body">
@csrf
<div class="row g-3">
@if(auth('web')->user()?->isCentralAdmin())<div class="col-md-4"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}">{{ $facility->name }}</option>@endforeach</select></div>@endif
<div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+" required></div>
<div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+" required></div>
<div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option>male</option><option>female</option></select></div>
<div class="col-md-4"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Mobile Number</label><div class="input-group"><span class="input-group-text">09</span><input name="contact_number" class="form-control js-mobile-suffix" value="{{ \App\Support\PhilippinePhone::mobileSuffix(old('contact_number')) }}" inputmode="numeric" maxlength="9" pattern="\d{9}" placeholder="123456789"></div><small class="text-muted">Enter the 9 digits after 09.</small></div>
<div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
<div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control"></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
</div>
</form>
@endsection
