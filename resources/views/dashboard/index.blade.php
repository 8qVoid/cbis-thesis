@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title">Operations Dashboard</h1>
        <p class="cbis-page-subtitle">Real-time facility monitoring for donation flow, stock levels, and release usage.</p>
    </div>
    <span class="badge text-bg-secondary">Role-Based Access Active</span>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-ui.kpi-card label="Total Donors" :value="$donors" suffix="Registered in scope" />
    </div>
    <div class="col-md-4">
        <x-ui.kpi-card label="Total Donations" :value="$donations" suffix="Recorded transactions" />
    </div>
    <div class="col-md-4">
        <x-ui.kpi-card label="Total Releases" :value="$releases" suffix="Units released to use cases" />
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Current Stock by Blood Type</span>
        <div class="d-flex gap-2">
            @if(auth('web')->user()?->can('manage donation records') || auth('web')->user()?->hasRole('Facility Admin / Blood Bank Personnel') || auth('web')->user()?->isCentralAdmin())
                <a href="{{ route('donation-records.create') }}" class="btn btn-sm btn-danger">New Donation</a>
            @endif
            @if(auth('web')->user()?->can('manage blood releases') || auth('web')->user()?->hasRole('Facility Admin / Blood Bank Personnel') || auth('web')->user()?->isCentralAdmin())
                <a href="{{ route('blood-releases.create') }}" class="btn btn-sm btn-outline-danger">New Release</a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead><tr><th>Blood Type</th><th>Units</th></tr></thead>
            <tbody>
                @forelse($inventoryByType as $row)
                    <tr>
                        <td>{{ $row->blood_type }}</td>
                        <td>
                            <span class="badge {{ $row->units <= 5 ? 'cbis-status-low' : 'cbis-status-active' }}">{{ $row->units }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center">No inventory data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
