@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Donor Profile</h4>
</div>
<form method="POST" action="{{ route('donor.portal.profile.update') }}" class="card card-body">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" value="{{ old('first_name',$donor->first_name) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" value="{{ old('last_name',$donor->last_name) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Middle Name</label><input name="middle_name" value="{{ old('middle_name',$donor->middle_name) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" value="{{ old('birth_date',$donor->birth_date?->toDateString()) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select"><option value="male" @selected($donor->sex==='male')>male</option><option value="female" @selected($donor->sex==='female')>female</option></select></div>
        <div class="col-md-4"><label class="form-label">Blood Type</label><select name="blood_type" class="form-select">@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)<option value="{{ $type }}" @selected($donor->blood_type===$type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Home Facility (Optional)</label><select name="facility_id" class="form-select"><option value="">No default facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((int) old('facility_id', $donor->facility_id ?? 0) === $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Contact Number</label><input name="contact_number" value="{{ old('contact_number',$donor->contact_number) }}" class="form-control" placeholder="+63 917 123 4567 or 09171234567"></div>
        <div class="col-md-6"><label class="form-label">Address</label><input name="address" value="{{ old('address',$donor->address) }}" class="form-control"></div>
        <div class="col-12"><button class="btn btn-danger">Update Profile</button></div>
    </div>
</form>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>My Event Registrations</span>
        <a href="{{ route('donor.events.index') }}" class="btn btn-sm btn-outline-danger">View All</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
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
                    <tr>
                        <td>{{ $registration->event?->title ?? '-' }}</td>
                        <td>{{ $registration->event?->facility?->name ?? '-' }}</td>
                        <td>{{ $registration->event?->event_date?->toDateString() ?? '-' }}</td>
                        <td>{{ ucfirst($registration->status) }}</td>
                        <td>
                            @if($registration->status === 'registered' && $registration->event)
                                <form method="POST" action="{{ route('donor.events.cancel', $registration->event) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No event registrations yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">My Donation History</div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
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
                    <tr>
                        <td>{{ $record->donation_no }}</td>
                        <td>{{ $record->donated_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $record->blood_type }}</td>
                        <td>{{ $record->volume_ml }}</td>
                        <td>{{ $record->status }}</td>
                        <td>{{ $record->bloodlettingRecord->verification_status ?? 'N/A' }}</td>
                        <td>{{ $record->expiration_date?->toDateString() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No donation records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
