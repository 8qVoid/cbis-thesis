<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterPublicEventsRequest;
use App\Http\Requests\FilterPublicInventoryRequest;
use App\Models\BloodBankLocation;
use App\Models\BloodInventory;
use App\Models\DonationSchedule;
use App\Models\EventRegistration;
use App\Models\Facility;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicPortalController extends Controller
{
    public function index(FilterPublicEventsRequest $request): View
    {
        $filters = $request->validated();

        $schedulesQuery = DonationSchedule::query()
            ->with('facility')
            ->where('is_public', true)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->whereIn('status', ['planned', 'ongoing']);

        if (! empty($filters['event_type'])) {
            $schedulesQuery->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['facility_id'])) {
            $schedulesQuery->where('facility_id', $filters['facility_id']);
        }

        if (! empty($filters['event_date'])) {
            $schedulesQuery->whereDate('event_date', $filters['event_date']);
        }

        $schedules = $schedulesQuery
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(20)
            ->get();

        $facilities = Facility::query()->where('is_active', true)->orderBy('name')->get();

        $registeredEventIds = $this->registeredEventIdsForDonor($schedules->pluck('id')->all());

        return view('public-portal.index', compact('schedules', 'facilities', 'registeredEventIds'));
    }

    public function events(FilterPublicEventsRequest $request): View
    {
        $filters = $request->validated();

        $eventsQuery = DonationSchedule::query()
            ->with('facility')
            ->where('is_public', true)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->whereIn('status', ['planned', 'ongoing']);

        if (! empty($filters['event_type'])) {
            $eventsQuery->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['facility_id'])) {
            $eventsQuery->where('facility_id', $filters['facility_id']);
        }

        if (! empty($filters['event_date'])) {
            $eventsQuery->whereDate('event_date', $filters['event_date']);
        }

        $events = $eventsQuery
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $facilities = Facility::query()->where('is_active', true)->orderBy('name')->get();

        $registeredEventIds = $this->registeredEventIdsForDonor($events->pluck('id')->all());

        return view('public-portal.events', compact('events', 'facilities', 'registeredEventIds'));
    }

    public function map(FilterPublicEventsRequest $request): View
    {
        $filters = $request->validated();

        $eventsQuery = DonationSchedule::query()
            ->with('facility')
            ->where('is_public', true)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->whereIn('status', ['planned', 'ongoing']);

        if (! empty($filters['event_type'])) {
            $eventsQuery->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['facility_id'])) {
            $eventsQuery->where('facility_id', $filters['facility_id']);
        }

        if (! empty($filters['event_date'])) {
            $eventsQuery->whereDate('event_date', $filters['event_date']);
        }

        $events = $eventsQuery->orderBy('event_date')->orderBy('start_time')->get();

        $facilities = Facility::query()->where('is_active', true)->orderBy('name')->get();

        $facilityLocationById = BloodBankLocation::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->keyBy('facility_id');

        $mapLocations = $events
            ->map(function (DonationSchedule $event) use ($facilityLocationById) {
                $fallbackLocation = $facilityLocationById->get($event->facility_id);
                $lat = $event->latitude ?? $fallbackLocation?->latitude;
                $lng = $event->longitude ?? $fallbackLocation?->longitude;

                if ($lat === null || $lng === null) {
                    return null;
                }

                return [
                    'title' => $event->title,
                    'event_type' => $event->event_type_label,
                    'facility' => $event->facility?->name ?? 'Unknown Facility',
                    'date' => $event->event_date?->toDateString(),
                    'time' => trim(($event->start_time ?? '').' - '.($event->end_time ?? ''), ' -'),
                    'venue' => $event->venue,
                    'contact_person' => $event->contact_person ?: ($event->facility?->contact_person ?? 'N/A'),
                    'contact_number' => $event->contact_number ?: ($event->facility?->contact_number ?? 'N/A'),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                ];
            })
            ->filter()
            ->values();

        $registeredEventIds = $this->registeredEventIdsForDonor($events->pluck('id')->all());

        return view('public-portal.map', compact('mapLocations', 'events', 'facilities', 'registeredEventIds'));
    }

    public function availability(FilterPublicInventoryRequest $request): View
    {
        $filters = $request->validated();
        $bloodTypes = ! empty($filters['blood_type'])
            ? [$filters['blood_type']]
            : BloodInventory::BLOOD_TYPES;

        $facilitiesQuery = Facility::query()
            ->where('is_active', true)
            ->where('type', 'blood_bank')
            ->orderBy('name');

        if (! empty($filters['facility_id'])) {
            $facilitiesQuery->where('id', $filters['facility_id']);
        }

        $facilities = $facilitiesQuery->get();

        $availablePairs = BloodInventory::query()
            ->select('facility_id', 'blood_type')
            ->whereIn('facility_id', $facilities->pluck('id'))
            ->whereIn('blood_type', $bloodTypes)
            ->where('units_available', '>', 0)
            ->where('status', '!=', 'expired')
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->groupBy('facility_id', 'blood_type')
            ->get()
            ->groupBy('facility_id')
            ->map(fn (Collection $rows) => $rows->pluck('blood_type')->all());

        $availabilityByFacility = $facilities->map(function (Facility $facility) use ($availablePairs) {
            $availableTypes = $availablePairs->get($facility->id, []);

            return [
                'facility' => $facility,
                'blood_types' => array_values($availableTypes),
            ];
        })->filter(fn (array $facilityAvailability) => $facilityAvailability['blood_types'] !== [])->values();

        $facilityOptions = Facility::query()
            ->where('is_active', true)
            ->where('type', 'blood_bank')
            ->orderBy('name')
            ->get();

        return view('public-portal.availability', [
            'availabilityByFacility' => $availabilityByFacility,
            'bloodTypes' => BloodInventory::BLOOD_TYPES,
            'selectedBloodTypes' => $bloodTypes,
            'facilities' => $facilityOptions,
        ]);
    }

    private function registeredEventIdsForDonor(array $eventIds): array
    {
        $donor = auth('donor')->user();

        if ($donor === null || $eventIds === []) {
            return [];
        }

        return EventRegistration::query()
            ->where('donor_id', $donor->id)
            ->where('status', 'registered')
            ->whereIn('donation_schedule_id', $eventIds)
            ->pluck('donation_schedule_id')
            ->all();
    }
}
