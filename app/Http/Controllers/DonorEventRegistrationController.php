<?php

namespace App\Http\Controllers;

use App\Models\DonationSchedule;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DonorEventRegistrationController extends Controller
{
    public function index(): View
    {
        $donor = auth('donor')->user();

        $registrations = EventRegistration::query()
            ->with(['event.facility'])
            ->where('donor_id', $donor->id)
            ->latest('registered_at')
            ->paginate(15);

        return view('donor-portal.events', compact('registrations'));
    }

    public function join(DonationSchedule $donationSchedule): RedirectResponse
    {
        $created = $this->registerForEvent($donationSchedule);

        return redirect()->route('public.map')->with(
            'success',
            $created ? 'You are now registered for this event.' : 'You are already registered for this event.'
        );
    }

    public function store(DonationSchedule $donationSchedule): RedirectResponse
    {
        $created = $this->registerForEvent($donationSchedule);

        return back()->with(
            'success',
            $created ? 'You are now registered for this event.' : 'You are already registered for this event.'
        );
    }

    public function destroy(DonationSchedule $donationSchedule): RedirectResponse
    {
        $donor = auth('donor')->user();

        EventRegistration::query()
            ->where('donation_schedule_id', $donationSchedule->id)
            ->where('donor_id', $donor->id)
            ->update(['status' => 'cancelled']);

        return back()->with('success', 'Your event registration has been cancelled.');
    }

    private function registerForEvent(DonationSchedule $donationSchedule): bool
    {
        $donor = auth('donor')->user();

        $existingRegistration = EventRegistration::query()
            ->where('donation_schedule_id', $donationSchedule->id)
            ->where('donor_id', $donor->id)
            ->first();

        if ($existingRegistration?->status === 'registered') {
            return false;
        }

        abort_unless($donationSchedule->isRegistrationOpen(), 422, 'This event is no longer open for registration.');

        EventRegistration::query()->updateOrCreate(
            [
                'donation_schedule_id' => $donationSchedule->id,
                'donor_id' => $donor->id,
            ],
            [
                'facility_id' => $donationSchedule->facility_id,
                'status' => 'registered',
                'registered_at' => now(),
            ]
        );

        return true;
    }
}
