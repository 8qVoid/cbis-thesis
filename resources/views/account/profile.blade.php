@extends('layouts.app')
@section('content')
@php($requestedService = in_array(request('service'), ['donor', 'patient'], true) ? request('service') : old('continue_to'))
@if(in_array($requestedService, ['donor', 'patient'], true))
<div class="cbis-dashboard-heading"><div><div class="cbis-eyebrow">One-time service setup</div><h1 class="cbis-page-title">{{ $requestedService === 'donor' ? 'Start Donating Blood' : 'Request Blood' }}</h1><p class="cbis-page-subtitle">Use your existing account and personal information.</p></div></div>
<form method="POST" action="{{ route('account.profile.update') }}" class="card card-body" style="max-width:720px">@csrf @method('PUT')
<input type="hidden" name="continue_to" value="{{ $requestedService }}">
@foreach(['donor', 'patient'] as $service)
@if($service === $requestedService || ($service === 'donor' ? $user->hasDonorAccess() : $user->hasPatientAccess()))<input type="hidden" name="services[]" value="{{ $service }}">@endif
@endforeach
<span class="cbis-action-icon mb-3"><x-ui.icon :name="$requestedService === 'donor' ? 'drop' : 'report'" /></span>
<h2 class="h5">{{ $requestedService === 'donor' ? 'Enable Donor Services' : 'Enable Patient Services' }}</h2>
<p class="text-muted">{{ $requestedService === 'donor' ? 'Find donation events, register to participate, and keep your donation history in this account.' : 'Submit a blood request and track its status. You will upload your ID and doctor’s blood request on the next page.' }}</p>
@if($requestedService === 'donor' || $user->hasDonorAccess())
@if($user->donorProfile)<input type="hidden" name="blood_type" value="{{ $user->donorProfile->blood_type }}">
@else<label for="setupBloodType" class="form-label">Blood Type</label><select id="setupBloodType" name="blood_type" class="form-select mb-3" required><option value="">Select blood type</option>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>@endforeach</select>@endif
@endif
<div class="d-flex flex-wrap gap-2 mt-2"><button class="btn btn-danger" type="submit">{{ $requestedService === 'donor' ? 'Enable & Find Events' : 'Enable & Continue to Request' }}</button><a class="btn btn-outline-secondary" href="{{ route('account.dashboard') }}">Cancel</a></div>
</form>
@else
<div class="cbis-dashboard-heading"><div><div class="cbis-eyebrow">Your account</div><h1 class="cbis-page-title">Patient/Donor Services</h1><p class="cbis-page-subtitle">Choose the services you need. Select both to use Patient/Donor services with one account.</p></div><a class="btn btn-outline-secondary" href="{{ route('account.details.edit') }}">Back to My Profile</a></div>
<form method="POST" action="{{ route('account.profile.update') }}" class="card card-body cbis-services-form">
    @csrf @method('PUT')
    <fieldset><legend class="h6 mb-3">Select your services</legend><div class="cbis-service-options">
    <label class="cbis-service-option" for="servicePatient"><input class="form-check-input js-service" type="checkbox" name="services[]" value="patient" id="servicePatient" @checked(in_array('patient', old('services', $user->hasPatientAccess() ? ['patient'] : []), true))><span><x-ui.icon name="report" /><strong>Patient</strong><small>Request blood and track your reservations.</small></span></label>
    <label class="cbis-service-option" for="serviceDonor"><input class="form-check-input js-service" type="checkbox" name="services[]" value="donor" id="serviceDonor" @checked(in_array('donor', old('services', $user->hasDonorAccess() ? ['donor'] : []), true))><span><x-ui.icon name="drop" /><strong>Donor</strong><small>Join donation events and view your history.</small></span></label>
    </div></fieldset>
    <div id="bloodTypeGroup" class="my-4"><label for="servicesBloodType" class="form-label">Blood Type</label><select id="servicesBloodType" name="blood_type" class="form-select"><option value="">Select blood type</option>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option value="{{ $type }}" @selected(old('blood_type', $user->donorProfile?->blood_type) === $type)>{{ $type }}</option>@endforeach</select><small class="text-muted">Blood Bank Staff confirms donation eligibility.</small></div>
    <div><button class="btn btn-danger">Save Services</button> <a href="{{ route('account.dashboard') }}" class="btn btn-outline-secondary">Cancel</a></div>
</form>
@endif
@endsection
@push('scripts')<script>(()=>{const d=document.getElementById('serviceDonor'),g=document.getElementById('bloodTypeGroup'),s=g?.querySelector('select');function sync(){g?.classList.toggle('d-none',!d?.checked);if(s)s.required=!!d?.checked;}d?.addEventListener('change',sync);sync();})();</script>@endpush
