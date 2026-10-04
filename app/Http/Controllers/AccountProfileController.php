<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use App\Models\IdentityDocument;
use App\Models\PatientProfile;
use App\Models\User;
use App\Rules\NegrosOccidentalAddressRule;
use App\Support\PhilippinePhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountProfileController extends Controller
{
    public function details(): View
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);
        $user->load('latestIdentityDocument');

        return view('account.details', compact('user'));
    }

    public function saveDetails(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);
        if ($request->filled('phone')) {
            $request->merge(['phone' => PhilippinePhone::normalizeMobileInput((string) $request->input('phone')) ?? trim((string) $request->input('phone'))]);
        }
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80', "regex:/^[\\pL\\s.'-]+$/u"],
            'middle_name' => ['nullable', 'string', 'max:80', "regex:/^[\\pL\\s.'-]+$/u"],
            'last_name' => ['required', 'string', 'max:80', "regex:/^[\\pL\\s.'-]+$/u"],
            'address' => ['required', 'string', 'max:500', new NegrosOccidentalAddressRule],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'regex:/^\+639\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'identity_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $emailChanged = $user->email !== $data['email'];
        DB::transaction(function () use ($request, $user, $data, $emailChanged): void {
            unset($data['identity_document']);
            if ($emailChanged) {
                $user->forceFill(['email_verified_at' => null]);
            }
            $user->update([...$data, 'name' => trim(implode(' ', array_filter([$data['first_name'], $data['middle_name'] ?? null, $data['last_name']])))]);
            $user->donorProfile()->update([
                'first_name' => $data['first_name'], 'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'], 'address' => $data['address'],
                'contact_number' => $data['phone'],
            ]);
            self::storeIdentityDocument($user, $request);
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('success', 'Profile updated. Verify your new email address using the link we sent before continuing.');
        }

        return redirect()->route('account.details.edit')->with('success', 'Profile updated.');
    }

    public function identityDocument(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['Donor', 'Patient']), 403);
        $document = $user->identityDocuments()->latest()->firstOrFail();

        abort_unless(Storage::disk('local')->exists($document->path), 404);
        abort_unless(in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);

        return Storage::disk('local')->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ], $request->boolean('download') ? 'attachment' : 'inline');
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

    public static function storeIdentityDocument(User $user, Request $request): ?IdentityDocument
    {
        if (! $request->hasFile('identity_document')) {
            return null;
        }

        $file = $request->file('identity_document');
        $path = $file->store("identity-documents/{$user->id}", 'local');

        return $user->identityDocuments()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);
    }
}
