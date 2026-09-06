<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonorRequest;
use App\Http\Requests\UpdateDonorRequest;
use App\Models\Donor;
use App\Models\Facility;
use App\Notifications\DonorScreeningUpdated;
use App\Support\DonorScope;
use App\Support\MainChapter;
use App\Traits\LogsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DonorController extends Controller
{
    use LogsAudit;

    public function screen(Request $request, Donor $donor): RedirectResponse
    {
        $this->authorizeDonor($donor);
        abort_unless($request->user()->isBloodBankStaff(), 403);
        $data = $request->validate([
            'status' => ['required', 'in:awaiting,eligible,deferred'],
            'screening_confirmed' => ['accepted'],
            'donor_message' => ['required_if:status,deferred', 'nullable', 'string', 'max:1000'],
            'review_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $screening = DB::transaction(function () use ($donor, $data, $request) {
            $screening = $donor->screenings()->create([...collect($data)->except('screening_confirmed')->all(), 'reviewed_by' => $request->user()->id]);
            $donor->update(['is_eligible' => $data['status'] === 'eligible']);
            $this->logAudit('donor.screening_recorded', $screening, ['status' => $data['status']], $request);
            $donor->user?->notify(new DonorScreeningUpdated($screening));

            return $screening;
        });

        return back()->with('success', 'Screening decision saved. The donor can see the updated status in their account.');
    }

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
        if ($donor->user_id || $donor->donationRecords()->exists() || $donor->eventRegistrations()->exists() || $donor->screenings()->exists()) {
            return back()->withErrors(['donor' => 'This donor has an account or recorded history and cannot be deleted. Keep the profile for traceability; QAO can deactivate account access when needed.']);
        }
        $donor->delete();
        $this->logAudit('donor.deleted', $donor);

        return redirect()->route('donors.index')->with('success', 'Donor removed.');
    }

    private function authorizeDonor(Donor $donor): void
    {
        $user = auth()->user();

        if ($user->isCentralAdmin() || ($user->isBloodBankStaff() && MainChapter::contains($user->facility_id))) {
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
