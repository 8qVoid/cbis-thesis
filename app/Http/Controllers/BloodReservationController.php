<?php

namespace App\Http\Controllers;

use App\Models\BloodInventory;
use App\Models\BloodReservation;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\BloodReservationStatusChanged;
use App\Notifications\BloodReservationSubmitted;
use App\Support\MainChapter;
use App\Support\ReservedStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BloodReservationController extends Controller
{
    public function index(): View
    {
        $filters = request()->validate(['status' => ['nullable', 'in:pending']]);
        $user = auth()->user();
        if ($user->hasRole('Patient')) {
            $reservations = $user->bloodReservations()->with('facility')->when($filters['status'] ?? null, fn ($query) => $query->whereIn('status', ['submitted', 'under_review']))->latest()->paginate(15)->withQueryString();
        } else {
            abort_unless($user->can('process reservations') || $user->can('monitor reservations'), 403);
            abort_unless($user->isQao() || MainChapter::contains($user->facility_id), 403);
            $query = BloodReservation::with(['patient', 'facility'])->whereIn('facility_id', MainChapter::ids());
            if (! $user->isQao()) {
                $query->where('facility_id', $user->facility_id);
            }
            $reservations = $query->when($filters['status'] ?? null, fn ($query) => $query->whereIn('status', ['submitted', 'under_review']))->latest()->paginate(15)->withQueryString();
        }

        return view('blood-reservations.index', compact('reservations'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasRole('Patient'), 403);
        $facilities = Facility::whereIn('id', MainChapter::ids())->get();

        return view('blood-reservations.create', compact('facilities'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('Patient'), 403);
        $data = $request->validate([
            'facility_id' => ['required', Rule::exists('facilities', 'id')->where('is_main_chapter', true)->where('is_active', true)->whereNull('deleted_at')], 'blood_type' => ['required', 'in:'.implode(',', BloodInventory::BLOOD_TYPES)],
            'component' => ['required', 'in:'.implode(',', array_keys(BloodInventory::COMPONENTS))], 'units_requested' => ['required', 'integer', 'min:1', 'max:20'],
            'needed_on' => ['required', 'date', 'after_or_equal:today'], 'clinical_purpose' => ['nullable', 'string', 'max:1000'],
            'blood_request' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'identification' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $reservation = DB::transaction(function () use ($request, $data): BloodReservation {
            $reservation = BloodReservation::create([
                ...collect($data)->except(['blood_request', 'identification'])->all(),
                'reference' => 'BR-'.now()->format('Ymd').'-'.strtoupper(str()->random(6)), 'patient_user_id' => auth()->id(),
            ]);
            foreach (['blood_request', 'identification'] as $type) {
                if (! $request->hasFile($type)) {
                    continue;
                }
                $file = $request->file($type);
                $path = $file->store("reservations/{$reservation->id}", 'local');
                $reservation->documents()->create(['type' => $type, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
            }

            return $reservation;
        });
        User::role(['Quality Assurance Officer', 'Blood Bank Staff'])->where(fn ($q) => $q->whereNull('facility_id')->orWhere('facility_id', $reservation->facility_id))->each(fn ($user) => $user->notify(new BloodReservationSubmitted($reservation)));

        return redirect()->route('reservations.index')->with('success', 'Blood request submitted. Track its status in My Blood Requests. Bring the original documents when requested by Blood Bank Staff.');
    }

    public function show(BloodReservation $reservation): View
    {
        $this->authorizeView($reservation);
        $reservation->load(['patient', 'facility', 'documents', 'reviewer']);

        return view('blood-reservations.show', compact('reservation'));
    }

    public function document(Request $request, BloodReservation $reservation, int $document)
    {
        $user = auth()->user();
        $isOwner = $user->hasRole('Patient') && $reservation->patient_user_id === $user->id;
        $isFacilityBbs = $user->can('process reservations') && $user->facility_id === $reservation->facility_id && MainChapter::contains($user->facility_id);
        abort_unless($isOwner || $isFacilityBbs, 403);
        $record = $reservation->documents()->findOrFail($document);

        abort_unless(Storage::disk('local')->exists($record->path), 404, 'This document is no longer available. Please ask the patient to provide another copy.');
        abort_unless(in_array($record->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true), 415, 'This document type cannot be previewed.');

        return Storage::disk('local')->response($record->path, $record->original_name, [
            'Content-Type' => $record->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ], $request->boolean('download') ? 'attachment' : 'inline');
    }

    public function review(Request $request, BloodReservation $reservation): RedirectResponse
    {
        abort_unless(auth()->user()->can('process reservations'), 403);
        abort_unless(MainChapter::contains($reservation->facility_id), 403);
        abort_unless(auth()->user()->facility_id === $reservation->facility_id, 403);
        DB::transaction(function () use ($request, &$reservation): void {
            Facility::whereKey($reservation->facility_id)->lockForUpdate()->firstOrFail();
            $reservation = BloodReservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $transitions = [
                'submitted' => ['under_review', 'rejected'],
                'under_review' => ['approved', 'rejected'],
                'approved' => ['rejected'],
                'rejected' => [],
                'fulfilled' => [],
                'cancelled' => [],
            ];
            $allowedStatuses = $transitions[$reservation->status] ?? [];
            $data = $request->validate([
                'status' => ['required', 'in:'.implode(',', $allowedStatuses ?: ['__none__'])],
                'review_notes' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
            ], ['status.in' => 'That status change is not allowed from the reservation’s current status.']);

            if ($data['status'] === 'approved') {
                $available = BloodInventory::query()
                    ->where('facility_id', $reservation->facility_id)
                    ->where('blood_type', $reservation->blood_type)
                    ->where('component', $reservation->component)
                    ->whereIn('status', ['active', 'low_stock'])
                    ->whereDate('expiration_date', '>=', today())
                    ->sum('units_available');
                $alreadyReserved = ReservedStock::outstanding($reservation->facility_id, $reservation->blood_type, $reservation->component, $reservation->id);

                if (($available - $alreadyReserved) < $reservation->units_requested) {
                    $component = BloodInventory::COMPONENTS[$reservation->component] ?? $reservation->component;
                    $freeUnits = max(0, (int) ($available - $alreadyReserved));
                    throw ValidationException::withMessages(['status' => "Approval was not saved. This request needs {$reservation->units_requested} unit(s) of {$reservation->blood_type} {$component}; only {$freeUnits} unreserved, non-expired unit(s) are available. Check Inventory for matching stock, then retry when stock is available. The request remains under review."]);
                }
            }
            $reservation->update([...$data, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        });
        // A mail outage must not turn a saved decision into an apparent failure.
        try {
            $reservation->patient->notify(new BloodReservationStatusChanged($reservation));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Reservation status updated. '.match ($reservation->status) {
            'under_review' => 'Review the submitted documents before saving a decision.',
            'approved' => 'Stock is reserved. Record the blood release when the units are issued.',
            'rejected' => 'The patient can see the decision and review notes in their account.',
            default => 'The latest status is shown in the request details.',
        });
    }

    private function authorizeView(BloodReservation $reservation): void
    {
        $user = auth()->user();
        if ($user->hasRole('Patient')) {
            abort_unless($reservation->patient_user_id === $user->id, 403);

            return;
        }
        abort_unless($user->can('process reservations') || $user->can('monitor reservations'), 403);
        abort_unless(MainChapter::contains($reservation->facility_id), 403);
        if (! $user->isQao()) {
            abort_unless($reservation->facility_id === $user->facility_id, 403);
        }
    }
}
