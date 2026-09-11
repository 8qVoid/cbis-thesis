@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageInventory = $currentUser?->can('manage inventory') ?? false;
@endphp
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Blood Inventory</h1>
        <p class="cbis-page-subtitle">Monitor stock levels, expiration dates, and status. Donation records update inventory automatically.</p>
    </div>
    @if($canManageInventory)
        <a href="{{ route('blood-inventory.create') }}" class="btn btn-danger">Add Manual Adjustment</a>
    @endif
</div>
<div class="table-responsive">
@if(request('status') || request('expiring'))
<p class="small text-muted">Showing: {{ request('expiring') ? 'Stock expiring within 14 days' : 'Low-stock items (5 units or fewer, or marked low stock)' }}. <a href="{{ route('blood-inventory.index') }}">View all inventory</a></p>
@endif
<table class="table table-striped bg-white"><thead><tr><th>Blood Type</th><th>Component</th><th>Units</th><th>Expiry</th><th>Source</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($inventory as $item)
<tr><td>{{ $item->blood_type }}</td><td>{{ $item->component_label }}</td><td>{{ $item->units_available }}</td><td>{{ $item->expiration_date?->toDateString() }}</td><td>{{ $item->donationRecord ? 'Donation record' : 'Manual adjustment' }}</td><td><span class="badge {{ $item->status === 'low_stock' ? 'cbis-status-low' : ($item->status === 'expired' ? 'cbis-status-expired' : 'cbis-status-active') }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td><td><a href="{{ route('blood-inventory.show',$item) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
@empty
<tr><td colspan="7"><div class="cbis-empty-state"><strong>No inventory records found</strong><span>Verified donations add stock automatically. Manual adjustments are used to record or correct stock with a reason.</span>@if($canManageInventory)<a href="{{ route('donation-records.create') }}" class="btn btn-sm btn-outline-danger mt-2">Record a donation</a>@endif</div></td></tr>
@endforelse
</tbody></table></div>{{ $inventory->links() }}
@endsection
