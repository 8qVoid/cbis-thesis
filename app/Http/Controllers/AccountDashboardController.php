<?php

namespace App\Http\Controllers;

use App\Models\DonationRecord;
use App\Models\EventRegistration;
use Illuminate\View\View;

class AccountDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);
        $donor = $user->donorProfile()->with('facility')->first();
        $donationHistory = $donor ? DonationRecord::with('bloodlettingRecord')->where('donor_id', $donor->id)->latest('donated_at')->get() : collect();
        $eventRegistrations = $donor ? EventRegistration::with(['event.facility'])->where('donor_id', $donor->id)->latest('registered_at')->limit(10)->get() : collect();
        $reservations = $user->bloodReservations()->with('facility')->latest()->get();
        return view('account.dashboard', compact('user', 'donor', 'donationHistory', 'eventRegistrations', 'reservations'));
    }
}
