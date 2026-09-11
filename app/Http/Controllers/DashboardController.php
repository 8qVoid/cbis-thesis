<?php

namespace App\Http\Controllers;

use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use App\Support\DonorScope;
use App\Support\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if ($user->isEventFacilitator()) {
            $eventsQuery = FacilityScope::apply(DonationSchedule::query(), $user);
            $events = (clone $eventsQuery)->withCount(['eventRegistrations as registrations_count' => fn ($query) => $query->where('status', 'registered')])
                ->orderByDesc('event_date')->limit(10)->get();
            $upcomingEvents = (clone $eventsQuery)->whereDate('event_date', '>=', today())->whereIn('status', ['planned', 'ongoing'])->count();
            $pendingEvents = (clone $eventsQuery)->where('approval_status', 'pending')->count();
            $approvedThisMonth = (clone $eventsQuery)->where('approval_status', 'approved')->whereBetween('reviewed_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
            $registeredDonors = (clone $eventsQuery)->whereDate('event_date', '>=', today())->whereIn('status', ['planned', 'ongoing'])
                ->withCount(['eventRegistrations as registered_count' => fn ($query) => $query->where('status', 'registered')])->get()->sum('registered_count');
            $completedEvents = (clone $eventsQuery)->where('status', 'completed')->count();
            $nextEvent = (clone $eventsQuery)->withCount(['eventRegistrations as registrations_count' => fn ($query) => $query->where('status', 'registered')])
                ->whereDate('event_date', '>=', today())->whereIn('status', ['planned', 'ongoing'])->orderBy('event_date')->orderBy('start_time')->first();
            $calendarEvents = (clone $eventsQuery)->whereBetween('event_date', [today()->startOfMonth(), today()->endOfMonth()])->orderBy('event_date')->get();

            return view('dashboard.facilitator', compact('events', 'upcomingEvents', 'pendingEvents', 'approvedThisMonth', 'registeredDonors', 'completedEvents', 'nextEvent', 'calendarEvents'));
        }

        $donors = DonorScope::apply(Donor::query(), $user)->count();
        $awaitingScreeningCount = DonorScope::apply(Donor::query(), $user)
            ->where(fn ($query) => $query->whereDoesntHave('latestScreening')->orWhereHas('latestScreening', fn ($screening) => $screening->whereNotIn('status', ['eligible', 'deferred'])))->count();
        $activeFacilityCount = $user->isQao() ? Facility::where('is_active', true)->count() : 0;
        $publicUserCount = $user->isQao() ? User::role(['Donor', 'Patient'])->count() : 0;
        $expiringStockCount = FacilityScope::apply(BloodInventory::query(), $user)->where('units_available', '>', 0)
            ->whereDate('expiration_date', '>=', today())->whereDate('expiration_date', '<=', today()->addDays(14))->count();
        $donations = FacilityScope::apply(DonationRecord::query(), $user)->count();
        $releases = FacilityScope::apply(BloodRelease::query(), $user)->count();

        $inventoryByType = FacilityScope::apply(BloodInventory::query(), $user)
            ->select('blood_type', DB::raw('SUM(units_available) as units'))
            ->groupBy('blood_type')
            ->orderBy('blood_type')
            ->get();

        $inventoryByComponent = FacilityScope::apply(BloodInventory::query(), $user)
            ->select('component', DB::raw('SUM(units_available) as units'))
            ->groupBy('component')->orderBy('component')->get()->keyBy('component');
        $totalUnits = (int) $inventoryByComponent->sum('units');
        $lowStockCount = FacilityScope::apply(BloodInventory::query(), $user)
            ->where(fn ($query) => $query->where('status', 'low_stock')->orWhere('units_available', '<=', 5))->count();
        $pendingRequestCount = FacilityScope::apply(BloodReservation::query(), $user)->whereIn('status', ['submitted', 'under_review'])->count();
        $submittedRequestCount = FacilityScope::apply(BloodReservation::query(), $user)->where('status', 'submitted')->count();
        $reservationQueue = $user->isBloodBankStaff() ? FacilityScope::apply(BloodReservation::query(), $user)
            ->with(['patient', 'documents'])->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('needed_on')->limit(6)->get() : collect();
        $reservationNotices = FacilityScope::apply(BloodReservation::query(), $user)
            ->with('facility')->latest()->limit(5)->get();
        $pendingActivityCount = DonationSchedule::query()->where('approval_status', 'pending')->count();
        $pendingActivities = DonationSchedule::query()->with('facility')->where('approval_status', 'pending')->orderBy('event_date')->limit(5)->get();
        $expiringInventory = FacilityScope::apply(BloodInventory::query(), $user)
            ->whereDate('expiration_date', '>=', today())->whereDate('expiration_date', '<=', today()->addDays(14))
            ->orderBy('expiration_date')->limit(6)->get();

        return view('dashboard.index', compact(
            'awaitingScreeningCount', 'activeFacilityCount', 'publicUserCount', 'expiringStockCount',
            'donors', 'donations', 'releases', 'inventoryByType', 'inventoryByComponent', 'totalUnits',
            'lowStockCount', 'reservationQueue', 'reservationNotices', 'pendingActivityCount', 'pendingActivities', 'expiringInventory', 'pendingRequestCount', 'submittedRequestCount'
        ));
    }
}
