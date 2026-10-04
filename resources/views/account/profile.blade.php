@extends('layouts.app')
@section('content')
@php($requestedService = in_array(request('service'), ['donor', 'patient'], true) ? request('service') : old('continue_to'))
<div class="cbis-account-page">
@if(in_array($requestedService, ['donor', 'patient'], true))
<div class="cbis-page-heading"><div><h1 class="cbis-page-title">{{ $requestedService === 'donor' ? 'Start Donating Blood' : 'Request Blood' }}</h1><p class="cbis-page-subtitle">Use your existing account and personal information.</p></div></div>
<form method="POST" action="{{ route('account.profile.update') }}" class="card cbis-services-form cbis-compact-form">@csrf @method('PUT')
<input type="hidden" name="continue_to" value="{{ $requestedService }}">
@foreach(['donor', 'patient'] as $service)
@if($service === $requestedService || ($service === 'donor' ? $user->hasDonorAccess() : $user->hasPatientAccess()))<input type="hidden" name="services[]" value="{{ $service }}">@endif
@endforeach
<section class="cbis-form-section">
    <div class="d-flex align-items-center gap-3 mb-3"><span class="cbis-action-icon"><x-ui.icon :name="$requestedService === 'donor' ? 'drop' : 'report'" /></span><h2 class="cbis-form-section-title mb-0">{{ $requestedService === 'donor' ? 'Enable Donor Services' : 'Enable Patient Services' }}</h2></div>
    <p class="cbis-form-section-help mb-0">{{ $requestedService === 'donor' ? 'Find donation events, register to participate, and keep your donation history in this account.' : 'Submit a blood request and track its status. You will upload your ID and doctor’s blood request on the next page.' }}</p>
</section>
@if($requestedService === 'donor' || $user->hasDonorAccess())
@if($user->donorProfile)<input type="hidden" name="blood_type" value="{{ $user->donorProfile->blood_type }}">
@else<fieldset class="cbis-form-section"><legend class="cbis-form-section-title">Donation information</legend><p class="cbis-form-section-help cbis-required-note"><span class="cbis-required-marker" aria-hidden="true">*</span> Required fields</p><label for="setupBloodType" class="form-label">Blood Type <span class="cbis-required-marker" aria-hidden="true">*</span></label><select id="setupBloodType" name="blood_type" class="form-select @error('blood_type') is-invalid @enderror" required @error('blood_type') aria-invalid="true" aria-describedby="setupBloodType-error" @enderror><option value="">Select blood type</option>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>@endforeach</select><x-ui.field-error field="blood_type" id="setupBloodType-error" /></fieldset>@endif
@endif
<div class="cbis-form-actions cbis-mobile-form-actions"><button class="btn btn-danger" type="submit">{{ $requestedService === 'donor' ? 'Enable & Find Events' : 'Enable & Continue to Request' }}</button><a class="btn btn-outline-secondary" href="{{ route('account.dashboard') }}">Cancel</a></div>
</form>
@else
<div class="cbis-page-heading"><div><h1 class="cbis-page-title">Services</h1><p class="cbis-page-subtitle">Choose one or both services for this account.</p></div><a class="btn btn-outline-secondary" href="{{ route('account.details.edit') }}">Back to Profile</a></div>
<form method="POST" action="{{ route('account.profile.update') }}" class="card cbis-services-form cbis-compact-form">
    @csrf @method('PUT')
    @php($servicesError = $errors->first('services') ?: $errors->first('services.0') ?: $errors->first('services.1'))
    <fieldset class="cbis-form-section"><legend class="cbis-form-section-title">Select your services</legend><p id="servicesHelp" class="cbis-form-section-help cbis-required-note">Select at least one service. <span class="cbis-required-marker" aria-hidden="true">*</span> Required fields</p><div class="cbis-service-options">
    <label class="cbis-service-option" for="servicePatient"><input class="form-check-input js-service @if($servicesError) is-invalid @endif" type="checkbox" name="services[]" value="patient" id="servicePatient" aria-describedby="servicesHelp @if($servicesError) services-error @endif" @if($servicesError) aria-invalid="true" @endif @checked(in_array('patient', old('services', $user->hasPatientAccess() ? ['patient'] : []), true))><span><x-ui.icon name="report" /><strong>Patient</strong><small>Request blood and track your reservations.</small></span></label>
    <label class="cbis-service-option" for="serviceDonor"><input class="form-check-input js-service @if($servicesError) is-invalid @endif" type="checkbox" name="services[]" value="donor" id="serviceDonor" aria-describedby="servicesHelp @if($servicesError) services-error @endif" @if($servicesError) aria-invalid="true" @endif @checked(in_array('donor', old('services', $user->hasDonorAccess() ? ['donor'] : []), true))><span><x-ui.icon name="drop" /><strong>Donor</strong><small>Join donation events and view your history.</small></span></label>
    </div>@if($servicesError)<div id="services-error" class="cbis-field-error invalid-feedback d-block" role="alert">{{ $servicesError }}</div>@endif</fieldset>
    <div id="bloodTypeGroup" class="cbis-form-section"><label for="servicesBloodType" class="form-label">Blood Type <span class="cbis-required-marker" aria-hidden="true">*</span></label><select id="servicesBloodType" name="blood_type" class="form-select @error('blood_type') is-invalid @enderror" aria-describedby="servicesBloodTypeHelp @error('blood_type') servicesBloodType-error @enderror" @error('blood_type') aria-invalid="true" @enderror><option value="">Select blood type</option>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option value="{{ $type }}" @selected(old('blood_type', $user->donorProfile?->blood_type) === $type)>{{ $type }}</option>@endforeach</select><x-ui.field-error field="blood_type" id="servicesBloodType-error" /><small id="servicesBloodTypeHelp" class="text-muted">Blood Bank Staff confirms donation eligibility.</small></div>
    <div class="cbis-form-actions cbis-mobile-form-actions"><button class="btn btn-danger">Save Services</button><a href="{{ route('account.dashboard') }}" class="btn btn-outline-secondary">Cancel</a></div>
</form>
@endif
</div>
@endsection
@push('scripts')<script>(()=>{const d=document.getElementById('serviceDonor'),g=document.getElementById('bloodTypeGroup'),s=g?.querySelector('select');function sync(){g?.classList.toggle('d-none',!d?.checked);if(s)s.required=!!d?.checked;}d?.addEventListener('change',sync);sync();})();</script>@endpush
