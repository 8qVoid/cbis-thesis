<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AccountProfileController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DonorSelfRegisterRequest;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\PatientProfile;
use App\Models\User;
use App\Support\VerificationEmailDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $selectedService = request()->string('service')->value();
        $selectedService = in_array($selectedService, ['donor', 'patient'], true) ? $selectedService : 'donor';
        $selectedEvent = null;
        $eventId = request()->integer('event_id');

        if ($eventId > 0) {
            $selectedEvent = DonationSchedule::query()
                ->with('facility')
                ->where('is_public', true)->where('approval_status', 'approved')
                ->whereDate('event_date', '>=', now()->toDateString())
                ->find($eventId);

            if ($selectedEvent && ! $selectedEvent->isRegistrationOpen()) {
                $selectedEvent = null;
            }
        }

        return view('donor-auth.register', compact('selectedEvent', 'selectedService'));
    }

    public function register(DonorSelfRegisterRequest $request, VerificationEmailDelivery $verificationEmail): RedirectResponse
    {
        $data = $request->validated();
        $eventId = $data['event_id'] ?? null;
        $services = $data['services'];

        if ($eventId && ! in_array('donor', $services, true)) {
            return back()->withInput()->withErrors(['services' => 'Select Donor to register for a donation activity.']);
        }
        unset($data['event_id'], $data['services'], $data['password_confirmation']);

        [$user, $donor] = DB::transaction(function () use ($request, $data, $eventId, $services): array {
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
                    'user_id' => $user->id, 'facility_id' => null,
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
            $user->forceFill(['pending_event_id' => $eventId])->save();
            AccountProfileController::storeIdentityDocument($user, $request);

            return [$user, $donor];
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        if (! $verificationEmail->send($user)) {
            return redirect()->route('verification.notice')
                ->with('verification_warning', VerificationEmailDelivery::FAILURE_MESSAGE);
        }

        return redirect()->route('verification.notice')
            ->with('success', 'Account created. Open the verification link sent to your email to activate your account.');
    }

    public function logout(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
