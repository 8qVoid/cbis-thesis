@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">Create Your Account</div>
            <div class="card-body">
                <form method="POST" action="{{ route('donor.register.store') }}" class="js-confirm-action" data-confirm-title="Check your information" data-confirm-message="Please make sure your name, birth date, blood type, email, mobile number, and address are correct before continuing." data-confirm-button="Continue Registration" data-confirm-variant="danger">
                    @csrf
                    @if($selectedEvent)
                        <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
                    @endif
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">What would you like to do?</label>
                            <div class="d-flex flex-wrap gap-3">
                                <label class="form-check"><input class="form-check-input js-service" type="checkbox" name="services[]" value="donor" @checked(in_array('donor', old('services', ['donor']), true))> <span class="form-check-label">Donor — I want to donate blood</span></label>
                                <label class="form-check"><input class="form-check-input js-service" type="checkbox" name="services[]" value="patient" @checked(in_array('patient', old('services', []), true))> <span class="form-check-label">Patient — I want to request blood</span></label>
                            </div>
                            <small class="text-muted">Choose one or both. You will use one login for all selected services.</small>
                        </div>
                        @if($selectedEvent)
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    You are signing up for event: <strong>{{ $selectedEvent->title }}</strong>
                                    ({{ $selectedEvent->event_date?->toDateString() }}) at {{ $selectedEvent->facility?->name ?? '-' }}.
                                </div>
                            </div>
                        @endif
                        <div class="col-md-4"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((int) old('facility_id', $selectedFacilityId ?? 0) === $facility->id)>{{ $facility->name }}</option>@endforeach</select><small class="text-muted">Used as your default preference only.</small></div>
                        <div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+" required></div>
                        <div class="col-md-4"><label class="form-label">Middle Name</label><input name="middle_name" value="{{ old('middle_name') }}" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+"></div>
                        <div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+" required></div>
                        <div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option>male</option><option>female</option></select></div>
                        <div class="col-md-4 js-donor-field"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select"><option value="">Select blood type</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option @selected(old('blood_type')===$type)>{{ $type }}</option>@endforeach</select><small class="text-muted">Staff will verify eligibility during screening.</small></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text">09</span>
                                <input name="contact_number" class="form-control js-mobile-suffix" value="{{ \App\Support\PhilippinePhone::mobileSuffix(old('contact_number')) }}" inputmode="numeric" maxlength="9" pattern="\d{9}" placeholder="123456789" required>
                            </div>
                            <small class="text-muted">Enter the 9 digits after 09.</small>
                        </div>
                        <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address') }}" required></div>
                        <div class="col-12">
                            <hr class="my-1">
                            <h5 class="mb-1">Account password</h5>
                            <p class="text-muted small mb-0">Use this password for your Donor and/or Patient services.</p>
                        </div>
                        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                        <div class="col-12"><button class="btn btn-danger">Register</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const serviceInputs = [...document.querySelectorAll('.js-service')];
const donorFields = [...document.querySelectorAll('.js-donor-field')];
function updateServiceFields() {
    const donorEnabled = serviceInputs.some(input => input.value === 'donor' && input.checked);
    donorFields.forEach(field => { field.hidden = !donorEnabled; field.querySelector('select').required = donorEnabled; });
}
serviceInputs.forEach(input => input.addEventListener('change', updateServiceFields));
updateServiceFields();
</script>
@endpush
