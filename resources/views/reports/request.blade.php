@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <p class="text-muted small mb-1">Report request #{{ $reportRequest->id }}</p>
        <h1 class="cbis-page-title mb-1">{{ $reportRequest->report_label }}</h1>
        <p class="cbis-page-subtitle mb-0">Requested by {{ $reportRequest->requester_name }} on {{ $reportRequest->created_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT.</p>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Back to reports</a>
</div>

<div data-live-region="report-request-status">
    @if($reportRequest->selection)
        <div class="card mb-3">
            <div class="card-body">
                <div class="fw-semibold mb-1">Requested contents</div>
                <div class="small">{{ implode(', ', array_map(fn ($type) => \App\Support\ReportData::TYPES[$type] ?? $type, $reportRequest->selection['records'])) }}</div>
                <div class="small text-muted mt-1">{{ ['details' => 'Detailed records', 'summary' => 'Totals only', 'both' => 'Detailed records and totals'][$reportRequest->selection['detail']] }} · {{ $reportRequest->selection['period_label'] }}</div>
            </div>
        </div>
    @endif
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-muted mb-1">Status</div>
                <span class="badge {{ $reportRequest->status === 'approved' ? 'text-bg-success' : ($reportRequest->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($reportRequest->status) }}</span>
                @if($reportRequest->reviewed_at)
                    <span class="text-muted small ms-2">Reviewed by {{ $reportRequest->reviewer_name }} on {{ $reportRequest->reviewed_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT</span>
                @endif
                @if($reportRequest->review_notes)<p class="mb-0 mt-2">{{ $reportRequest->review_notes }}</p>@endif
            </div>
            @if($reportRequest->status === 'approved')
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('report-requests.download', [$reportRequest, 'pdf']) }}" class="btn btn-outline-danger">Download PDF</a>
                    <a href="{{ route('report-requests.download', [$reportRequest, 'excel']) }}" class="btn btn-outline-success">Download protected Excel</a>
                </div>
            @elseif($reportRequest->status === 'pending' && ! auth()->user()->isQao())
                <span class="text-muted small">QAO will review this request. You will receive a notification.</span>
            @endif
        </div>
    </div>

    @if($reportRequest->status === 'pending' && auth()->user()->isQao())
        <div class="card mb-3">
            <div class="card-header">Review request</div>
            <div class="card-body">
                <p class="text-muted small">Approval captures the requested sections and period as a fixed report. The requester will then be notified and can download PDF and protected Excel copies.</p>
                <form method="POST" action="{{ route('report-requests.review', $reportRequest) }}" class="js-confirm-action mb-3" data-confirm-title="Approve this report?" data-confirm-message="This will capture the requested report and allow the requester to download it." data-confirm-button="Approve report" data-confirm-variant="success">
                    @csrf
                    <input type="hidden" name="decision" value="approved">
                    <button class="btn btn-success">Approve and prepare report</button>
                </form>
                <form method="POST" action="{{ route('report-requests.review', $reportRequest) }}" class="js-confirm-action" data-confirm-title="Reject this report request?" data-confirm-message="The requester will be notified and can see your reason." data-confirm-button="Reject request" data-confirm-variant="danger">
                    @csrf
                    <input type="hidden" name="decision" value="rejected">
                    <label for="review-notes" class="form-label">Reason for rejection</label>
                    <textarea id="review-notes" name="review_notes" class="form-control mb-2" rows="2" maxlength="1000" required>{{ old('review_notes') }}</textarea>
                    <button class="btn btn-outline-danger">Reject request</button>
                </form>
            </div>
        </div>
    @endif

    @if($sections && auth()->user()->isQao())
        <div class="card">
            <div class="card-header">{{ $reportRequest->status === 'pending' ? 'Current report preview' : 'Approved report snapshot' }}</div>
            <div class="card-body">
                @foreach($sections as $section)
                    <h2 class="h6">{{ $section['title'] }}</h2>
                    @if($section['summary'] !== null)
                        <div class="d-flex flex-wrap gap-3 mb-2 small">
                            @foreach($section['summary'] as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach
                        </div>
                    @endif
                    @if(($reportRequest->selection['detail'] ?? 'both') !== 'summary')
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead><tr>@foreach($section['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse(array_slice($section['rows'], 0, 25) as $row)
                                    <tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
                                @empty
                                    <tr><td colspan="{{ count($section['headings']) }}" class="text-muted text-center py-3">No records available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($section['rows']) > 25)<p class="small text-muted mt-2 mb-0">Showing 25 of {{ count($section['rows']) }} rows. Downloads include every row.</p>@endif
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
