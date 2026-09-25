@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h1 class="cbis-page-title mb-0">Edit Inventory Adjustment</h1>
    <p class="cbis-page-subtitle">Correct the unit balance, expiration date, or status. The stock type and facility stay linked to this record.</p>
</div>
<form method="POST" action="{{ route('blood-inventory.update',$bloodInventory) }}" class="card card-body">@csrf @method('PUT')
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-light border">Available stock must expire today or later. Select Expired only for stock whose expiration date has passed. Update existing manual stock when the blood type, component, and expiry match.</div>
<div class="row g-3">
@if(auth('web')->user()?->isCentralAdmin())<div class="col-md-4"><label class="form-label">Facility</label><input type="hidden" name="facility_id" value="{{ $bloodInventory->facility_id }}"><select disabled aria-label="facility id" class="form-select" required><option value="">Select facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected(old('facility_id',$bloodInventory->facility_id) == $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>@endif
<div class="col-md-3"><label class="form-label">Blood Type</label><input type="hidden" name="blood_type" value="{{ $bloodInventory->blood_type }}"><select disabled aria-label="blood type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option value="{{ $type }}" @selected(old('blood_type', $bloodInventory->blood_type)===$type)>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Blood Component</label><input type="hidden" name="component" value="{{ $bloodInventory->component }}"><select disabled aria-label="component" class="form-select">@foreach(\App\Models\BloodInventory::COMPONENTS as $value=>$label)<option value="{{ $value }}" @selected(old('component', $bloodInventory->component)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units</label><input name="units_available" type="number" min="0" max="100000" step="1" class="form-control" value="{{ old('units_available',$bloodInventory->units_available) }}" required></div>
<div class="col-md-3"><label class="form-label">Expiration Date</label><input name="expiration_date" type="date" class="form-control" value="{{ old('expiration_date',$bloodInventory->expiration_date?->toDateString()) }}" required></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status', $bloodInventory->status)==='active')>In storage</option><option value="low_stock" @selected(old('status', $bloodInventory->status)==='low_stock')>Low stock</option><option value="expired" @selected(old('status', $bloodInventory->status)==='expired')>expired</option></select></div>
<div class="col-12 d-flex flex-wrap gap-2 pt-2"><a href="{{ route('blood-inventory.index') }}#stock-batches" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Update Adjustment</button></div>
</div></form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const date = document.querySelector('[name="expiration_date"]');
    const status = document.querySelector('[name="status"]');
    const update = () => {
        if (status.value === 'expired') {
            date.removeAttribute('min'); date.max = '{{ today()->subDay()->toDateString() }}';
        } else {
            date.removeAttribute('max'); date.min = '{{ today()->toDateString() }}';
        }
    };
    status.addEventListener('change', update); update();
});
</script>
@endsection
