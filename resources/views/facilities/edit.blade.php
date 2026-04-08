@extends('layouts.app')
@section('content')
<h4 class="mb-3">Edit Facility</h4>
<form method="POST" action="{{ route('facilities.update',$facility) }}" class="card card-body">
@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Code</label><input name="code" class="form-control" value="{{ old('code',$facility->code) }}" required></div>
<div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name',$facility->name) }}" required></div>
<div class="col-md-4"><label class="form-label">Type</label><input name="type" class="form-control" value="{{ old('type',$facility->type) }}" required></div>
<div class="col-md-4"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control" value="{{ old('contact_person',$facility->contact_person) }}"></div>
<div class="col-md-4"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number',$facility->contact_number) }}" placeholder="+63 917 123 4567 or 09171234567"></div>
<div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email',$facility->email) }}"></div>
<div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address',$facility->address) }}"></div>
<div class="col-md-2"><label class="form-label">Active</label><select name="is_active" class="form-select"><option value="1" @selected($facility->is_active)>Yes</option><option value="0" @selected(!$facility->is_active)>No</option></select></div>
<div class="col-12"><button class="btn btn-danger">Update</button></div>
</div>
</form>
@endsection
