@extends('layouts.app')
@section('content')
<h4>Edit Inventory</h4>
<form method="POST" action="{{ route('blood-inventory.update',$bloodInventory) }}" class="card card-body">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option value="{{ $type }}" @selected($bloodInventory->blood_type===$type)>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units</label><input name="units_available" type="number" class="form-control" value="{{ old('units_available',$bloodInventory->units_available) }}" required></div>
<div class="col-md-3"><label class="form-label">Expiration Date</label><input name="expiration_date" type="date" class="form-control" value="{{ old('expiration_date',$bloodInventory->expiration_date?->toDateString()) }}" required></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected($bloodInventory->status==='active')>active</option><option value="low_stock" @selected($bloodInventory->status==='low_stock')>low_stock</option><option value="expired" @selected($bloodInventory->status==='expired')>expired</option></select></div>
<div class="col-12"><button class="btn btn-danger">Update</button></div>
</div></form>
@endsection
