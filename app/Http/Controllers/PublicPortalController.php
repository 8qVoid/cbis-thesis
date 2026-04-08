<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterPublicEventsRequest;
use App\Models\BloodBankLocation;
use App\Models\DonationSchedule;
use App\Models\Facility;
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

        return view('public-portal.index', compact('schedules', 'facilities'));
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

        return view('public-portal.events', compact('events', 'facilities'));
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

        return view('public-portal.map', compact('mapLocations', 'events', 'facilities'));
    }
}
