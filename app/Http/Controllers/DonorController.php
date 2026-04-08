<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonorRequest;
use App\Http\Requests\UpdateDonorRequest;
use App\Models\Donor;
use App\Models\Facility;
use App\Support\DonorScope;
use App\Traits\LogsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DonorController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $donors = DonorScope::apply(Donor::query()->with('facility'), auth()->user())
            ->latest()
            ->paginate(15);

        return view('donors.index', compact('donors'));
    }

    public function create(): View
    {
        $facilities = Facility::query()->orderBy('name')->get();

        return view('donors.create', compact('facilities'));
    }

    public function store(StoreDonorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $donor = Donor::create($data);
        $this->logAudit('donor.created', $donor, $data, $request);

        return redirect()->route('donors.index')->with('success', 'Donor added.');
    }

    public function show(Donor $donor): View
    {
        $this->authorizeDonor($donor);

        return view('donors.show', compact('donor'));
    }

    public function edit(Donor $donor): View
    {
        $this->authorizeDonor($donor);
        $facilities = Facility::query()->orderBy('name')->get();

        return view('donors.edit', compact('donor', 'facilities'));
    }

    public function update(UpdateDonorRequest $request, Donor $donor): RedirectResponse
    {
        $this->authorizeDonor($donor);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $donor->update($data);
        $this->logAudit('donor.updated', $donor, $data, $request);

        return redirect()->route('donors.index')->with('success', 'Donor updated.');
    }

    public function destroy(Donor $donor): RedirectResponse
    {
        $this->authorizeDonor($donor);
        $donor->delete();
        $this->logAudit('donor.deleted', $donor);

        return redirect()->route('donors.index')->with('success', 'Donor removed.');
    }

    private function authorizeDonor(Donor $donor): void
    {
        $user = auth()->user();

        if ($user->isCentralAdmin()) {
            return;
        }

        $facilityId = $user->facility_id;

        $isScoped = Donor::query()
            ->whereKey($donor->id)
            ->where(function (Builder $builder) use ($facilityId): void {
                $builder->where('facility_id', $facilityId)
                    ->orWhereHas('donationRecords', fn (Builder $q) => $q->where('facility_id', $facilityId))
                    ->orWhereHas('eventRegistrations', fn (Builder $q) => $q->where('facility_id', $facilityId));
            })
            ->exists();

        if (! $isScoped) {
            abort(403);
        }
    }
}
