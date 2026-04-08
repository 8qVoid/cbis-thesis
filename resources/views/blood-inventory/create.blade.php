@extends('layouts.app')
@section('content')
<h4>Add Inventory</h4>
<form method="POST" action="{{ route('blood-inventory.store') }}" class="card card-body">@csrf
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units</label><input name="units_available" type="number" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Expiration Date</label><input name="expiration_date" type="date" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option>active</option><option>low_stock</option><option>expired</option></select></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
</div></form>
@endsection
