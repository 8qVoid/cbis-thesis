<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterDonationScheduleRequest;
use App\Http\Requests\StoreDonationScheduleRequest;
use App\Http\Requests\UpdateDonationScheduleRequest;
use App\Models\DonationSchedule;
use App\Models\Facility;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DonationScheduleController extends Controller
{
    use LogsAudit;

    public function index(FilterDonationScheduleRequest $request): View
    {
        $filters = $request->validated();
        $query = FacilityScope::apply(
            DonationSchedule::query()
                ->with('facility')
                ->withCount([
                    'eventRegistrations as registrations_count' => fn ($q) => $q->where('status', 'registered'),
                ]),
            auth()->user()
        );

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['facility_id']) && auth()->user()->isCentralAdmin()) {
            $query->where('facility_id', $filters['facility_id']);
        }

        if (! empty($filters['event_date'])) {
            $query->whereDate('event_date', $filters['event_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $schedules = $query
            ->latest('event_date')
            ->latest('start_time')
            ->paginate(15);

        $facilities = auth()->user()->isCentralAdmin()
            ? Facility::query()->orderBy('name')->get()
            : collect();

        return view('donation-schedules.index', compact('schedules', 'facilities'));
    }

    public function create(): View
    {
        $facilities = auth()->user()->isCentralAdmin()
            ? Facility::query()->orderBy('name')->get()
            : Facility::query()->whereKey(auth()->user()->facility_id)->get();

        return view('donation-schedules.create', compact('facilities'));
    }

    public function store(StoreDonationScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }
        $data['start_at'] = "{$data['event_date']} {$data['start_time']}:00";
        $data['end_at'] = "{$data['event_date']} {$data['end_time']}:00";

        $schedule = DonationSchedule::create($data);
        $this->logAudit('donation_schedule.created', $schedule, $data, $request);

        return redirect()->route('donation-schedules.index')->with('success', 'Schedule added.');
    }

    public function show(DonationSchedule $donationSchedule): View
    {
        $this->authorizeRecord($donationSchedule);
        $donationSchedule->load([
            'eventRegistrations' => fn ($query) => $query
                ->where('status', 'registered')
                ->with('donor')
                ->latest('registered_at'),
        ]);

        return view('donation-schedules.show', compact('donationSchedule'));
    }

    public function edit(DonationSchedule $donationSchedule): View
    {
        $this->authorizeRecord($donationSchedule);
        $facilities = auth()->user()->isCentralAdmin()
            ? Facility::query()->orderBy('name')->get()
            : Facility::query()->whereKey(auth()->user()->facility_id)->get();

        return view('donation-schedules.edit', compact('donationSchedule', 'facilities'));
    }

    public function update(UpdateDonationScheduleRequest $request, DonationSchedule $donationSchedule): RedirectResponse
    {
        $this->authorizeRecord($donationSchedule);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }
        $data['start_at'] = "{$data['event_date']} {$data['start_time']}:00";
        $data['end_at'] = "{$data['event_date']} {$data['end_time']}:00";

        $donationSchedule->update($data);
        $this->logAudit('donation_schedule.updated', $donationSchedule, $data, $request);

        return redirect()->route('donation-schedules.index')->with('success', 'Schedule updated.');
    }

    public function destroy(DonationSchedule $donationSchedule): RedirectResponse
    {
        $this->authorizeRecord($donationSchedule);
        $donationSchedule->delete();
        $this->logAudit('donation_schedule.deleted', $donationSchedule);

        return redirect()->route('donation-schedules.index')->with('success', 'Schedule deleted.');
    }

    private function authorizeRecord(DonationSchedule $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
