@extends('layouts.app')

@section('content')
@php
    $facilityApplicationType = \App\Notifications\FacilityApplicationSubmitted::class;
    $isCentralAdmin = auth('web')->user()?->isCentralAdmin() ?? false;
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Notifications</h1>
        <p class="cbis-page-subtitle">{{ $isCentralAdmin ? 'Facility application alerts for central review.' : 'Low stock alerts for your assigned facility.' }}</p>
    </div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm">Mark all read</button>
    </form>
</div>

<form method="GET" class="card card-body mb-3 cbis-filter-card">
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="all" @selected(($status ?? 'all') === 'all')>All</option>
                <option value="unread" @selected(($status ?? 'all') === 'unread')>Unread</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-outline-danger w-100">Filter</button>
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
                                @if($notification->type === $facilityApplicationType)
                                    <div>{{ $data['organization_name'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">
                                        {{ $data['facility_type'] ?? 'N/A' }} | {{ $data['contact_person'] ?? 'N/A' }} | {{ $data['email'] ?? 'N/A' }}
                                    </div>
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
