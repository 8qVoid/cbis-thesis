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
    public function details(): View
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);

        return view('account.details', compact('user'));
    }

    public function saveDetails(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:500'],
        ]);
        DB::transaction(function () use ($user, $data): void {
            $user->update([...$data, 'name' => trim(implode(' ', array_filter([$data['first_name'], $data['middle_name'] ?? null, $data['last_name']])))]);
            $user->donorProfile()->update($data);
        });

        return redirect()->route('account.details.edit')->with('success', 'Profile updated.');
    }

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
            'continue_to' => ['nullable', 'in:donor,patient'],
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

        $destination = match ($data['continue_to'] ?? null) {
            'donor' => in_array('donor', $data['services'], true) ? 'public.map' : 'account.dashboard',
            'patient' => in_array('patient', $data['services'], true) ? 'reservations.create' : 'account.dashboard',
            default => 'account.dashboard',
        };

        return redirect()->route($destination)->with('success', 'Account services updated. Your existing history was preserved.');
    }
}
