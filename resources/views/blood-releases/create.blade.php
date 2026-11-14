@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Inventory release</div><h1 class="cbis-page-title mb-0">Record blood release</h1><p class="cbis-page-subtitle">Select available stock and record where and when it was released.</p></div><a href="{{ route('blood-releases.index') }}" class="btn btn-outline-secondary">Back to releases</a></div>
<form method="POST" action="{{ route('blood-releases.store') }}" class="card card-body cbis-compact-form cbis-legacy-form">@csrf
<div class="row g-3">
@if($reservation)
<input type="hidden" name="blood_reservation_id" value="{{ $reservation->id }}">
<div class="col-12"><div class="alert alert-info">Releasing for {{ $reservation->reference }} — {{ $reservation->patient->name }}. Remaining: {{ $reservation->units_requested - $reservation->releases()->sum('units_released') }} unit(s). Saving deducts stock and completes the reservation once all units are released.</div></div>
@else
<div class="col-12"><p class="text-muted">For an online request, open its approved reservation and choose <strong>Record Blood Release</strong>. This form records a separate release and cannot use stock committed to other reservations.</p></div>
@endif
@if($inventory->isEmpty())
<div class="col-12"><div class="alert alert-warning mb-0">No active inventory units are available for release.</div></div>
@else
<div class="col-md-6"><label class="form-label">Inventory</label><select name="blood_inventory_id" id="releaseInventorySelect" class="form-select">@foreach($inventory as $item)<option value="{{ $item->id }}" data-units="{{ $item->units_available }}">{{ $item->blood_type }} · {{ $item->component_label }} · {{ $item->units_available }} units · expires {{ $item->expiration_date->toDateString() }} @if(auth('web')->user()?->isCentralAdmin()) - {{ $item->facility->name ?? 'No facility' }} @endif</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Units Released</label><input type="number" name="units_released" id="releaseUnitsInput" min="1" max="{{ min($inventory->first()?->units_available ?? 1, $reservation ? $reservation->units_requested - $reservation->releases()->sum('units_released') : 100000) }}" class="form-control" required><small class="text-muted" id="releaseUnitsHelp">Available in selected batch: {{ $inventory->first()?->units_available }} unit(s)</small></div>
<div class="col-md-3"><label class="form-label">Released At</label><input type="datetime-local" name="released_at" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Patient Name</label><input name="patient_name" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Requesting department / unit</label><input name="requesting_unit" class="form-control"><small class="text-muted">Name of the receiving department, not the number of blood bags.</small></div>
<div class="col-12"><small class="text-muted">Each release uses one stock batch. To fulfill a request using bags from several batches, record another release for each batch.</small></div>
<div class="col-12 cbis-form-actions"><a href="{{ route('blood-releases.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger">Record release</button></div>
@endif
</div></form>
@if($inventory->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const inventorySelect = document.getElementById('releaseInventorySelect');
        const unitsInput = document.getElementById('releaseUnitsInput');
        const unitsHelp = document.getElementById('releaseUnitsHelp');

        const reservationRemaining = @json($reservation ? $reservation->units_requested - $reservation->releases()->sum('units_released') : null);
        const syncAvailableUnits = () => {
            const selected = inventorySelect.options[inventorySelect.selectedIndex];
            const available = selected?.dataset.units || '1';
            const maximum = reservationRemaining === null ? Number(available) : Math.min(Number(available), reservationRemaining);

            unitsInput.max = String(maximum);
            unitsHelp.textContent = `Available in selected batch: ${available} unit(s)` + (reservationRemaining === null ? '' : ` · Request remaining: ${reservationRemaining} unit(s)`);

            if (Number(unitsInput.value) > maximum) {
                unitsInput.value = String(maximum);
            }
        };

        inventorySelect.addEventListener('change', syncAvailableUnits);
        syncAvailableUnits();
    });
</script>
@endif
@endsection
