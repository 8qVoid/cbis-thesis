@extends('layouts.app')

@section('content')
@php
    $reservationSubmittedType = \App\Notifications\BloodReservationSubmitted::class;
    $reservationStatusType = \App\Notifications\BloodReservationStatusChanged::class;
    $activityReviewType = \App\Notifications\ActivityReviewStatusChanged::class;
    $eventPostedType = \App\Notifications\EventPostedNotification::class;
    $reportSubmittedType = \App\Notifications\ReportRequestSubmitted::class;
    $reportReviewedType = \App\Notifications\ReportRequestReviewed::class;
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Notifications</h1>
        <p class="cbis-page-subtitle">Your reservation, inventory, activity, and event updates.</p>
    </div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm">Mark all read</button>
    </form>
</div>

<form method="GET" class="card card-body mb-3 cbis-filter-card" data-auto-filter="true">
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="all" @selected(($status ?? 'all') === 'all')>All</option>
                <option value="unread" @selected(($status ?? 'all') === 'unread')>Unread</option>
            </select>
        </div>
        @if(auth()->user()->isQao() || auth()->user()->isBloodBankStaff())
            <div class="col-md-3">
                <label class="form-label">Alert Type</label>
                <select name="type" class="form-select">
                    <option value="all" @selected(($alertType ?? 'all') === 'all')>All alerts</option>
                    <option value="low_stock" @selected(($alertType ?? 'all') === 'low_stock')>Low blood stock</option>
                    <option value="reservation" @selected(($alertType ?? 'all') === 'reservation')>Blood reservations</option>
                    <option value="report" @selected(($alertType ?? 'all') === 'report')>Inventory reports</option>
                </select>
            </div>
        @endif
        @if(auth()->user()->hasAnyRole(['Donor', 'Patient']))
            <div class="col-md-3">
                <label class="form-label">Update Type</label>
                <select name="type" class="form-select">
                    <option value="all" @selected(($alertType ?? 'all') === 'all')>All updates</option>
                    @if(auth()->user()->hasPatientAccess())<option value="reservation_status" @selected(($alertType ?? 'all') === 'reservation_status')>Blood requests</option>@endif
                    @if(auth()->user()->hasDonorAccess())<option value="event" @selected(($alertType ?? 'all') === 'event')>Donation activities</option>@endif
                    @if(auth()->user()->hasDonorAccess())<option value="screening" @selected(($alertType ?? 'all') === 'screening')>Donation screening</option>@endif
                </select>
            </div>
        @endif
        <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
        </div>
    </div>
</form>

<div class="card" data-live-region="notification-list">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Details</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                        @endphp
                        <tr>
                            <td><a href="{{ route('notifications.open', $notification->id) }}" class="fw-semibold text-decoration-none">{{ $data['title'] ?? 'Notification' }}</a></td>
                            <td>
                                @if($notification->type === $reservationSubmittedType)
                                    <div>Reservation {{ $data['reference'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">{{ $data['blood_type'] ?? 'N/A' }} · {{ \App\Models\BloodInventory::COMPONENTS[$data['component'] ?? ''] ?? ($data['component'] ?? 'N/A') }}</div>
                                @elseif($notification->type === $activityReviewType)
                                    <div>{{ $data['activity_title'] ?? 'Activity' }}</div>
                                    <div class="text-muted small">Status: {{ str($data['approval_status'] ?? 'updated')->title() }}{{ !empty($data['review_notes']) ? ' · '.$data['review_notes'] : '' }}</div>
                                @elseif($notification->type === $reservationStatusType)
                                    <div>Reservation {{ $data['reference'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">Status: {{ str($data['status'] ?? 'updated')->headline() }}{{ !empty($data['review_notes']) ? ' · '.$data['review_notes'] : '' }}</div>
                                @elseif(in_array($notification->type, [$reportSubmittedType, $reportReviewedType], true))
                                    <div>{{ \App\Models\ReportRequest::TYPES[$data['report_type'] ?? ''] ?? 'Report' }}</div>
                                    <div class="text-muted small">{{ $data['requester_name'] ?? 'Request' }} · {{ str($data['status'] ?? 'updated')->headline() }}{{ !empty($data['review_notes']) ? ' · '.$data['review_notes'] : '' }}</div>
                                @elseif($notification->type === \App\Notifications\DonorScreeningUpdated::class)
                                    <div>Screening: {{ str($data['status'] ?? 'awaiting')->headline() }}</div>
                                    <div>{{ $data['donor_message'] ?? '' }}</div>
                                    <a href="{{ route('account.dashboard', ['view' => 'donor']) }}">View screening status</a>
                                @elseif($notification->type === $eventPostedType)
                                    <div>{{ $data['event_title'] ?? 'Donation activity' }}</div>
                                    <div class="text-muted small">{{ $data['event_date'] ?? 'Date to be announced' }} · {{ $data['facility_name'] ?? 'Facility to be announced' }}</div>
                                @else
                                    <div>{{ $data['facility_name'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">
                                        {{ $data['blood_type'] ?? 'N/A' }} | {{ $data['units_available'] ?? 'N/A' }} units | Expires {{ $data['expiration_date'] ?? 'N/A' }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $notification->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($notification->read_at)
                                    <span class="badge text-bg-secondary">Read</span>
                                @else
                                    <span class="badge cbis-status-low">Unread</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('notifications.open', $notification->id) }}" class="btn btn-sm btn-outline-danger">Open</a>
                                    @if($notification->read_at === null)
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-primary">Mark read</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4"><strong>You’re all caught up</strong><div class="small text-muted mt-1">New updates for your account will appear here.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3" data-live-region="notification-pages">
    {{ $notifications->links() }}
</div>
@endsection
