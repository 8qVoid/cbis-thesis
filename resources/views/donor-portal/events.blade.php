@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">My Event Registrations</h4>
    <a href="{{ route('donor.portal.profile') }}" class="btn btn-outline-secondary btn-sm">Back to Profile</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Type</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $registration)
                        <tr>
                            <td>{{ $registration->event?->title ?? '-' }}</td>
                            <td>{{ $registration->event?->event_type_label ?? '-' }}</td>
                            <td>{{ $registration->event?->facility?->name ?? '-' }}</td>
                            <td>{{ $registration->event?->event_date?->toDateString() ?? '-' }}</td>
                            <td>{{ $registration->event?->start_time ?? '-' }} - {{ $registration->event?->end_time ?? '-' }}</td>
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
                            <td colspan="7" class="text-center">No event registrations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $registrations->links() }}
</div>
@endsection
