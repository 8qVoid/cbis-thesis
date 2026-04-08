@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="cbis-page-title">Reports</h1>
        <p class="cbis-page-subtitle">Demand, usage, expiration risk, and inventory status summaries.</p>
    </div>
</div>
<form class="row g-3 mb-3 cbis-filter-card p-3">
<div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div class="col-md-6 d-flex align-items-end gap-2 flex-wrap"><button class="btn btn-danger">Filter</button><a href="{{ route('reports.pdf', request()->query()) }}" class="btn btn-outline-danger">Download PDF</a><a href="{{ route('reports.excel', request()->query()) }}" class="btn btn-outline-success">Download Excel</a></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <x-ui.kpi-card label="Blood Demand" :value="$demandUnits" suffix="Units released" />
    </div>
    <div class="col-md-3">
        <x-ui.kpi-card label="Usage Transactions" :value="$usageTransactions" suffix="Release records" />
    </div>
    <div class="col-md-3">
        <x-ui.kpi-card label="Expiration Risk (7d)" :value="$expirationRiskCount" statusClass="text-warning" suffix="Near-expiry records" />
    </div>
    <div class="col-md-3">
        <x-ui.kpi-card label="Low Stock Items" :value="$lowStockCount" statusClass="text-warning" suffix="Below threshold" />
    </div>
</div>

<div class="card mb-3"><div class="card-header">Inventory Summary</div><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Blood Type</th><th>Units</th><th>Status</th></tr></thead><tbody>@foreach($inventory as $item)<tr><td>{{ $item->blood_type }}</td><td>{{ $item->units_available }}</td><td><span class="badge {{ $item->status === 'low_stock' ? 'cbis-status-low' : ($item->status === 'expired' ? 'cbis-status-expired' : 'cbis-status-active') }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td></tr>@endforeach</tbody></table></div></div></div>
<div class="card"><div class="card-header">Donation Records</div><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Donation No</th><th>Blood Type</th><th>Date</th></tr></thead><tbody>@foreach($donations as $record)<tr><td>{{ $record->donation_no }}</td><td>{{ $record->blood_type }}</td><td>{{ $record->donated_at?->format('Y-m-d H:i') }}</td></tr>@endforeach</tbody></table></div></div></div>
@endsection
