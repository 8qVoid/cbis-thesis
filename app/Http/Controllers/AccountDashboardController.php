<?php

namespace App\Http\Controllers;

use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\EventRegistration;
use Illuminate\View\View;

class AccountDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);
        $availableViews = array_filter([
            'both' => $user->hasDonorAccess() && $user->hasPatientAccess(),
            'donor' => $user->hasDonorAccess(),
            'patient' => $user->hasPatientAccess(),
        ]);
        $selectedView = request()->query('view');
        if (! is_string($selectedView) || ! isset($availableViews[$selectedView])) {
            $selectedView = array_key_first($availableViews);
        }
        $showDonor = $user->hasDonorAccess() && in_array($selectedView, ['donor', 'both'], true);
        $showPatient = $user->hasPatientAccess() && in_array($selectedView, ['patient', 'both'], true);
        $donor = $user->donorProfile()->with('facility')->first();
        $donationHistory = $donor ? DonationRecord::with(['bloodlettingRecord', 'facility'])->where('donor_id', $donor->id)->latest('donated_at')->get() : collect();
        $eventRegistrations = $donor ? EventRegistration::with(['event.facility'])->where('donor_id', $donor->id)->latest('registered_at')->limit(10)->get() : collect();
        $reservations = $user->bloodReservations()->with(['facility', 'documents'])->latest()->get();
        $upcomingEvents = DonationSchedule::query()->with('facility')
            ->where('is_public', true)->where('approval_status', 'approved')
            ->whereDate('event_date', '>=', today())->whereIn('status', ['planned', 'ongoing'])
            ->orderBy('event_date')->orderBy('start_time')->limit(3)->get();

        return view($selectedView === 'both' ? 'account.dashboard' : 'account.service-dashboard', compact('user', 'donor', 'donationHistory', 'eventRegistrations', 'reservations', 'upcomingEvents', 'availableViews', 'selectedView', 'showDonor', 'showPatient'));
    }
}
