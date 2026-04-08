@extends('layouts.app')
@section('content')
<h4>Edit Donor</h4>
<form method="POST" action="{{ route('donors.update',$donor) }}" class="card card-body">@csrf @method('PUT')
<div class="row g-3">
@if(auth('web')->user()?->isCentralAdmin())<div class="col-md-4"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected($donor->facility_id==$facility->id)>{{ $facility->name }}</option>@endforeach</select></div>@endif
<div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" class="form-control" value="{{ old('first_name',$donor->first_name) }}" required></div>
<div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" class="form-control" value="{{ old('last_name',$donor->last_name) }}" required></div>
<div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control" value="{{ old('birth_date',$donor->birth_date?->toDateString()) }}" required></div>
<div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option value="male" @selected($donor->sex==='male')>male</option><option value="female" @selected($donor->sex==='female')>female</option></select></div>
<div class="col-md-4"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option value="{{ $type }}" @selected($donor->blood_type===$type)>{{ $type }}</option>@endforeach</select></div>
<div class="col-12"><button class="btn btn-danger">Update</button></div>
</div></form>
@endsection
