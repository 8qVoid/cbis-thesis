@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageInventory = ! ($currentUser?->isCentralAdmin() ?? false) && ($currentUser?->can('manage inventory') ?? false);
@endphp
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Blood Inventory</h1>
        <p class="cbis-page-subtitle">Monitor stock levels, expiration dates, and status.</p>
    </div>
    @if($canManageInventory)
        <a href="{{ route('blood-inventory.create') }}" class="btn btn-danger">Add Manual Entry</a>
    @endif
</div>
<div class="table-responsive">
<table class="table table-striped bg-white"><thead><tr><th>Blood Type</th><th>Units</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($inventory as $item)
<tr><td>{{ $item->blood_type }}</td><td>{{ $item->units_available }}</td><td>{{ $item->expiration_date?->toDateString() }}</td><td><span class="badge {{ $item->status === 'low_stock' ? 'cbis-status-low' : ($item->status === 'expired' ? 'cbis-status-expired' : 'cbis-status-active') }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td><td><a href="{{ route('blood-inventory.show',$item) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@endforeach
</tbody></table></div>{{ $inventory->links() }}
@endsection
