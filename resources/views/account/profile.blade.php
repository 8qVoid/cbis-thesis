@extends('layouts.app')
@section('content')
<h1 class="cbis-page-title">Account Services</h1>
<p class="text-muted">Use one login for donating blood, requesting blood, or both.</p>
<form method="POST" action="{{ route('account.profile.update') }}" class="card card-body" style="max-width:720px">
    @csrf @method('PUT')
    <div class="form-check mb-2"><input class="form-check-input js-service" type="checkbox" name="services[]" value="donor" id="serviceDonor" @checked(old('services') ? in_array('donor', old('services', []), true) : $user->hasDonorAccess())><label class="form-check-label" for="serviceDonor"><strong>Donor</strong> — join donation activities and view donation history.</label></div>
    <div class="form-check mb-3"><input class="form-check-input js-service" type="checkbox" name="services[]" value="patient" id="servicePatient" @checked(old('services') ? in_array('patient', old('services', []), true) : $user->hasPatientAccess())><label class="form-check-label" for="servicePatient"><strong>Patient</strong> — submit and track blood requests.</label></div>
    <div id="bloodTypeGroup" class="mb-3"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select"><option value="">Select blood type</option>@foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)<option value="{{ $type }}" @selected(old('blood_type', $user->donorProfile?->blood_type) === $type)>{{ $type }}</option>@endforeach</select><small class="text-muted">This does not confirm medical eligibility. Staff confirms eligibility separately.</small></div>
    <div><button class="btn btn-danger">Save Services</button> <a href="{{ route('account.dashboard') }}" class="btn btn-outline-secondary">Cancel</a></div>
</form>
@endsection
@push('scripts')<script>(()=>{const d=document.getElementById('serviceDonor'),g=document.getElementById('bloodTypeGroup'),s=g?.querySelector('select');function sync(){g?.classList.toggle('d-none',!d?.checked);if(s)s.required=!!d?.checked;}d?.addEventListener('change',sync);sync();})();</script>@endpush
