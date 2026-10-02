@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div><div class="cbis-eyebrow">Stock record #{{ $bloodInventory->id }}</div><h1 class="cbis-page-title mb-0">{{ $bloodInventory->blood_type }} · {{ $bloodInventory->component_label }}</h1><p class="cbis-page-subtitle mb-0">This stock record represents one batch. Its quantity and expiry are shown below.</p></div>
    <div class="d-flex flex-wrap gap-2"><a href="{{ route('blood-inventory.storage', ['blood_type' => $bloodInventory->blood_type, 'component' => $bloodInventory->component]) }}" class="btn btn-outline-secondary">View storage batches</a><a href="{{ route('blood-inventory.index') }}#stock-batches" class="btn btn-outline-secondary">Back to inventory</a>@can('manage inventory')<a href="{{ route('blood-inventory.edit', $bloodInventory) }}" class="btn btn-danger">Edit stock</a>@endcan</div>
</div>
<div class="card card-body" data-live-region="inventory-record">
    <dl class="row mb-0">
        <dt class="col-sm-3">Units in this batch</dt><dd class="col-sm-9">{{ $bloodInventory->units_available }}</dd>
        <dt class="col-sm-3">Expiration date</dt><dd class="col-sm-9">{{ $bloodInventory->expiration_date?->format('M j, Y') }}</dd>
        <dt class="col-sm-3">Source</dt><dd class="col-sm-9">{{ $bloodInventory->donationRecord ? 'Donation '.$bloodInventory->donationRecord->donation_no : 'Manual stock entry' }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $bloodInventory->status === 'expired' || $bloodInventory->expiration_date?->lt(today()) ? 'Expired' : ((int) $bloodInventory->units_available === 0 ? 'Depleted' : ((int) $bloodInventory->units_available <= 5 ? 'Low stock' : 'In storage')) }}</dd>
    </dl>
</div>
@endsection
