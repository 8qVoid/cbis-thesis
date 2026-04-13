<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterFacilityApplicationsRequest;
use App\Http\Requests\ReviewFacilityApplicationRequest;
use App\Http\Requests\StoreFacilityApplicationRequest;
use App\Models\Facility;
use App\Models\FacilityApplication;
use App\Models\User;
use App\Notifications\FacilityApplicationApproved;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FacilityApplicationController extends Controller
{
    use LogsAudit;

    private function primaryContactNumber(string $contactNumbers): string
    {
        return trim(strtok($contactNumbers, ',')) ?: $contactNumbers;
    }

    public function create(): View
    {
        return view('public-portal.facility-application');
    }

    public function store(StoreFacilityApplicationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['legitimacy_proof_path'] = $request->file('legitimacy_proof')->store('facility-applications/legitimacy', 'public');
        $data['doh_accreditation_proof_path'] = $request->file('doh_accreditation_proof')->store('facility-applications/doh', 'public');
        $data['status'] = 'pending';

        $application = FacilityApplication::create($data);
        $this->logAudit('facility_application.submitted', $application, $data, $request);

        return redirect()->route('public.index')->with('success', 'Facility application submitted. Philippine Red Cross will review your legitimacy and DOH accreditation documents.');
    }

    public function index(FilterFacilityApplicationsRequest $request): View
    {
        $filters = $request->validated();
        $query = FacilityApplication::query()->with(['reviewer', 'facility'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $applications = $query->paginate(20)->withQueryString();

        return view('facility-applications.index', compact('applications'));
    }

    public function show(FacilityApplication $facilityApplication): View
    {
        return view('facility-applications.show', [
            'application' => $facilityApplication,
        ]);
    }

    public function proof(FacilityApplication $facilityApplication, string $type): StreamedResponse
    {
        $path = match ($type) {
            'legitimacy' => $facilityApplication->legitimacy_proof_path,
            'doh' => $facilityApplication->doh_accreditation_proof_path,
            default => abort(404),
        };

        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Proof file not found.');
        }

        return Storage::disk('public')->response($path);
    }

    public function review(ReviewFacilityApplicationRequest $request, FacilityApplication $facilityApplication): RedirectResponse
    {
        $data = $request->validated();
        $temporaryPassword = null;
        $emailSent = false;

        DB::transaction(function () use ($data, $facilityApplication, $request, &$temporaryPassword): void {
            $facilityId = $facilityApplication->facility_id;
            $facility = $facilityApplication->facility;

            if ($data['status'] === 'approved' && ! $facilityId) {
                $facility = Facility::create([
                    'code' => sprintf('FAC-%04d', $facilityApplication->id),
                    'name' => $facilityApplication->organization_name,
                    'type' => $facilityApplication->facility_type,
                    'contact_person' => $facilityApplication->contact_person,
                    'contact_number' => $facilityApplication->contact_number,
                    'email' => $facilityApplication->email,
                    'address' => $facilityApplication->address,
                    'is_active' => true,
                ]);

                $facilityId = $facility->id;
            }

            if ($data['status'] === 'approved' && $facilityId) {
                $facility ??= Facility::query()->find($facilityId);
                $existingStaff = User::query()->where('email', $facilityApplication->email)->first();

                if (! $existingStaff) {
                    $temporaryPassword = Str::password(12);
                    $staffUser = User::create([
                        'name' => $facilityApplication->contact_person,
                        'email' => $facilityApplication->email,
                        'phone' => $this->primaryContactNumber($facilityApplication->contact_number),
                        'facility_id' => $facilityId,
                        'password' => $temporaryPassword,
                        'is_active' => true,
                    ]);

                    $staffUser->syncRoles(['Facility Admin / Blood Bank Personnel']);
                }
            }

            $facilityApplication->update([
                'status' => $data['status'],
                'review_notes' => $data['review_notes'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'facility_id' => $facilityId,
            ]);

            $this->logAudit('facility_application.reviewed', $facilityApplication, $data, $request);
        });

        if ($data['status'] === 'approved') {
            try {
                $facility = $facilityApplication->fresh(['facility'])->facility;

                if ($facility) {
                    Notification::route('mail', $facilityApplication->email)->notify(
                        new FacilityApplicationApproved(
                            facility: $facility,
                            recipientName: $facilityApplication->contact_person,
                            recipientEmail: $facilityApplication->email,
                            temporaryPassword: $temporaryPassword,
                            reviewNotes: $data['review_notes'] ?? null,
                        )
                    );
                    $emailSent = true;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $message = 'Application review saved.';
        if ($data['status'] === 'approved') {
            $message = $emailSent
                ? 'Application approved. Onboarding email has been sent to the applicant.'
                : 'Application approved, but onboarding email could not be sent. Please verify mail settings and retry.';
        }

        return redirect()->route('facility-applications.show', $facilityApplication)->with('success', $message);
    }
}
