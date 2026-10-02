@extends('layouts.app')

@section('content')
@php
    $homeFacilityName = $donor->facility?->name ?? 'Not set';
    $donorFullName = collect([$donor->first_name, $donor->middle_name, $donor->last_name])
        ->filter(fn ($name) => filled($name))
        ->implode(' ');
@endphp
<div class="cbis-donor-hero mb-3">
    <div>
        <div class="cbis-eyebrow">Profile</div>
        <h1 class="cbis-page-title mb-1">{{ $donorFullName }}</h1>
        <p class="cbis-page-subtitle">Keep your contact details updated and track your blood donation activity.</p>
    </div>
    <div class="cbis-donor-summary">
        <div class="cbis-donor-summary-item">
            <span>Blood type</span>
            <strong>{{ $donor->blood_type }}</strong>
        </div>
        <div class="cbis-donor-summary-item">
            <span>Home facility</span>
            <strong title="{{ $homeFacilityName }}">{{ $homeFacilityName }}</strong>
        </div>
        <div class="cbis-donor-summary-item">
            <span>Event registrations</span>
            <strong>{{ $eventRegistrations->count() }}</strong>
        </div>
    </div>
</div>

<form
    method="POST"
    action="{{ route('donor.portal.profile.update') }}"
    class="card cbis-profile-card js-confirm-action"
    data-confirm-title="Update profile?"
    data-confirm-message="Are you sure you want to update your profile? Please check your information before continuing."
    data-confirm-button="Update Profile"
    data-confirm-variant="danger"
>
    @csrf
    @method('PUT')
    <div class="card-header">
        <div>
            <div class="fw-bold">Personal information</div>
            <div class="text-muted small">This information helps facilities identify your records during events and donations.</div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" value="{{ old('first_name',$donor->first_name) }}" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+" required></div>
            <div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" value="{{ old('last_name',$donor->last_name) }}" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+" required></div>
            <div class="col-md-4"><label class="form-label">Middle Name</label><input name="middle_name" value="{{ old('middle_name',$donor->middle_name) }}" class="form-control js-person-name" maxlength="80" pattern="[\p{L}\s.'-]+"></div>
            <div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" value="{{ old('birth_date',$donor->birth_date?->toDateString()) }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option value="male" @selected($donor->sex==='male')>Male</option><option value="female" @selected($donor->sex==='female')>Female</option></select></div>
            <div class="col-md-4"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option value="{{ $type }}" @selected($donor->blood_type===$type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((int) old('facility_id', $donor->facility_id ?? 0) === $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
            <div class="col-md-6">
                <label class="form-label">Mobile Number</label>
                <input name="contact_number" value="{{ \App\Support\PhilippinePhone::mobileLocal(old('contact_number', $donor->contact_number)) }}" class="form-control js-mobile-local" inputmode="numeric" minlength="11" maxlength="11" pattern="09\d{9}" title="Enter exactly 11 digits starting with 09" placeholder="09171234567" required>
                <small class="text-muted">Enter all 11 digits, starting with 09.</small>
            </div>
            <div class="col-12"><label class="form-label">Address</label><x-negros-occidental-address-fields :address="old('address',$donor->address)" /></div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end"><button class="btn btn-danger">Update profile</button></div>
</form>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>My Event Registrations</span>
        <a href="{{ route('donor.events.index') }}" class="btn btn-sm btn-outline-danger">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive cbis-mobile-table-wrap">
        <table class="table table-striped mb-0 cbis-mobile-card-table cbis-status-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Facility</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventRegistrations as $registration)
                    <tr class="cbis-reservation-row">
                        <td data-label="Event">{{ $registration->event?->title ?? '-' }}</td>
                        <td data-label="Facility">{{ $registration->event?->facility?->name ?? '-' }}</td>
                        <td data-label="Date">{{ $registration->event?->event_date?->format('M j, Y') ?? '-' }}</td>
                        <td data-label="Status"><span class="cbis-inline-status {{ $registration->status === 'registered' ? 'cbis-tone-success' : ($registration->status === 'cancelled' ? 'cbis-tone-danger' : 'cbis-tone-warning') }}">{{ $registration->status === 'no_show' ? 'No-show' : ucfirst($registration->status) }}</span></td>
                        <td data-label="Action" class="cbis-record-actions">
                            @if($registration->status === 'registered' && $registration->event?->isRegistrationOpen())
                                <form
                                    method="POST"
                                    action="{{ route('donor.events.cancel', $registration->event) }}"
                                    class="d-inline js-confirm-action"
                                    data-confirm-title="Cancel registration?"
                                    data-confirm-message="This will cancel your registration for {{ $registration->event->title }}."
                                    data-confirm-button="Cancel Registration"
                                    data-confirm-variant="danger"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="cbis-empty-state">
                                <strong>No event registrations yet</strong>
                                <span>Browse public events and register for an upcoming activity.</span>
                                <a href="{{ route('public.map') }}" class="btn btn-sm btn-outline-danger">Find events</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">My Donation History</div>
    <div class="card-body p-0">
        <div class="table-responsive cbis-mobile-table-wrap">
        <table class="table table-striped mb-0 cbis-mobile-card-table cbis-status-table">
            <thead>
                <tr>
                    <th>Donation No.</th>
                    <th>Date</th>
                    <th>Blood Type</th>
                    <th>Volume (ml)</th>
                    <th>Record Status</th>
                    <th>Bloodletting Verification</th>
                    <th>Expiration Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($donationHistory as $record)
                    <tr class="cbis-reservation-row">
                        <td data-label="Donation no."><strong>{{ $record->donation_no }}</strong></td>
                        <td data-label="Date">{{ $record->donated_at?->format('M j, Y · g:i A') }}</td>
                        <td data-label="Blood type">{{ $record->blood_type }}</td>
                        <td data-label="Volume">{{ $record->volume_ml }} ml</td>
                        <td data-label="Record status"><span class="cbis-inline-status {{ $record->status === 'verified' ? 'cbis-tone-success' : 'cbis-tone-warning' }}">{{ str($record->status)->headline() }}</span></td>
                        <td data-label="Bloodletting">{{ str($record->bloodlettingRecord->verification_status ?? 'N/A')->headline() }}</td>
                        <td data-label="Expiry">{{ $record->expiration_date?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="cbis-empty-state">
                                <strong>No donation records yet</strong>
                                <span>Your completed donation records will appear here after a facility records them.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
