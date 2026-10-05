@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h1 class="cbis-page-title mb-0">Add Manual Inventory Adjustment</h1>
    <p class="cbis-page-subtitle">Use this only for corrections or non-donation stock adjustments. Saved donation records already add to inventory automatically.</p>
</div>
<form method="POST" action="{{ route('blood-inventory.store') }}" class="card card-body">@csrf
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-light border">One unit equals one blood bag. Use one record per bag when expiration dates differ; use a quantity greater than one only when all bags share the same expiration date. Available stock must expire today or later.</div>
<div class="row g-3">
@if(auth('web')->user()?->isCentralAdmin())<div class="col-md-4"><label class="form-label">Facility</label><select name="facility_id" class="form-select" required><option value="">Select facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected(old('facility_id') == $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>@endif
<div class="col-md-3"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option @selected(old('blood_type') === $type)>{{ $type }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Blood Component</label><select name="component" class="form-select">@foreach(\App\Models\BloodInventory::COMPONENTS as $value=>$label)<option value="{{ $value }}" @selected(old('component') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Blood bags</label><input name="units_available" value="{{ old('units_available') }}" type="number" min="0" max="100000" step="1" class="form-control" required><small class="form-text text-muted">Each bag counts as one unit.</small></div>
<div class="col-md-3"><label class="form-label">Expiration Date</label><input name="expiration_date" value="{{ old('expiration_date') }}" type="date" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status') === 'active')>In storage</option><option value="low_stock" @selected(old('status') === 'low_stock')>Low stock</option><option value="expired" @selected(old('status') === 'expired')>Expired</option></select></div>
<div class="col-12 d-flex flex-wrap gap-2 pt-2"><a href="{{ route('blood-inventory.index') }}#stock-batches" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Save Adjustment</button></div>
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
