@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h1 class="cbis-page-title mb-0">Available Bloods</h1>
    <p class="cbis-page-subtitle">Check which active blood banks currently have blood available right now.</p>
</div>

@include('public-portal.partials.nav')

<form method="GET" class="card card-body mb-3 cbis-filter-card">
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">Blood Type</label>
            <select name="blood_type" class="form-select">
                <option value="">All</option>
                @foreach($bloodTypes as $bloodType)
                    <option value="{{ $bloodType }}" @selected(request('blood_type') === $bloodType)>{{ $bloodType }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Blood Bank</label>
            <select name="facility_id" class="form-select">
                <option value="">All</option>
                @foreach($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected((int) request('facility_id') === $facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-outline-danger w-100">Filter Availability</button>
        </div>
    </div>
</form>

<div class="row g-3">
    @forelse($availabilityByFacility as $facilityAvailability)
        <div class="col-12">
            <div class="card cbis-card h-100">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $facilityAvailability['facility']->name }}</h2>
                            <p class="text-muted mb-0">{{ $facilityAvailability['facility']->address ?: 'Address not available' }}</p>
                        </div>
                        <div class="text-muted small">Showing only blood types that are currently available.</div>
                    </div>

                    <div class="row g-2">
                        @foreach($facilityAvailability['blood_types'] as $bloodType)
                            <div class="col-sm-6 col-lg-3">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="fw-semibold">{{ $bloodType }}</div>
                                    <span class="badge text-bg-success mt-2">Available</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card cbis-card">
                <div class="card-body text-center text-muted">
                    No blood banks currently have available blood for the selected filters.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
