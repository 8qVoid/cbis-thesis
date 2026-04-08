@extends('layouts.app')
@section('content')
<h4>Apply as Blood Bank / Hospital</h4>
<p class="text-muted">Submit your organization details and proof of legitimacy and DOH accreditation. Philippine Red Cross will review your application.</p>

<form method="POST" action="{{ route('facility-application.store') }}" enctype="multipart/form-data" class="card card-body">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Organization Name</label>
            <input name="organization_name" class="form-control" value="{{ old('organization_name') }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="facility_type" class="form-select" required>
                <option value="blood_bank" @selected(old('facility_type') === 'blood_bank')>Blood Bank</option>
                <option value="hospital" @selected(old('facility_type') === 'hospital')>Hospital</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Contact Person</label>
            <input name="contact_person" class="form-control" value="{{ old('contact_person') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Contact Number</label>
            <input name="contact_number" class="form-control" value="{{ old('contact_number') }}" placeholder="+63 917 123 4567 or 09171234567" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">DOH Accreditation Number</label>
            <input name="doh_accreditation_number" class="form-control" value="{{ old('doh_accreditation_number') }}">
        </div>
        <div class="col-12">
            <label class="form-label">Address</label>
            <input name="address" class="form-control" value="{{ old('address') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Proof of Legitimacy (PDF/JPG/PNG)</label>
            <input type="file" name="legitimacy_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">DOH Accreditation Proof (PDF/JPG/PNG)</label>
            <input type="file" name="doh_accreditation_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div class="col-12">
            <button class="btn btn-danger">Submit Application</button>
        </div>
    </div>
</form>
@endsection
