@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageSchedules = ($currentUser?->can('manage schedules') ?? false);
@endphp
<div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Event Schedules</h1>
        <p class="cbis-page-subtitle">Plan and publish blood donation and bloodletting activities.</p>
    </div>
    @if($canManageSchedules)
        <a href="{{ route('donation-schedules.create') }}" class="btn btn-danger">Create Event</a>
    @endif
</div>

<form method="GET" class="card card-body mb-3 cbis-filter-card">
    <div class="row g-2">
        <div class="col-md-4"><label for="event-search" class="form-label">Search events</label><input id="event-search" class="form-control" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Title or venue"></div>
        <div class="col-md-3">
            <label class="form-label">Event Type</label>
            <select name="event_type" class="form-select">
                <option value="">All</option>
                <option value="blood_donation" @selected(request('event_type') === 'blood_donation')>Blood Donation</option>
                <option value="bloodletting" @selected(request('event_type') === 'bloodletting')>Bloodletting</option>
            </select>
        </div>
        @if(auth('web')->user()?->isCentralAdmin())
            <div class="col-md-3">
                <label class="form-label">Facility</label>
                <select name="facility_id" class="form-select">
                    <option value="">All</option>
                    @foreach($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((int) request('facility_id') === $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="event_date" class="form-control" value="{{ request('event_date') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                @foreach(['planned', 'ongoing', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label for="event-approval" class="form-label">QAO approval</label><select id="event-approval" name="approval_status" class="form-select"><option value="">All decisions</option>@foreach(['pending','approved','rejected'] as $approval)<option value="{{ $approval }}" @selected(request('approval_status') === $approval)>{{ ucfirst($approval) }}</option>@endforeach</select></div>
        <div class="col-12 d-flex gap-2 mt-3"><button class="btn btn-danger">Apply filters</button><a href="{{ route('donation-schedules.index') }}" class="btn btn-outline-secondary">Reset</a></div>
    </div>
</form>

<p class="small text-muted">{{ $schedules->total() }} matching {{ str('event')->plural($schedules->total()) }}</p>
<div class="cbis-event-list">
@forelse($schedules as $schedule)
    <article class="card cbis-schedule-card mb-3">
        <div class="card-body cbis-event-overview">
            <div>
                <div class="small text-muted mb-2">{{ $schedule->event_type_label }}</div>
                <h2 class="h5 cbis-event-name"><a href="{{ route('donation-schedules.show', $schedule) }}">{{ $schedule->title }}</a></h2>
                <p class="mb-1 fw-semibold">{{ $schedule->venue ?: 'Venue to be announced' }}</p>
                <p class="small text-muted mb-0">{{ $schedule->facility?->name ?? 'No facility assigned' }}</p>
            </div>
            <div>
                <div class="cbis-event-label">Schedule</div>
                <div class="fw-semibold">{{ $schedule->event_date?->format('M j, Y') }}</div>
                <div class="small text-muted mt-1">{{ $schedule->time_range_label }}</div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="badge {{ in_array($schedule->status, ['planned', 'ongoing']) ? 'cbis-status-active' : 'cbis-status-expired' }}">{{ ucfirst($schedule->status) }}</span>
                    <span class="badge text-bg-{{ $schedule->approval_status === 'approved' ? 'success' : ($schedule->approval_status === 'rejected' ? 'danger' : 'warning') }}">QAO: {{ ucfirst($schedule->approval_status) }}</span>
                </div>
            </div>
            <div>
                <div class="cbis-event-label">Registration status</div>
                <dl class="cbis-attendance-grid mb-0">
                    @foreach(['Registered'=>'registrations_count','Attended'=>'attended_count','No-show'=>'no_show_count','Cancelled'=>'cancelled_count'] as $label=>$count)
                    <div><dt>{{ $label }}</dt><dd>{{ $schedule->{$count} ?? 0 }}</dd></div>
                    @endforeach
                </dl>
            </div>
        </div>
        <div class="card-footer bg-white cbis-event-footer">
            <a href="{{ route('donation-schedules.show', $schedule) }}" class="btn btn-sm btn-outline-secondary">View event</a>
            @if($canManageSchedules || $currentUser?->can('review activities'))
            <details class="cbis-event-actions">
                <summary aria-label="More actions for {{ $schedule->title }}">More actions</summary>
                <div class="cbis-event-action-buttons">
                        @can('review activities')
                            <form method="POST" action="{{ route('donation-schedules.review',$schedule) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="approval_status" value="approved"><button class="btn btn-sm btn-outline-success">Approve</button></form>
                            <form method="POST" action="{{ route('donation-schedules.review',$schedule) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="approval_status" value="rejected"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                        @endcan
                        @if($canManageSchedules)
                            <a href="{{ route('donation-schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @if(in_array($schedule->status, ['planned', 'ongoing'], true))
                                <form
                                    method="POST"
                                    action="{{ route('donation-schedules.end', $schedule) }}"
                                    class="d-inline js-confirm-action"
                                    data-confirm-title="End event?"
                                    data-confirm-message="This will mark {{ $schedule->title }} as completed, remove it from public upcoming event listings, and mark remaining registered donors as no-show."
                                    data-confirm-button="End Event"
                                    data-confirm-variant="success"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success">End Event</button>
                                </form>
                            @endif
                            <form
                                method="POST"
                                action="{{ route('donation-schedules.destroy', $schedule) }}"
                                class="d-inline js-confirm-action"
                                data-confirm-title="Delete event?"
                                data-confirm-message="This will permanently remove {{ $schedule->title }} from the schedule list."
                                data-confirm-button="Delete Event"
                                data-confirm-variant="danger"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endif

                </div>
            </details>
            @endif
        </div>
    </article>
@empty
    <div class="card card-body cbis-empty-state py-5">
        <strong>No events to display</strong>
        <span>Create an event to begin, or clear your filters to see other schedules.</span>
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
            @if($canManageSchedules)<a href="{{ route('donation-schedules.create') }}" class="btn btn-danger">Create Event</a>@endif
            <a href="{{ route('donation-schedules.index') }}" class="btn btn-outline-secondary">Clear filters</a>
        </div>
    </div>
@endforelse
</div>

{{ $schedules->links() }}
@endsection
