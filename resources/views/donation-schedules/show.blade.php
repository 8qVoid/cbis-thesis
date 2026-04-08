@extends('layouts.app')
@section('content')
<h4 class="mb-3">Event Details</h4>
<div class="card card-body">
    <p><strong>Title:</strong> {{ $donationSchedule->title }}</p>
    <p><strong>Type:</strong> {{ $donationSchedule->event_type_label }}</p>
    <p><strong>Facility:</strong> {{ $donationSchedule->facility?->name ?? '-' }}</p>
    <p><strong>Date:</strong> {{ $donationSchedule->event_date?->toDateString() }}</p>
    <p><strong>Time:</strong> {{ $donationSchedule->start_time }} - {{ $donationSchedule->end_time }}</p>
    <p><strong>Venue / Address:</strong> {{ $donationSchedule->venue }}</p>
    <p><strong>Contact:</strong> {{ $donationSchedule->contact_person ?? '-' }} / {{ $donationSchedule->contact_number ?? '-' }}</p>
    <p><strong>Status:</strong> {{ ucfirst($donationSchedule->status) }}</p>
    <p><strong>Public:</strong> {{ $donationSchedule->is_public ? 'Yes' : 'No' }}</p>
    <p><strong>Description:</strong> {{ $donationSchedule->description ?: '-' }}</p>
    <p><strong>Coordinates:</strong> {{ $donationSchedule->latitude ?? '-' }}, {{ $donationSchedule->longitude ?? '-' }}</p>
</div>

<div class="card mt-3">
    <div class="card-header">Registered Donors</div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Donor Name</th>
                    <th>Blood Type</th>
                    <th>Contact</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($donationSchedule->eventRegistrations as $registration)
                    <tr>
                        <td>{{ $registration->donor?->full_name ?? '-' }}</td>
                        <td>{{ $registration->donor?->blood_type ?? '-' }}</td>
                        <td>{{ $registration->donor?->contact_number ?? '-' }}</td>
                        <td>{{ $registration->registered_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No donor registrations yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
