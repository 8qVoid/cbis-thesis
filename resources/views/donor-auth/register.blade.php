@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">Create Donor Account</div>
            <div class="card-body">
                <form method="POST" action="{{ route('donor.register.store') }}">
                    @csrf
                    @if($selectedEvent)
                        <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
                    @endif
                    <div class="row g-3">
                        @if($selectedEvent)
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    You are signing up for event: <strong>{{ $selectedEvent->title }}</strong>
                                    ({{ $selectedEvent->event_date?->toDateString() }}) at {{ $selectedEvent->facility?->name ?? '-' }}.
                                </div>
                            </div>
                        @endif
                        <div class="col-md-4"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((int) old('facility_id', $selectedFacilityId ?? 0) === $facility->id)>{{ $facility->name }}</option>@endforeach</select><small class="text-muted">Used as your default preference only.</small></div>
                        <div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option>male</option><option>female</option></select></div>
                        <div class="col-md-4"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" placeholder="+63 917 123 4567 or 09171234567"></div>
                        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control"></div>
                        <div class="col-12"><button class="btn btn-danger">Register</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
