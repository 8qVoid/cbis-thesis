<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use App\Models\PatientProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);

        return view('account.profile', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);

        $data = $request->validate([
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'in:donor,patient'],
            'blood_type' => [Rule::requiredIf(fn () => in_array('donor', $request->input('services', []), true)), 'nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $roles = [];
            if (in_array('donor', $data['services'], true)) {
                $roles[] = 'Donor';
                Donor::firstOrCreate(['user_id' => $user->id], [
                    'first_name' => $user->first_name ?: $user->name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name ?: 'Not provided',
                    'birth_date' => $user->birth_date,
                    'sex' => $user->sex,
                    'blood_type' => $data['blood_type'],
                    'contact_number' => $user->phone,
                    'address' => $user->address,
                    'is_eligible' => false,
                    'is_online_registered' => true,
                ])->update(['blood_type' => $data['blood_type']]);
            }
            if (in_array('patient', $data['services'], true)) {
                $roles[] = 'Patient';
                PatientProfile::firstOrCreate(['user_id' => $user->id]);
            }

            // Historical donor and patient records are retained when a service is disabled.
            $user->syncRoles($roles);
        });

        return redirect()->route('account.dashboard')->with('success', 'Account services updated. Your existing history was preserved.');
    }
}
