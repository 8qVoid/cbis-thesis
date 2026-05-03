@extends('layouts.app')
@section('content')
<h4 class="mb-3">Facility Application Review</h4>

<div class="card card-body mb-3">
    <p><strong>Organization:</strong> {{ $application->organization_name }}</p>
    <p><strong>Type:</strong> {{ $application->facility_type }}</p>
    <p><strong>Contact Person:</strong> {{ $application->contact_person }}</p>
    <p><strong>Contact Number:</strong> {{ $application->contact_number }}</p>
    <p><strong>Email:</strong> {{ $application->email }}</p>
    <p><strong>Address:</strong> {{ $application->address }}</p>
    <p><strong>DOH Accreditation Number:</strong> {{ $application->doh_accreditation_number ?? '-' }}</p>
    <p><strong>Status:</strong> {{ ucfirst($application->status) }}</p>
    <p><strong>Reviewed By:</strong> {{ $application->reviewer?->name ?? '-' }}</p>
    <p><strong>Reviewed At:</strong> {{ $application->reviewed_at?->format('Y-m-d H:i') ?? '-' }}</p>
    <p><strong>Linked Facility:</strong> {{ $application->facility?->name ?? '-' }}</p>
    <p><strong>Legitimacy Proof:</strong> <a href="{{ route('facility-applications.proof', ['facilityApplication' => $application, 'type' => 'legitimacy']) }}" target="_blank">View File</a></p>
    <p><strong>DOH Accreditation Proof:</strong> <a href="{{ route('facility-applications.proof', ['facilityApplication' => $application, 'type' => 'doh']) }}" target="_blank">View File</a></p>
</div>

<form method="POST" action="{{ route('facility-applications.review', $application) }}" class="card card-body js-facility-review-form" data-current-status="{{ $application->status }}" data-has-facility="{{ $application->facility_id ? '1' : '0' }}">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Decision</label>
            <select name="status" class="form-select" required>
                <option value="pending" @selected(old('status', $application->status) === 'pending')>Pending</option>
                <option value="approved" @selected(old('status', $application->status) === 'approved')>Approve</option>
                <option value="rejected" @selected(old('status', $application->status) === 'rejected')>Reject</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Review Notes</label>
            <textarea name="review_notes" rows="4" class="form-control">{{ old('review_notes', $application->review_notes) }}</textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-danger">Save Review</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-facility-review-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const decision = form.querySelector('[name="status"]')?.value;
        const currentStatus = form.dataset.currentStatus;
        const hasFacility = form.dataset.hasFacility === '1';

        const messages = {
            approved: 'Approve this facility application? The system will create or reactivate the facility account and email a temporary password to the applicant.',
            rejected: hasFacility
                ? 'Reject this facility application? The linked facility and all assigned staff accounts will be deactivated, but records will be kept.'
                : 'Reject this facility application?',
            pending: hasFacility
                ? 'Move this application back to pending? The linked facility and all assigned staff accounts will be temporarily deactivated.'
                : 'Move this application back to pending?',
        };

        if (decision !== currentStatus && messages[decision] && !confirm(messages[decision])) {
            event.preventDefault();
        }
    });
});
</script>
@endpush
