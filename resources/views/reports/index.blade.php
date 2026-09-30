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

<form method="GET" class="mb-3 cbis-filter-card p-3" data-auto-filter="true">
    <div class="row g-3 align-items-start">
    <div class="col-lg-3">
        <label class="form-label">Report Period</label>
        <select name="period" class="form-select js-report-period">
            <option value="month" @selected($periodMode === 'month')>Monthly report</option>
            <option value="day" @selected($periodMode === 'day')>Daily report</option>
            <option value="range" @selected($periodMode === 'range')>Custom range</option>
        </select>
        <small class="text-muted">Choose the dates for the report data below.</small>
    </div>

    <div class="col-lg-4 js-report-control" data-period-control="month">
        <label class="form-label">Month</label>
        <div class="d-flex gap-2 flex-wrap">
            <input type="month" name="month" value="{{ $selectedMonth ?? $currentMonth }}" max="{{ $currentMonth }}" class="form-control">
            <a href="{{ route('reports.index', ['period' => 'month', 'month' => $previousMonth]) }}" class="btn btn-outline-secondary" title="Previous month">Previous</a>
            <a href="{{ route('reports.index', ['period' => 'month', 'month' => $currentMonth]) }}" class="btn btn-outline-secondary" title="Current month">This Month</a>
            @if($nextMonth <= $currentMonth)
                <a href="{{ route('reports.index', ['period' => 'month', 'month' => $nextMonth]) }}" class="btn btn-outline-secondary" title="Next month">Next</a>
            @else
                <button type="button" class="btn btn-outline-secondary" disabled title="Future months are unavailable">Next</button>
            @endif
        </div>
        <small class="text-muted">Use the calendar picker, or jump to the previous month.</small>
    </div>

    <div class="col-lg-3 js-report-control" data-period-control="day">
        <label class="form-label">Day</label>
        <input type="date" name="day" value="{{ $selectedDay ?? now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control">
        <small class="text-muted">Shows records for one selected date.</small>
    </div>

    <div class="col-lg-5 js-report-control" data-period-control="range">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ $periodMode === 'range' ? $from : '' }}" max="{{ now()->toDateString() }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ $periodMode === 'range' ? $to : '' }}" max="{{ now()->toDateString() }}" class="form-control">
            </div>
        </div>
        <small class="text-muted">Choose both dates to apply a custom range. Until then, the summary below shows all records.</small>
    </div>

    <div class="col-12">
        <div class="cbis-report-actions">
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Current Month</a>
            @can('export reports')
                <span class="text-muted small">Select export contents below.</span>
            @else
                <span class="text-muted small align-self-center">Summary view only. Request QAO approval below to download a report.</span>
            @endcan
        </div>
    </div>
    </div>
</form>

@can('export reports')
<form method="GET" action="{{ route('reports.excel') }}" class="card card-body mb-3">
    <input type="hidden" name="export_selection" value="1">
    <h2 class="h5">Choose what to export</h2>
    <p class="text-muted">Bacolod main chapter records for {{ $periodLabel }}. Private documents and clinical notes are excluded.</p>
    @foreach($exportQuery as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
    <div class="d-flex flex-wrap gap-3 mb-3">
    @foreach(\App\Support\ReportData::TYPES as $value=>$label)
        <label><input type="checkbox" name="records[]" value="{{ $value }}" @checked(in_array($value, $selectedRecords))> {{ $label }}</label>
    @endforeach
    </div>
    <label for="report-detail" class="form-label">Export contents</label>
    <select id="report-detail" name="detail" class="form-select mb-3">
        @foreach(['details'=>'Detailed records','summary'=>'Totals only','both'=>'Detailed records and totals'] as $value=>$label)<option value="{{ $value }}" @selected($selectedDetail===$value)>{{ $label }}</option>@endforeach
    </select>
    <label for="requested-by" class="form-label">Requested by <span class="text-muted">(required for both downloads)</span></label>
    <input id="requested-by" name="requested_by" class="form-control mb-2" value="{{ old('requested_by') }}" maxlength="120" required placeholder="Full name of the person requesting the printed report">
    <small class="text-muted mb-3">The name and a blank signature line appear in both the Excel and PDF reports. The requester signs the printed copy.</small>
    <div class="d-flex gap-2"><button type="submit" class="btn btn-outline-success">Download Excel</button><button type="submit" formaction="{{ route('reports.pdf') }}" class="btn btn-outline-danger">Download PDF</button></div>
</form>
@endcan
@if(auth()->user()->isBloodBankStaff() && \App\Support\MainChapter::contains(auth()->user()->facility_id))
<form method="POST" action="{{ route('report-requests.store') }}" class="card card-body mb-3 js-confirm-action js-report-request-form" data-confirm-title="Confirm report request?" data-confirm-button="Send request" data-confirm-variant="danger" data-period-ready="{{ $periodMode !== 'range' || ($from && $to) ? 'true' : 'false' }}">
    @csrf
    <h2 class="h5 mb-1">Request a report</h2>
    <p class="text-muted small mb-3">Choose what QAO should prepare. Your account name is recorded automatically, and download buttons appear after approval.</p>
    @foreach($exportQuery as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
    <label for="report-request-type" class="form-label">Choose what to request</label>
    <select id="report-request-type" name="report_type" class="form-select" required>
        <option value="" disabled @selected(! old('report_type'))>Select a report type</option>
        <option value="stock_summary" @selected(old('report_type') === 'stock_summary')>Current blood stock summary</option>
        <option value="selected_report" @selected(old('report_type') === 'selected_report')>Selected report sections</option>
    </select>
    <div class="alert alert-light border rounded px-3 py-2 mt-2 mb-0 small js-report-request-info" hidden>
        <span aria-hidden="true" class="text-primary me-1">ⓘ</span>
        <strong class="js-report-request-info-title"></strong>
        <span class="text-muted js-report-request-help" aria-live="polite"></span>
    </div>
    <div class="border rounded p-3 my-3 js-selected-report-options" @if(old('report_type') !== 'selected_report') hidden @endif>
        <div class="fw-semibold mb-1">Choose sections for a selected report</div>
        @if($periodMode === 'range' && (! $from || ! $to))
            <p class="small text-danger mb-2">Set both From and To dates above before requesting this report.</p>
        @endif
        <p class="small text-muted mb-2">Inventory here includes records from the chosen dates, even depleted or expired ones. Use Current blood stock summary for usable stock now.</p>
        <div class="d-flex flex-wrap gap-3 mb-3">
            @foreach(\App\Support\ReportData::TYPES as $value => $label)
                <label><input type="checkbox" name="records[]" value="{{ $value }}" @checked(in_array($value, old('records', [])))> {{ $label }}</label>
            @endforeach
        </div>
        <label for="request-detail" class="form-label">Report contents</label>
        <select id="request-detail" name="detail" class="form-select">
            @foreach(['details' => 'Detailed records', 'summary' => 'Totals only', 'both' => 'Detailed records and totals'] as $value => $label)
                <option value="{{ $value }}" @selected(old('detail', 'both') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-danger mt-3">Send request to QAO</button>
</form>
@endif

@if(auth()->user()->isQao() || auth()->user()->isBloodBankStaff())
<div class="card mb-3" id="report-requests" data-live-region="report-requests">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0">{{ auth()->user()->isQao() ? 'Inventory report requests' : 'My report requests' }}</h2>
        <form method="GET" action="{{ route('reports.index') }}#report-requests" class="d-flex align-items-center gap-2">
            @foreach(['period', 'month', 'day', 'from', 'to'] as $reportFilter)
                @if(request()->filled($reportFilter))<input type="hidden" name="{{ $reportFilter }}" value="{{ request($reportFilter) }}">@endif
            @endforeach
            <label for="request-status" class="small text-muted mb-0">Status</label>
            <select id="request-status" name="request_status" class="form-select form-select-sm" aria-label="Filter report requests by status" onchange="this.form.submit()">
                <option value="" @selected($requestStatus === '')>All requests</option>
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $status => $label)
                    <option value="{{ $status }}" @selected($requestStatus === $status)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-sm btn-outline-secondary">Apply</button></noscript>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Report</th><th>Requested by</th><th>Requested</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($reportRequests as $reportRequest)
                        <tr>
                            <td>{{ $reportRequest->report_label }}</td>
                            <td>{{ $reportRequest->requester_name }}</td>
                            <td>{{ $reportRequest->created_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT</td>
                            <td><span class="badge {{ $reportRequest->status === 'approved' ? 'text-bg-success' : ($reportRequest->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($reportRequest->status) }}</span></td>
                            <td class="text-end"><a href="{{ route('report-requests.show', $reportRequest) }}" class="btn btn-sm btn-outline-danger">{{ $reportRequest->status === 'pending' && auth()->user()->isQao() ? 'Review' : 'View' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ $requestStatus ? 'No '.strtolower($requestStatus).' requests.' : 'No report requests yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-muted">{{ $reportRequests->total() ? 'Showing '.$reportRequests->firstItem().'–'.$reportRequests->lastItem().' of '.$reportRequests->total().' '.\Illuminate\Support\Str::plural('request', $reportRequests->total()) : '0 requests' }}</span>
        @if($reportRequests->hasPages()){{ $reportRequests->links() }}@endif
    </div>
</div>
@endif

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
                        <th>Component</th>
                        <th>Units</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory as $item)
                        <tr>
                            <td>{{ $item->blood_type }}</td>
                            <td>{{ $item->component_label }}</td>
                            <td>{{ $item->units_available }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'low_stock' ? 'cbis-status-low' : ($item->status === 'expired' ? 'cbis-status-expired' : 'cbis-status-active') }}">
                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No inventory records for this period.</td>
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
                input.required = isActive;
            });
        });
    };

    periodSelect.addEventListener('change', syncReportControls);
    syncReportControls();
});

document.addEventListener('DOMContentLoaded', () => {
    const reportType = document.getElementById('report-request-type');
    const requestForm = document.querySelector('.js-report-request-form');
    const options = document.querySelector('.js-selected-report-options');
    const info = document.querySelector('.js-report-request-info');
    const infoTitle = document.querySelector('.js-report-request-info-title');
    const help = document.querySelector('.js-report-request-help');
    const requestPeriodLabel = @json($periodLabel);
    if (!reportType || !requestForm || !options) return;
    const sectionCheckboxes = [...options.querySelectorAll('input[name="records[]"]')];

    const validateSections = () => {
        const missing = reportType.value === 'selected_report' && !sectionCheckboxes.some(field => field.checked);
        sectionCheckboxes[0]?.setCustomValidity(missing ? 'Select at least one report section.' : '');
        return !missing;
    };

    const syncRequestOptions = () => {
        const selected = reportType.value === 'selected_report';
        options.hidden = !selected;
        options.querySelectorAll('input, select').forEach(field => { field.disabled = !selected; });
        info.hidden = !reportType.value;
        infoTitle.textContent = reportType.value === 'stock_summary'
            ? 'Current blood stock summary. ' : 'Selected report sections. ';
        help.textContent = reportType.value === 'stock_summary'
            ? 'Shows available, unexpired units grouped by blood type and component, with the nearest expiry date. Stock is captured when QAO approves; the date filter above does not apply.'
            : selected ? 'Choose the records and level of detail below. The date filter above applies.' : '';
        validateSections();
    };

    reportType.addEventListener('change', syncRequestOptions);
    sectionCheckboxes.forEach(field => field.addEventListener('change', validateSections));
    requestForm.addEventListener('submit', event => {
        if (!validateSections()) {
            event.preventDefault();
            event.stopPropagation();
            sectionCheckboxes[0].reportValidity();
            return;
        }
        if (reportType.value === 'selected_report' && requestForm.dataset.periodReady === 'false') {
            event.preventDefault();
            event.stopPropagation();
            document.querySelector('.js-report-control[data-period-control="range"] input[name="from"]')?.reportValidity();
            return;
        }

        const chosen = sectionCheckboxes.filter(field => field.checked)
            .map(field => '☑ ' + field.closest('label').textContent.trim());
        const contents = options.querySelector('select[name="detail"]')?.selectedOptions[0]?.textContent.trim();
        requestForm.dataset.confirmMessage = reportType.value === 'stock_summary'
            ? 'Are you sure you want to request the current blood stock summary from QAO?'
            : 'Are you sure you want to request these report sections from QAO?\n\n' + chosen.join('\n')
                + '\n\nContents: ' + contents + '\nPeriod: ' + requestPeriodLabel;
    });
    syncRequestOptions();
});
</script>
@endpush
