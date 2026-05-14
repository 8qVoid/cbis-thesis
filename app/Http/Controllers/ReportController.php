<?php

namespace App\Http\Controllers;

use App\Exports\BloodInventoryExport;
use App\Http\Requests\FilterReportsRequest;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\DonationRecord;
use App\Support\FacilityScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(FilterReportsRequest $request): View
    {
        $this->authorizeFacilityReports();

        $filters = $request->validated();
        [$from, $to, $selectedMonth, $selectedDay, $periodMode, $periodLabel] = $this->resolvePeriod($filters);
        $report = $this->buildReportData($from, $to);
        $currentMonth = now()->startOfMonth();
        $activeMonth = $selectedMonth
            ? Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()
            : $currentMonth->copy();

        return view('reports.index', [
            ...$report,
            'from' => $from,
            'to' => $to,
            'selectedMonth' => $selectedMonth,
            'selectedDay' => $selectedDay,
            'periodMode' => $periodMode,
            'periodLabel' => $periodLabel,
            'exportQuery' => $this->exportQuery($periodMode, $selectedMonth, $selectedDay, $from, $to),
            'previousMonth' => $activeMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $activeMonth->copy()->addMonth()->format('Y-m'),
            'currentMonth' => $currentMonth->format('Y-m'),
        ]);
    }

    public function pdf(FilterReportsRequest $request)
    {
        $this->authorizeFacilityReports();

        $filters = $request->validated();
        [$from, $to, $selectedMonth, $selectedDay, $periodMode, $periodLabel] = $this->resolvePeriod($filters);

        $inventory = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('blood_type')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.inventory', [
            'records' => $inventory,
            'periodLabel' => $periodLabel,
        ]);

        $fileName = 'blood-inventory-report-'.$this->filePeriodSlug($periodMode, $selectedMonth, $from, $to).'.pdf';

        return $pdf->download($fileName);
    }

    public function excel(FilterReportsRequest $request): BinaryFileResponse
    {
        $this->authorizeFacilityReports();

        $filters = $request->validated();
        [$from, $to, $selectedMonth, $selectedDay, $periodMode] = $this->resolvePeriod($filters);

        $fileName = 'blood-inventory-report-'.$this->filePeriodSlug($periodMode, $selectedMonth, $from, $to).'.xlsx';

        return Excel::download(
            new BloodInventoryExport($from, $to, auth()->user()),
            $fileName
        );
    }

    private function buildReportData(?string $from, ?string $to): array
    {
        $inventory = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('blood_type')
            ->get();

        $donations = FacilityScope::apply(DonationRecord::query(), auth()->user())
            ->when($from, fn ($q) => $q->whereDate('donated_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('donated_at', '<=', $to))
            ->latest('donated_at')
            ->get();

        $releases = FacilityScope::apply(BloodRelease::query(), auth()->user())
            ->when($from, fn ($q) => $q->whereDate('released_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('released_at', '<=', $to))
            ->get();

        $demandUnits = (int) $releases->sum('units_released');
        $usageTransactions = $releases->count();
        $expirationRiskCount = $inventory
            ->where('status', '!=', 'expired')
            ->filter(fn (BloodInventory $item) => $item->expiration_date && $item->expiration_date->between(now()->startOfDay(), now()->addDays(7)->endOfDay()))
            ->count();
        $lowStockCount = $inventory->where('status', 'low_stock')->count();

        return compact(
            'inventory',
            'donations',
            'demandUnits',
            'usageTransactions',
            'expirationRiskCount',
            'lowStockCount'
        ) + [
            'donationCount' => $donations->count(),
        ];
    }

    private function resolvePeriod(array $filters): array
    {
        $periodMode = $filters['period'] ?? null;

        if ($periodMode === 'day' && ! empty($filters['day'])) {
            $day = Carbon::parse($filters['day'])->startOfDay();

            return [
                $day->toDateString(),
                $day->toDateString(),
                null,
                $day->toDateString(),
                'day',
                $day->format('F d, Y'),
            ];
        }

        if ($periodMode === 'range' && (! empty($filters['from']) || ! empty($filters['to']))) {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            return [
                $from,
                $to,
                null,
                null,
                'range',
                $this->rangeLabel($from, $to),
            ];
        }

        if (! empty($filters['month']) || $periodMode === 'month') {
            $month = ! empty($filters['month'])
                ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
                : now()->startOfMonth();

            return [
                $month->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
                $month->format('Y-m'),
                null,
                'month',
                $month->format('F Y'),
            ];
        }

        if (! empty($filters['day'])) {
            $day = Carbon::parse($filters['day'])->startOfDay();

            return [
                $day->toDateString(),
                $day->toDateString(),
                null,
                $day->toDateString(),
                'day',
                $day->format('F d, Y'),
            ];
        }

        if (! empty($filters['from']) || ! empty($filters['to'])) {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            return [
                $from,
                $to,
                null,
                null,
                'range',
                $this->rangeLabel($from, $to),
            ];
        }

        $month = now()->startOfMonth();

        return [
            $month->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
            $month->format('Y-m'),
            null,
            'month',
            $month->format('F Y'),
        ];
    }

    private function exportQuery(string $periodMode, ?string $selectedMonth, ?string $selectedDay, ?string $from, ?string $to): array
    {
        if ($periodMode === 'month') {
            return [
                'period' => 'month',
                'month' => $selectedMonth,
            ];
        }

        if ($periodMode === 'day') {
            return [
                'period' => 'day',
                'day' => $selectedDay,
            ];
        }

        return array_filter([
            'period' => 'range',
            'from' => $from,
            'to' => $to,
        ]);
    }

    private function filePeriodSlug(string $periodMode, ?string $selectedMonth, ?string $from, ?string $to): string
    {
        if ($periodMode === 'month' && $selectedMonth) {
            return $selectedMonth;
        }

        if ($periodMode === 'day' && $from) {
            return $from;
        }

        if ($periodMode === 'range' && ($from || $to)) {
            return ($from ?: 'start').'-to-'.($to ?: 'end');
        }

        return now()->format('Y-m');
    }

    private function rangeLabel(?string $from, ?string $to): string
    {
        if ($from && $to) {
            return Carbon::parse($from)->format('M d, Y').' to '.Carbon::parse($to)->format('M d, Y');
        }

        if ($from) {
            return 'From '.Carbon::parse($from)->format('M d, Y');
        }

        if ($to) {
            return 'Until '.Carbon::parse($to)->format('M d, Y');
        }

        return 'All records';
    }

    private function authorizeFacilityReports(): void
    {
        if (auth()->user()?->isCentralAdmin() || ! auth()->user()?->can('manage inventory')) {
            abort(403);
        }
    }
}
