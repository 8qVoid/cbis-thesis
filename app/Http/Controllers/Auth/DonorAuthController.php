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
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\PatientProfile;
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
                ->where('is_public', true)->where('approval_status', 'approved')
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
        $services = $data['services'];

        if ($eventId && ! in_array('donor', $services, true)) {
            return back()->withInput()->withErrors(['services' => 'Select Donor to register for a donation activity.']);
        }
        unset($data['event_id'], $data['services'], $data['password_confirmation']);

        [$user, $donor] = DB::transaction(function () use ($data, $services): array {
            $user = User::create([
                'name' => trim($data['first_name'].' '.($data['middle_name'] ?? '').' '.$data['last_name']),
                'first_name' => $data['first_name'], 'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'], 'birth_date' => $data['birth_date'], 'sex' => $data['sex'],
                'email' => $data['email'], 'phone' => $data['contact_number'], 'address' => $data['address'],
                'password' => $data['password'], 'is_active' => true,
            ]);

            $roles = [];
            $donor = null;
            if (in_array('donor', $services, true)) {
                $roles[] = 'Donor';
                $donor = Donor::create([
                    'user_id' => $user->id, 'facility_id' => $data['facility_id'] ?? null,
                    'first_name' => $data['first_name'], 'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'], 'birth_date' => $data['birth_date'], 'sex' => $data['sex'],
                    'blood_type' => $data['blood_type'], 'contact_number' => $data['contact_number'],
                    'email' => null, 'address' => $data['address'], 'is_eligible' => false, 'is_online_registered' => true,
                ]);
            }
            if (in_array('patient', $services, true)) {
                $roles[] = 'Patient';
                PatientProfile::create(['user_id' => $user->id]);
            }
            $user->syncRoles($roles);
            return [$user, $donor];
        });

        Auth::guard('web')->login($user);

        if ($eventId && $donor) {
            $event = DonationSchedule::query()
                ->where('is_public', true)->where('approval_status', 'approved')
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

        $message = $eventId && $donor
            ? 'Donor registration successful. You are now registered for the selected event.'
            : 'Donor registration successful.';

        return redirect()->route('account.dashboard')->with('success', str_replace('Donor registration', 'Account registration', $message));
    }

    public function logout(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
