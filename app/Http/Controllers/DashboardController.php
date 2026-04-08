<?php

namespace App\Http\Controllers;

use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\DonationRecord;
use App\Models\Donor;
use App\Support\DonorScope;
use App\Support\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $donors = DonorScope::apply(Donor::query(), $user)->count();
        $donations = FacilityScope::apply(DonationRecord::query(), $user)->count();
        $releases = FacilityScope::apply(BloodRelease::query(), $user)->count();

        $inventoryByType = FacilityScope::apply(BloodInventory::query(), $user)
            ->select('blood_type', DB::raw('SUM(units_available) as units'))
            ->groupBy('blood_type')
            ->orderBy('blood_type')
            ->get();

        return view('dashboard.index', compact('donors', 'donations', 'releases', 'inventoryByType'));
    }
}
