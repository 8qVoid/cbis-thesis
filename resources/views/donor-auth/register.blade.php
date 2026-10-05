@extends('layouts.app')

@section('content')
@php
    $registrationServices = (array) old('services', session()->has('_old_input') ? [] : [$selectedService]);
    $registrationServicesError = $errors->first('services') ?: $errors->first('services.0') ?: $errors->first('services.1');
    $registrationRecovery = in_array(\App\Http\Requests\Auth\DonorSelfRegisterRequest::EMAIL_ALREADY_REGISTERED, $errors->get('email'), true)
        || in_array(\App\Http\Requests\Auth\DonorSelfRegisterRequest::PHONE_ALREADY_REGISTERED, $errors->get('contact_number'), true);
@endphp
<div class="cbis-registration-shell">
    <div class="cbis-registration-heading">
        <div><span class="cbis-registration-kicker">New account</span><h1>Create your account</h1><p>Enter your details once, then verify your email to activate your account.</p></div>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">Sign in instead</a>
    </div>

    @if($registrationRecovery)
        <section class="alert alert-info mb-3" aria-labelledby="registration-recovery-title" role="status">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div>
                    <strong id="registration-recovery-title">Continue with your existing account</strong>
                    <p class="small mb-0 mt-1">Sign in to check your verification status and request a new email link.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('login') }}" class="btn btn-danger btn-sm">Sign in</a>
                    <a href="{{ route('password.request') }}" class="btn btn-outline-secondary btn-sm">Forgot password?</a>
                </div>
            </div>
        </section>
    @endif

    <form id="registrationForm" method="POST" action="{{ route('donor.register.store') }}" enctype="multipart/form-data" class="card shadow-sm cbis-registration-form js-confirm-action" data-confirm-title="Check your information" data-confirm-message="Please make sure your name, birth date, blood type, email, mobile number, and address are correct before continuing." data-confirm-button="Continue Registration" data-confirm-variant="danger">
        @csrf
        @if($selectedEvent)<input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">@endif
        <div class="card-body">
            <p class="cbis-required-note small text-muted mb-3"><span class="cbis-required-marker" aria-hidden="true">*</span> Required fields</p>
            @if($selectedEvent)
                <div class="alert alert-info cbis-registration-event"><strong>{{ $selectedEvent->title }}</strong><span>{{ $selectedEvent->event_date?->toDateString() }} · {{ $selectedEvent->facility?->name ?? 'Location pending' }}</span></div>
            @endif

            <section class="cbis-registration-section cbis-registration-section-wide" aria-labelledby="registration-services">
                <div class="cbis-registration-section-heading"><span>1</span><div><h2 id="registration-services">Choose services</h2><p>Select at least one service. The same account works for both.</p></div></div>
                <div class="cbis-service-choice-grid">
                    <label class="cbis-service-choice"><input id="registrationServiceDonor" class="form-check-input js-service @if($registrationServicesError) is-invalid @endif" type="checkbox" name="services[]" value="donor" aria-describedby="servicesError" @if($registrationServicesError) aria-invalid="true" @endif @checked(in_array('donor', $registrationServices, true))><span><strong>Donate blood</strong><small>Register as a donor and join donation events.</small></span></label>
                    <label class="cbis-service-choice"><input id="registrationServicePatient" class="form-check-input js-service @if($registrationServicesError) is-invalid @endif" type="checkbox" name="services[]" value="patient" aria-describedby="servicesError" @if($registrationServicesError) aria-invalid="true" @endif @checked(in_array('patient', $registrationServices, true))><span><strong>Request blood</strong><small>Submit and track blood requests.</small></span></label>
                </div>
                <p id="servicesError" class="text-danger small mt-2 mb-0" role="status" @if(!$registrationServicesError) hidden @endif>{{ $registrationServicesError ?: 'Select at least one service to continue.' }}</p>
            </section>

            <section class="cbis-registration-section" aria-labelledby="registration-personal">
                <div class="cbis-registration-section-heading"><span>2</span><div><h2 id="registration-personal">Personal information</h2><p>Use the same details shown on your official records.</p></div></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="registrationFirstName">First name <span class="cbis-required-marker" aria-hidden="true">*</span></label><input id="registrationFirstName" name="first_name" value="{{ old('first_name') }}" class="form-control js-person-name @error('first_name') is-invalid @enderror" maxlength="80" pattern="[\p{L}\s.'\-]+" autocomplete="given-name" required @error('first_name') aria-invalid="true" aria-describedby="registrationFirstName-error" @enderror><x-ui.field-error field="first_name" id="registrationFirstName-error" /></div>
                    <div class="col-md-4"><label class="form-label" for="registrationMiddleName">Middle name <span class="cbis-optional-label">Optional</span></label><input id="registrationMiddleName" name="middle_name" value="{{ old('middle_name') }}" class="form-control js-person-name @error('middle_name') is-invalid @enderror" maxlength="80" pattern="[\p{L}\s.'\-]+" autocomplete="additional-name" @error('middle_name') aria-invalid="true" aria-describedby="registrationMiddleName-error" @enderror><x-ui.field-error field="middle_name" id="registrationMiddleName-error" /></div>
                    <div class="col-md-4"><label class="form-label" for="registrationLastName">Last name <span class="cbis-required-marker" aria-hidden="true">*</span></label><input id="registrationLastName" name="last_name" value="{{ old('last_name') }}" class="form-control js-person-name @error('last_name') is-invalid @enderror" maxlength="80" pattern="[\p{L}\s.'\-]+" autocomplete="family-name" required @error('last_name') aria-invalid="true" aria-describedby="registrationLastName-error" @enderror><x-ui.field-error field="last_name" id="registrationLastName-error" /></div>
                    <div class="col-md-4"><label class="form-label" for="birthDate">Birth date <span class="cbis-required-marker" aria-hidden="true">*</span></label><input id="birthDate" type="date" name="birth_date" value="{{ old('birth_date') }}" max="{{ today()->subDay()->toDateString() }}" class="form-control @error('birth_date') is-invalid @enderror" autocomplete="bday" required aria-describedby="birthDateHelp @error('birth_date') birthDate-error @enderror" @error('birth_date') aria-invalid="true" @enderror><x-ui.field-error field="birth_date" id="birthDate-error" /><small id="birthDateHelp" class="text-muted">Use a date before today. Donation is available from age {{ \App\Support\DonationAgePolicy::MINIMUM_AGE }}.</small></div>
                    <div class="col-md-4"><label class="form-label" for="registrationSex">Sex <span class="cbis-required-marker" aria-hidden="true">*</span></label><select id="registrationSex" name="sex" class="form-select @error('sex') is-invalid @enderror" required @error('sex') aria-invalid="true" aria-describedby="registrationSex-error" @enderror><option value="">Select sex</option><option value="male" @selected(old('sex') === 'male')>Male</option><option value="female" @selected(old('sex') === 'female')>Female</option></select><x-ui.field-error field="sex" id="registrationSex-error" /></div>
                    <div class="col-md-4 js-donor-field"><label class="form-label" for="registrationBloodType">Blood type <span class="cbis-required-marker" aria-hidden="true">*</span></label><select id="registrationBloodType" name="blood_type" class="form-select @error('blood_type') is-invalid @enderror" @required(in_array('donor', $registrationServices, true)) aria-describedby="registrationBloodTypeHelp @error('blood_type') registrationBloodType-error @enderror" @error('blood_type') aria-invalid="true" @enderror><option value="">Select blood type</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>@endforeach</select><x-ui.field-error field="blood_type" id="registrationBloodType-error" /><small id="registrationBloodTypeHelp" class="text-muted">Confirmed by staff during screening.</small></div>
                </div>
            </section>

            <section class="cbis-registration-section" aria-labelledby="registration-contact">
                <div class="cbis-registration-section-heading"><span>3</span><div><h2 id="registration-contact">Contact and address</h2><p>Used for verification and service updates.</p></div></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="registrationEmail">Email address <span class="cbis-required-marker" aria-hidden="true">*</span></label><input id="registrationEmail" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" required aria-describedby="registrationEmailHelp @error('email') registrationEmail-error @enderror" @error('email') aria-invalid="true" @enderror><x-ui.field-error field="email" id="registrationEmail-error" /><small id="registrationEmailHelp" class="text-muted">We will send your activation link here.</small></div>
                    <div class="col-md-6"><label class="form-label" for="registrationMobile">Mobile number <span class="cbis-required-marker" aria-hidden="true">*</span></label><input id="registrationMobile" name="contact_number" class="form-control js-mobile-local @error('contact_number') is-invalid @enderror" value="{{ \App\Support\PhilippinePhone::mobileLocal(old('contact_number')) }}" autocomplete="tel" inputmode="numeric" minlength="11" maxlength="11" pattern="09\d{9}" title="Enter exactly 11 digits starting with 09" placeholder="09171234567" required aria-describedby="registrationMobileHelp @error('contact_number') registrationMobile-error @enderror" @error('contact_number') aria-invalid="true" @enderror><x-ui.field-error field="contact_number" id="registrationMobile-error" /><small id="registrationMobileHelp" class="text-muted">11 digits starting with 09.</small></div>
                    <div class="col-12"><label class="form-label">Home address</label><x-negros-occidental-address-fields :address="old('address')" /></div>
                </div>
            </section>

            <section class="cbis-registration-section cbis-registration-section-wide" aria-labelledby="registration-security">
                <div class="cbis-registration-section-heading"><span>4</span><div><h2 id="registration-security">Account security</h2><p>Use 10+ characters with uppercase, lowercase, a number, and a symbol.</p></div></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="registrationPassword">Password <span class="cbis-required-marker" aria-hidden="true">*</span></label>
                        <input id="registrationPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="10" maxlength="255" aria-describedby="passwordStrength passwordStrengthFeedback passwordRequirements @error('password') registrationPassword-error @enderror" required @error('password') aria-invalid="true" @enderror>
                        <x-ui.field-error field="password" id="registrationPassword-error" />
                        <div class="cbis-password-strength mt-2" id="passwordStrength" aria-live="polite" aria-atomic="true">
                            <div class="cbis-password-strength-track" role="meter" aria-label="Estimated password strength" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-valuetext="Enter a password"><span></span></div>
                            <strong>Enter a password</strong>
                        </div>
                        <div id="passwordStrengthFeedback" class="cbis-password-feedback mt-1" hidden></div>
                        <small id="passwordRequirements" class="text-muted">Required: 10+ characters · uppercase · lowercase · number · symbol</small>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="registrationPasswordConfirmation">Confirm password <span class="cbis-required-marker" aria-hidden="true">*</span></label><input id="registrationPasswordConfirmation" type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password" minlength="10" required aria-describedby="passwordMatch @error('password_confirmation') registrationPasswordConfirmation-error @enderror" @error('password_confirmation') aria-invalid="true" @enderror><x-ui.field-error field="password_confirmation" id="registrationPasswordConfirmation-error" /><div id="passwordMatch" class="small mt-2" aria-live="polite"></div><label class="form-check mt-2"><input id="showRegistrationPassword" class="form-check-input" type="checkbox"> <span class="form-check-label small">Show passwords</span></label></div>
                </div>
            </section>

            <details class="cbis-registration-optional" @if($errors->has('identity_document')) open @endif>
                <summary><span><strong>Optional valid ID</strong><small>You can also upload this later</small></span></summary>
                <div class="row g-3 pt-3">
                    <div class="col-12"><label for="identity_document" class="form-label">Valid ID</label><input id="identity_document" type="file" name="identity_document" class="form-control @error('identity_document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="registrationIdHelp @error('identity_document') identity_document-error @enderror" @error('identity_document') aria-invalid="true" @enderror><x-ui.field-error field="identity_document" id="identity_document-error" /><small id="registrationIdHelp" class="text-muted">PDF, JPG, or PNG up to 5 MB.</small></div>
                </div>
            </details>
        </div>
        <div class="card-footer cbis-registration-footer cbis-mobile-form-actions"><p><strong>One last step</strong><span>You must verify your email before using account services.</span></p><button class="btn btn-danger px-4">Create account</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/zxcvbn.js') }}?v={{ filemtime(public_path('js/vendor/zxcvbn.js')) }}" defer></script>
<script src="{{ asset('js/password-estimator.js') }}?v={{ filemtime(public_path('js/password-estimator.js')) }}" defer></script>
<script src="{{ asset('js/password-strength.js') }}?v={{ filemtime(public_path('js/password-strength.js')) }}" defer></script>
<script src="{{ asset('js/registration-validation.js') }}?v={{ filemtime(public_path('js/registration-validation.js')) }}"></script>
@endpush
