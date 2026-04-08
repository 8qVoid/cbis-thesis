@extends('layouts.app')
@section('content')
<h4>Add Donor</h4>
<form method="POST" action="{{ route('donors.store') }}" class="card card-body">
@csrf
<div class="row g-3">
@if(auth('web')->user()?->isCentralAdmin())<div class="col-md-4"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}">{{ $facility->name }}</option>@endforeach</select></div>@endif
<div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option>male</option><option>female</option></select></div>
<div class="col-md-4"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Contact</label><input name="contact_number" class="form-control" placeholder="+63 917 123 4567 or 09171234567"></div>
<div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
<div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control"></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
</div>
</form>
@endsection
