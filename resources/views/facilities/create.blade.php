@extends('layouts.app')
@section('content')
<h4 class="mb-3">Add Facility</h4>
<form method="POST" action="{{ route('facilities.store') }}" class="card card-body">
@csrf
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Code</label><input name="code" class="form-control" value="{{ old('code') }}" required></div>
<div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
<div class="col-md-4"><label class="form-label">Type</label><input name="type" class="form-control" value="{{ old('type','blood_bank') }}" required></div>
<div class="col-md-4"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control" value="{{ old('contact_person') }}"></div>
<div class="col-md-4"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number') }}" placeholder="+63 917 123 4567 or 09171234567"></div>
<div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email') }}"></div>
<div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
</div>
</form>
@endsection
