@extends('layouts.app')

@section('content')
@php
    $reservationSubmittedType = \App\Notifications\BloodReservationSubmitted::class;
    $activityReviewType = \App\Notifications\ActivityReviewStatusChanged::class;
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Notifications</h1>
        <p class="cbis-page-subtitle">Role-specific reservation, inventory, and activity alerts.</p>
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

<div class="card">
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
                            <td>{{ $data['title'] ?? 'Low stock alert' }}</td>
                            <td>
                                @if($notification->type === $reservationSubmittedType)
                                    <div>Reservation {{ $data['reference'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">{{ $data['blood_type'] ?? 'N/A' }} · {{ \App\Models\BloodInventory::COMPONENTS[$data['component'] ?? ''] ?? ($data['component'] ?? 'N/A') }}</div>
                                @elseif($notification->type === $activityReviewType)
                                    <div>{{ $data['activity_title'] ?? 'Activity' }}</div>
                                    <div class="text-muted small">Status: {{ str($data['approval_status'] ?? 'updated')->title() }}{{ !empty($data['review_notes']) ? ' · '.$data['review_notes'] : '' }}</div>
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
                                @if($notification->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary">Mark as read</button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No notifications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $notifications->links() }}
</div>
@endsection
