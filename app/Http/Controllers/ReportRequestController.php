<?php

namespace App\Http\Controllers;

use App\Exports\SelectedReportsExport;
use App\Models\BloodInventory;
use App\Models\ReportRequest;
use App\Models\User;
use App\Notifications\ReportRequestReviewed;
use App\Notifications\ReportRequestSubmitted;
use App\Support\FacilityScope;
use App\Support\MainChapter;
use App\Support\ReportData;
use App\Traits\LogsAudit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportRequestController extends Controller
{
    use LogsAudit;

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canRequest($user), 403);

        $data = $request->validate([
            'report_type' => ['required', 'in:'.implode(',', array_keys(ReportRequest::TYPES))],
            'records' => ['required_if:report_type,selected_report', 'array', 'min:1'],
            'records.*' => ['required', 'distinct', 'in:'.implode(',', array_keys(ReportData::TYPES))],
            'detail' => ['required_if:report_type,selected_report', 'in:details,summary,both'],
            'period' => ['required_if:report_type,selected_report', 'in:month,day,range'],
            'month' => ['required_if:period,month', 'nullable', 'date_format:Y-m'],
            'day' => ['required_if:period,day', 'nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $type = $data['report_type'];
        $selection = $type === 'selected_report' ? $this->selectedReportOptions($data) : null;
        $pendingKey = $user->id.':'.$type;

        try {
            $reportRequest = DB::transaction(function () use ($request, $user, $type, $selection, $pendingKey) {
                if (ReportRequest::where('pending_key', $pendingKey)->exists()) {
                    throw ValidationException::withMessages([
                        'report_type' => 'You already have a pending request for this report.',
                    ]);
                }

                $reportRequest = ReportRequest::create([
                    'facility_id' => $user->facility_id,
                    'requested_by' => $user->id,
                    'requester_name' => $user->name,
                    'report_type' => $type,
                    'selection' => $selection,
                    'status' => 'pending',
                    'pending_key' => $pendingKey,
                ]);
                $this->logAudit('report_request.submitted', $reportRequest, [
                    'report_type' => $type,
                    'selection' => $selection,
                    'requester_name' => $user->name,
                ], $request);

                return $reportRequest;
            });
        } catch (QueryException $exception) {
            if (ReportRequest::where('pending_key', $pendingKey)->exists()) {
                throw ValidationException::withMessages([
                    'report_type' => 'You already have a pending request for this report.',
                ]);
            }

            throw $exception;
        }

        $qaoUsers = User::role(['Quality Assurance Officer', 'Super Administrator'])
            ->where('is_active', true)->get();
        Notification::send($qaoUsers, new ReportRequestSubmitted($reportRequest));

        return redirect()->route('report-requests.show', $reportRequest)
            ->with('success', 'Request sent to QAO. You will be notified when it is reviewed.');
    }

    public function show(Request $request, ReportRequest $reportRequest): View
    {
        $this->authorizeViewer($request->user(), $reportRequest);
        $reportRequest->load(['requester', 'reviewer']);
        $sections = $reportRequest->snapshot['sections'] ?? null;

        if ($reportRequest->status === 'pending' && $request->user()->isQao()) {
            $sections = $this->sectionsFor($reportRequest, $request->user());
        }

        return view('reports.request', compact('reportRequest', 'sections'));
    }

    public function review(Request $request, ReportRequest $reportRequest): RedirectResponse
    {
        abort_unless($request->user()->isQao() && $request->user()->can('export reports'), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_notes' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        $reportRequest = DB::transaction(function () use ($request, $reportRequest, $data) {
            $locked = ReportRequest::whereKey($reportRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 409, 'This request has already been reviewed.');

            $reviewedAt = now();
            $snapshot = $data['decision'] === 'approved'
                ? [
                    'sections' => $this->sectionsFor($locked, $request->user()),
                    'detail' => $locked->selection['detail'] ?? 'both',
                    'periodLabel' => $locked->selection['period_label'] ?? 'Stock snapshot approved '.$reviewedAt->copy()->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT',
                    'requestedBy' => $locked->requester_name,
                    'printedBy' => $request->user()->name,
                    'printedAt' => $reviewedAt->copy()->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT',
                    'attributionLabel' => 'Approved by',
                ] : null;

            $locked->update([
                'status' => $data['decision'],
                'pending_key' => null,
                'reviewed_by' => $request->user()->id,
                'reviewer_name' => $request->user()->name,
                'reviewed_at' => $reviewedAt,
                'review_notes' => trim($data['review_notes'] ?? '') ?: null,
                'snapshot' => $snapshot,
            ]);
            $this->logAudit('report_request.'.$locked->status, $locked, [
                'report_type' => $locked->report_type,
                'selection' => $locked->selection,
                'requester_name' => $locked->requester_name,
                'review_notes' => $locked->review_notes,
            ], $request);

            return $locked;
        });

        $reportRequest->requester?->notify(new ReportRequestReviewed($reportRequest));

        return redirect()->route('report-requests.show', $reportRequest)
            ->with('success', $reportRequest->status === 'approved'
                ? 'Report approved. The requester can now download the PDF or Excel copy.'
                : 'Report request rejected. The requester has been notified.');
    }

    public function download(Request $request, ReportRequest $reportRequest, string $format)
    {
        $this->authorizeViewer($request->user(), $reportRequest);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        abort_unless($reportRequest->status === 'approved' && is_array($reportRequest->snapshot), 404);

        $snapshot = $reportRequest->snapshot;
        if ($reportRequest->reviewed_at && ! str_ends_with($snapshot['printedAt'] ?? '', ' PHT')) {
            $approvedAt = $reportRequest->reviewed_at->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT';
            $snapshot['printedAt'] = $approvedAt;
            if (str_starts_with($snapshot['periodLabel'] ?? '', 'Stock snapshot approved ')) {
                $snapshot['periodLabel'] = 'Stock snapshot approved '.$approvedAt;
            }
        }
        $fileName = 'inventory-report-'.$reportRequest->id.'-'.$reportRequest->report_type;

        if ($format === 'pdf') {
            $response = Pdf::loadView('reports.pdf.selected', $snapshot)->download($fileName.'.pdf');
        } else {
            $response = Excel::download(new SelectedReportsExport(
                $snapshot['sections'], $snapshot['detail'], $snapshot['periodLabel'],
                $snapshot['requestedBy'], $snapshot['printedBy'], $snapshot['printedAt'],
                $snapshot['attributionLabel'] ?? 'Printed by',
            ), $fileName.'.xlsx');
        }

        $this->logAudit('report_request.downloaded', $reportRequest, ['format' => $format], $request);

        return $response;
    }

    private function sectionsFor(ReportRequest $reportRequest, User $user): array
    {
        if ($reportRequest->report_type === 'selected_report') {
            $selection = $reportRequest->selection;

            return ReportData::sections(
                $selection['records'], $selection['detail'], $selection['from'], $selection['to'], $user,
            );
        }

        if ($reportRequest->report_type === 'inventory_records') {
            return ReportData::sections(['inventory'], 'both', null, null, $user);
        }

        $groups = FacilityScope::apply(BloodInventory::query(), $user)
            ->selectRaw('blood_type, component, SUM(units_available) as total_units, MIN(expiration_date) as next_expiry')
            ->where('units_available', '>', 0)
            ->where('status', '!=', 'expired')
            ->whereDate('expiration_date', '>=', today())
            ->groupBy('blood_type', 'component')
            ->orderBy('blood_type')->orderBy('component')->get();

        return [[
            'title' => 'Current blood stock by type and component',
            'headings' => ['Blood type', 'Component', 'Available units', 'Next expiry'],
            'rows' => $groups->map(fn ($group) => [
                $group->blood_type,
                BloodInventory::COMPONENTS[$group->component] ?? $group->component,
                (string) $group->total_units,
                (string) $group->next_expiry,
            ])->all(),
            'summary' => ['Stock groups' => $groups->count(), 'Available units' => (int) $groups->sum('total_units')],
        ]];
    }

    private function selectedReportOptions(array $data): array
    {
        $today = today()->toDateString();
        if ((! empty($data['month']) && $data['month'] > now()->format('Y-m'))
            || collect(['day', 'from', 'to'])->contains(fn ($field) => ! empty($data[$field]) && $data[$field] > $today)) {
            throw ValidationException::withMessages(['period' => 'Choose a date that is not in the future.']);
        }

        $period = $data['period'];
        if ($period === 'range' && (empty($data['from']) || empty($data['to']))) {
            throw ValidationException::withMessages(['period' => 'Choose both dates for the custom range before requesting a report.']);
        }
        if ($period === 'month') {
            $start = Carbon::parse($data['month'].'-01');
            $from = $start->toDateString();
            $to = $start->copy()->endOfMonth()->toDateString();
            $label = $start->format('F Y');
        } elseif ($period === 'day') {
            $from = $to = $data['day'];
            $label = Carbon::parse($from)->format('F d, Y');
        } else {
            $from = $data['from'] ?? null;
            $to = $data['to'] ?? null;
            $label = $from && $to ? $from.' to '.$to : ($from ? 'From '.$from : ($to ? 'Until '.$to : 'All records'));
        }

        return [
            'records' => array_values($data['records']),
            'detail' => $data['detail'],
            'period' => $period,
            'period_label' => $label,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function canRequest(User $user): bool
    {
        return $user->isBloodBankStaff()
            && $user->can('request summaries')
            && MainChapter::contains($user->facility_id);
    }

    private function authorizeViewer(User $user, ReportRequest $reportRequest): void
    {
        abort_unless($user->isQao() || ($this->canRequest($user) && $reportRequest->requested_by === $user->id), 403);
    }
}
