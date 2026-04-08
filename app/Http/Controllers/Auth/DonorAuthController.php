<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DonorSelfRegisterRequest;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\EventRegistration;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DonorAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('donor-auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function showRegister(): View
    {
        $facilities = Facility::query()->where('is_active', true)->orderBy('name')->get();
        $requestedFacilityId = request()->integer('facility_id');
        $selectedFacilityId = $facilities->contains('id', $requestedFacilityId) ? $requestedFacilityId : null;
        $selectedEvent = null;
        $eventId = request()->integer('event_id');

        if ($eventId > 0) {
            $selectedEvent = DonationSchedule::query()
                ->with('facility')
                ->where('is_public', true)
                ->whereDate('event_date', '>=', now()->toDateString())
                ->find($eventId);

            if ($selectedEvent) {
                $selectedFacilityId = $selectedEvent->facility_id;
            }
        }

        return view('donor-auth.register', compact('facilities', 'selectedFacilityId', 'selectedEvent'));
    }

    public function register(DonorSelfRegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $eventId = $data['event_id'] ?? null;
        unset($data['event_id']);

        $data['is_eligible'] = true;
        $data['is_online_registered'] = true;

        $donor = Donor::create($data);

        Auth::guard('donor')->login($donor);

        if ($eventId) {
            $event = DonationSchedule::query()
                ->where('is_public', true)
                ->whereDate('event_date', '>=', now()->toDateString())
                ->find($eventId);

            if ($event) {
                EventRegistration::query()->updateOrCreate(
                    [
                        'donation_schedule_id' => $event->id,
                        'donor_id' => $donor->id,
                    ],
                    [
                        'facility_id' => $event->facility_id,
                        'status' => 'registered',
                        'registered_at' => now(),
                    ]
                );
            }
        }

        $message = $eventId
            ? 'Donor registration successful. You are now registered for the selected event.'
            : 'Donor registration successful.';

        return redirect()->route('donor.portal.profile')->with('success', $message);
    }

    public function logout(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
