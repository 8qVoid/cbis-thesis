@extends('layouts.app')
@section('content')
<h4>Record Blood Release</h4>
<form method="POST" action="{{ route('blood-releases.store') }}" class="card card-body">@csrf
<div class="row g-3">
@if($inventory->isEmpty())
<div class="col-12"><div class="alert alert-warning mb-0">No active inventory units are available for release.</div></div>
@else
<div class="col-md-6"><label class="form-label">Inventory</label><select name="blood_inventory_id" id="releaseInventorySelect" class="form-select">@foreach($inventory as $item)<option value="{{ $item->id }}" data-units="{{ $item->units_available }}">{{ $item->blood_type }} - {{ $item->units_available }} units @if(auth('web')->user()?->isCentralAdmin()) - {{ $item->facility->name ?? 'No facility' }} @endif</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units Released</label><input type="number" name="units_released" id="releaseUnitsInput" min="1" max="{{ $inventory->first()?->units_available }}" class="form-control" required><small class="text-muted" id="releaseUnitsHelp">Available: {{ $inventory->first()?->units_available }} units</small></div>
<div class="col-md-3"><label class="form-label">Released At</label><input type="datetime-local" name="released_at" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Patient Name</label><input name="patient_name" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Requesting Unit</label><input name="requesting_unit" class="form-control"></div>
<div class="col-12"><button class="btn btn-danger">Save</button></div>
@endif
</div></form>
@if($inventory->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const inventorySelect = document.getElementById('releaseInventorySelect');
        const unitsInput = document.getElementById('releaseUnitsInput');
        const unitsHelp = document.getElementById('releaseUnitsHelp');

        const syncAvailableUnits = () => {
            const selected = inventorySelect.options[inventorySelect.selectedIndex];
            const available = selected?.dataset.units || '1';

            unitsInput.max = available;
            unitsHelp.textContent = `Available: ${available} units`;

            if (Number(unitsInput.value) > Number(available)) {
                unitsInput.value = available;
            }
        };

        inventorySelect.addEventListener('change', syncAvailableUnits);
        syncAvailableUnits();
    });
</script>
@endif
@endsection
