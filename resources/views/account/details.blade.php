@extends('layouts.app')
@section('content')
<div class="cbis-account-page">
    <div class="cbis-page-heading">
        <div>
            <h1 class="cbis-page-title">Profile</h1>
            <p class="cbis-page-subtitle">Keep your personal and contact information up to date.</p>
        </div>
        <a href="{{ route('account.dashboard') }}" class="btn btn-outline-secondary">Back to Home</a>
    </div>

    <div class="cbis-profile-grid">
        <form method="POST" action="{{ route('account.details.update') }}" enctype="multipart/form-data" class="card cbis-profile-form cbis-compact-form">
            @csrf @method('PUT')
            <div class="card-body">
                <p class="cbis-form-section-help cbis-required-note"><span class="cbis-required-marker" aria-hidden="true">*</span> Required fields</p>
                <fieldset class="cbis-form-section">
                    <legend class="cbis-form-section-title">Personal information</legend>
                    <div class="row g-3">
                        @foreach(['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name'] as $field => $label)
                            <div class="col-md-4">
                                <label for="{{ $field }}" class="form-label">{{ $label }} @if($field === 'middle_name')<span class="text-muted fw-normal small ms-1">Optional</span>@else<span class="cbis-required-marker" aria-hidden="true">*</span>@endif</label>
                                <input id="{{ $field }}" name="{{ $field }}" class="form-control js-person-name @error($field) is-invalid @enderror" maxlength="80" pattern="[\p{L}\s.'-]+" value="{{ old($field, $user->$field) }}" @required($field !== 'middle_name') @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                                <x-ui.field-error :field="$field" :id="$field.'-error'" />
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="cbis-form-section">
                    <legend class="cbis-form-section-title">Contact and address</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="profileEmail">Email <span class="cbis-required-marker" aria-hidden="true">*</span></label>
                            <input id="profileEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" maxlength="255" value="{{ old('email', $user->email) }}" required aria-describedby="profileEmailHelp @error('email') profileEmail-error @enderror" @error('email') aria-invalid="true" @enderror>
                            <x-ui.field-error field="email" id="profileEmail-error" />
                            <small id="profileEmailHelp" class="text-muted">Use this email to sign in after saving.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="profilePhone">Mobile number <span class="cbis-required-marker" aria-hidden="true">*</span></label>
                            <input id="profilePhone" name="phone" class="form-control js-mobile-local @error('phone') is-invalid @enderror" inputmode="numeric" minlength="11" maxlength="11" pattern="09\d{9}" title="Enter exactly 11 digits starting with 09" value="{{ \App\Support\PhilippinePhone::mobileLocal(old('phone', $user->phone)) }}" placeholder="09171234567" required aria-describedby="profilePhoneHelp @error('phone') profilePhone-error @enderror" @error('phone') aria-invalid="true" @enderror>
                            <x-ui.field-error field="phone" id="profilePhone-error" />
                            <small id="profilePhoneHelp" class="text-muted">11 digits, starting with 09.</small>
                        </div>
                        <div class="col-12">
                            <x-negros-occidental-address-fields :address="old('address', $user->address)" />
                        </div>
                    </div>
                </fieldset>

                <div class="cbis-form-section">
                    <details class="cbis-profile-upload" @error('identity_document') open @enderror>
                        <summary>
                            <span class="fw-semibold">Valid ID</span>
                            <span class="small text-muted">{{ $user->latestIdentityDocument ? 'Saved · Replace or view' : 'Optional · Add now or later' }}</span>
                        </summary>
                        <div class="cbis-profile-upload-body">
                            @if($user->latestIdentityDocument)
                                <div class="cbis-profile-saved-id">
                                    <div>
                                        <p class="mb-1 fw-semibold text-break">{{ $user->latestIdentityDocument->original_name }}</p>
                                        <p class="small text-muted mb-0">Uploaded {{ $user->latestIdentityDocument->created_at->diffForHumans() }}. Reusable for blood requests.</p>
                                    </div>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('account.identity-document.show') }}" target="_blank" rel="noopener">View saved ID</a>
                                </div>
                            @else
                                <p class="cbis-form-section-help">No ID saved yet. You can also upload one during your first blood request.</p>
                            @endif
                            <label for="identity_document" class="form-label">{{ $user->latestIdentityDocument ? 'Replace saved ID' : 'Upload valid ID' }}</label>
                            <input id="identity_document" type="file" name="identity_document" class="form-control @error('identity_document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="profileIdHelp @error('identity_document') identity_document-error @enderror" @error('identity_document') aria-invalid="true" @enderror>
                            <x-ui.field-error field="identity_document" id="identity_document-error" />
                            <small id="profileIdHelp" class="text-muted d-block">PDF, JPG or PNG. Uploading a new copy replaces your saved ID.</small>
                        </div>
                    </details>
                </div>

                <div class="cbis-form-actions cbis-mobile-form-actions">
                    <button class="btn btn-danger" type="submit">Save Profile</button>
                </div>
            </div>
        </form>

        <aside class="card cbis-profile-summary" aria-label="Account summary">
            <div class="card-body">
                <section class="cbis-profile-summary-section">
                    <h2 class="cbis-form-section-title">Services</h2>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if($user->hasDonorAccess())<span class="badge text-bg-light border">Donor</span>@endif
                        @if($user->hasPatientAccess())<span class="badge text-bg-light border">Patient</span>@endif
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('account.profile.edit') }}">Manage Services</a>
                </section>
                @if($user->donorProfile)
                    <section class="cbis-profile-summary-section">
                        <h2 class="cbis-form-section-title">Recorded blood type</h2>
                        <p class="h4 mb-2">{{ $user->donorProfile->blood_type }}</p>
                        <p class="small text-muted mb-0">Blood Bank Staff confirms your donation eligibility.</p>
                    </section>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection
