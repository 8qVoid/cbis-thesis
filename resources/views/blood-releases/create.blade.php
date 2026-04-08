@extends('layouts.app')
@section('content')
<h4>Record Blood Release</h4>
<form method="POST" action="{{ route('blood-releases.store') }}" class="card card-body">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Inventory</label><select name="blood_inventory_id" class="form-select">@foreach($inventory as $item)<option value="{{ $item->id }}">{{ $item->blood_type }} - {{ $item->units_available }} units</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units Released</label><input type="number" name="units_released" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Released At</label><input type="datetime-local" name="released_at" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Patient Name</label><input name="patient_name" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Requesting Unit</label><input name="requesting_unit" class="form-control"></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
</div></form>
@endsection
