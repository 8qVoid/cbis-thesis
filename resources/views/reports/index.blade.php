@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="cbis-page-title">Reports</h1>
        <p class="cbis-page-subtitle">Monthly demand, usage, expiration risk, and inventory status summaries.</p>
    </div>
    <span class="badge text-bg-light border px-3 py-2">Showing {{ $periodLabel }}</span>
</div>

<style>
    .cbis-report-actions {
        display: grid;
        gap: .5rem;
        grid-template-columns: repeat(auto-fit, minmax(132px, max-content));
        align-items: center;
    }

    .cbis-report-actions .btn {
        min-width: 132px;
        white-space: nowrap;
    }
</style>

<form class="mb-3 cbis-filter-card p-3">
    <div class="row g-3 align-items-start">
    <div class="col-lg-3">
        <label class="form-label">Report Period</label>
        <select name="period" class="form-select js-report-period">
            <option value="month" @selected($periodMode === 'month')>Monthly report</option>
            <option value="day" @selected($periodMode === 'day')>Daily report</option>
            <option value="range" @selected($periodMode === 'range')>Custom range</option>
        </select>
        <small class="text-muted">Choose how detailed the report should be.</small>
    </div>

    <div class="col-lg-4 js-report-control" data-period-control="month">
        <label class="form-label">Month</label>
        <div class="d-flex gap-2 flex-wrap">
            <input type="month" name="month" value="{{ $selectedMonth ?? $currentMonth }}" class="form-control">
            <a href="{{ route('reports.index', ['period' => 'month', 'month' => $previousMonth]) }}" class="btn btn-outline-secondary" title="Previous month">Previous</a>
            <a href="{{ route('reports.index', ['period' => 'month', 'month' => $currentMonth]) }}" class="btn btn-outline-secondary" title="Current month">This Month</a>
            <a href="{{ route('reports.index', ['period' => 'month', 'month' => $nextMonth]) }}" class="btn btn-outline-secondary" title="Next month">Next</a>
        </div>
        <small class="text-muted">Use the calendar picker, or jump to the previous month.</small>
    </div>

    <div class="col-lg-3 js-report-control" data-period-control="day">
        <label class="form-label">Day</label>
        <input type="date" name="day" value="{{ $selectedDay ?? now()->toDateString() }}" class="form-control">
        <small class="text-muted">Shows records for one selected date.</small>
    </div>

    <div class="col-lg-5 js-report-control" data-period-control="range">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ $periodMode === 'range' ? $from : '' }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ $periodMode === 'range' ? $to : '' }}" class="form-control">
            </div>
        </div>
        <small class="text-muted">Use this only when the report needs an exact date range.</small>
    </div>

    <div class="col-12">
        <div class="cbis-report-actions">
            <button class="btn btn-danger">Filter</button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Current Month</a>
            <a href="{{ route('reports.pdf', $exportQuery) }}" class="btn btn-outline-danger">Download PDF</a>
            <a href="{{ route('reports.excel', $exportQuery) }}" class="btn btn-outline-success">Download Excel</a>
        </div>
    </div>
    </div>
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

<div class="card mb-3">
    <div class="card-header">Inventory Summary</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Blood Type</th>
                        <th>Units</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory as $item)
                        <tr>
                            <td>{{ $item->blood_type }}</td>
                            <td>{{ $item->units_available }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'low_stock' ? 'cbis-status-low' : ($item->status === 'expired' ? 'cbis-status-expired' : 'cbis-status-active') }}">
                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No inventory records for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Donation Records</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Donation No</th>
                        <th>Blood Type</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donations as $record)
                        <tr>
                            <td>{{ $record->donation_no }}</td>
                            <td>{{ $record->blood_type }}</td>
                            <td>{{ $record->donated_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No donation records for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const periodSelect = document.querySelector('.js-report-period');
    const controls = document.querySelectorAll('.js-report-control');

    if (!periodSelect || controls.length === 0) {
        return;
    }

    const syncReportControls = () => {
        controls.forEach((control) => {
            const isActive = control.dataset.periodControl === periodSelect.value;
            control.classList.toggle('d-none', !isActive);
            control.querySelectorAll('input').forEach((input) => {
                input.disabled = !isActive;
            });
        });
    };

    periodSelect.addEventListener('change', syncReportControls);
    syncReportControls();
});
</script>
@endpush
