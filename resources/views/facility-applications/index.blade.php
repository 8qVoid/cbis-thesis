@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Facility Applications</h1>
        <p class="cbis-page-subtitle">Review legitimacy and DOH accreditation submissions.</p>
    </div>
</div>

<form method="GET" class="card card-body mb-3 cbis-filter-card">
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                @foreach(['pending', 'approved', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-outline-danger w-100">Filter</button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped bg-white">
        <thead>
            <tr>
                <th>Organization</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Reviewed By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->organization_name }}</td>
                    <td>{{ $application->facility_type }}</td>
                    <td>{{ $application->contact_person }} / {{ $application->contact_number }}</td>
                    <td><span class="badge {{ $application->status === 'approved' ? 'cbis-status-active' : ($application->status === 'rejected' ? 'cbis-status-expired' : 'cbis-status-low') }}">{{ ucfirst($application->status) }}</span></td>
                    <td>{{ $application->reviewer?->name ?? '-' }}</td>
                    <td><a href="{{ route('facility-applications.show', $application) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No applications found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $applications->links() }}
@endsection
