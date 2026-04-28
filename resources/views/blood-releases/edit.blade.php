@extends('layouts.app')
@section('content')
<h4>Edit Blood Release</h4>
<form method="POST" action="{{ route('blood-releases.update',$bloodRelease) }}" class="card card-body">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Inventory</label><select name="blood_inventory_id" class="form-select">@foreach($inventory as $item)<option value="{{ $item->id }}" @selected($bloodRelease->blood_inventory_id==$item->id)>{{ $item->blood_type }} - {{ $item->units_available }} units @if(auth('web')->user()?->isCentralAdmin()) - {{ $item->facility->name ?? 'No facility' }} @endif</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units Released</label><input type="number" name="units_released" class="form-control" value="{{ old('units_released',$bloodRelease->units_released) }}" required></div>
<div class="col-md-3"><label class="form-label">Released At</label><input type="datetime-local" name="released_at" class="form-control" value="{{ old('released_at',$bloodRelease->released_at?->format('Y-m-d\TH:i')) }}" required></div>
<div class="col-12"><button class="btn btn-danger">Update</button></div>
</div></form>
@endsection
