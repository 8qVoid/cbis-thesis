<?php

namespace App\Http\Controllers;

use App\Exports\BloodInventoryExport;
use App\Http\Requests\FilterReportsRequest;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\DonationRecord;
use App\Support\FacilityScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(FilterReportsRequest $request): View
    {
        $filters = $request->validated();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

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

        return view('reports.index', compact(
            'inventory',
            'donations',
            'from',
            'to',
            'demandUnits',
            'usageTransactions',
            'expirationRiskCount',
            'lowStockCount'
        ));
    }

    public function pdf(FilterReportsRequest $request)
    {
        $filters = $request->validated();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $records = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->get();

        $pdf = Pdf::loadView('reports.pdf.inventory', ['records' => $records]);

        return $pdf->download('blood-inventory-report.pdf');
    }

    public function excel(FilterReportsRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();

        return Excel::download(
            new BloodInventoryExport($filters['from'] ?? null, $filters['to'] ?? null, auth()->user()),
            'blood-inventory-report.xlsx'
        );
    }
}
