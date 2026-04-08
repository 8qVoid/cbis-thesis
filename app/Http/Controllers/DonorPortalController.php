<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDonorPortalRequest;
use App\Models\DonationRecord;
use App\Models\EventRegistration;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DonorPortalController extends Controller
{
    public function profile(): View
    {
        $donor = auth('donor')->user();
        $donationHistory = DonationRecord::query()
            ->with('bloodlettingRecord')
            ->where('donor_id', $donor->id)
            ->latest('donated_at')
            ->get();
        $eventRegistrations = EventRegistration::query()
            ->with(['event.facility'])
            ->where('donor_id', $donor->id)
            ->latest('registered_at')
            ->limit(10)
            ->get();
        $facilities = Facility::query()->where('is_active', true)->orderBy('name')->get();

        return view('donor-portal.profile', compact('donor', 'donationHistory', 'eventRegistrations', 'facilities'));
    }

    public function update(UpdateDonorPortalRequest $request): RedirectResponse
    {
        $donor = auth('donor')->user();

        $data = $request->validated();

        $donor->update($data);

        return redirect()->route('donor.portal.profile')->with('success', 'Donor profile updated.');
    }
}
