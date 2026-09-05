@extends('layouts.app')
@section('content')
<div class="cbis-dashboard-heading">
    <div><div class="cbis-eyebrow">Event Facilitator</div><h1 class="cbis-page-title">Activity Dashboard</h1><p class="cbis-page-subtitle">{{ auth()->user()->facility?->name }} · Plan activities and follow Bacolod QAO decisions.</p></div>
    <a href="{{ route('donation-schedules.create') }}" class="btn btn-danger">Create New Activity</a>
</div>
<div class="cbis-metric-grid cbis-metric-grid-three mb-4">
    <x-ui.kpi-card label="Upcoming Activities" :value="$upcomingEvents" suffix="Planned or ongoing" />
    <x-ui.kpi-card label="Pending QAO Approval" :value="$pendingEvents" statusClass="{{ $pendingEvents ? 'text-warning' : 'text-success' }}" suffix="Submitted for review" />
    <x-ui.kpi-card label="Approved This Month" :value="$approvedThisMonth" suffix="Ready for public visibility" />
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="card h-100"><div class="card-header cbis-card-title"><span>Upcoming Activity</span><a href="{{ route('donation-schedules.index') }}">View all</a></div><div class="card-body">
        @if($nextEvent)
            <div class="cbis-feature-event"><div class="cbis-feature-event-icon">●</div><div><div class="d-flex flex-wrap align-items-start justify-content-between gap-2"><div><h2>{{ $nextEvent->title }}</h2><p>{{ $nextEvent->venue }}</p></div><a class="btn btn-sm btn-outline-danger" href="{{ route('donation-schedules.edit', $nextEvent) }}">Edit</a></div><dl class="cbis-detail-grid"><div><dt>Date</dt><dd>{{ $nextEvent->event_date?->format('F d, Y') }}</dd></div><div><dt>Time</dt><dd>{{ $nextEvent->time_range_label }}</dd></div><div><dt>Registrations</dt><dd>{{ $nextEvent->registrations_count }}</dd></div><div><dt>Status</dt><dd>{{ str($nextEvent->status)->title() }}</dd></div></dl></div></div>
            @php($steps = ['Draft', 'Submitted to Bacolod QAO', 'Approved', 'Public on Map'])
            @php($hasPublicPin = $nextEvent->is_public && $nextEvent->latitude !== null && $nextEvent->longitude !== null)
            @php($step = $nextEvent->approval_status === 'approved' ? ($hasPublicPin ? 4 : 3) : 2)
            <div class="cbis-approval-flow">@foreach($steps as $index => $label)<div class="{{ $index < $step ? 'is-complete' : '' }}"><span>{{ $index < $step ? '✓' : $index + 1 }}</span><strong>{{ $label }}</strong></div>@endforeach</div>
            @if($nextEvent->approval_status === 'pending')<div class="alert alert-info mb-0 mt-3">This activity is pending review by Bacolod QAO. It remains hidden from the public map.</div>@elseif($nextEvent->approval_status === 'rejected')<div class="alert alert-danger mb-0 mt-3">QAO requested changes: {{ $nextEvent->review_notes ?: 'Review the activity details before resubmitting.' }}</div>@else<div class="alert alert-success mb-0 mt-3">{{ $hasPublicPin ? 'Approved and published with an activity location.' : 'Approved. Public map visibility also requires publication and a location.' }}</div>@endif
        @else<div class="cbis-empty-state"><strong>No upcoming activity</strong><span>Create an activity and send its venue and map location to QAO for approval.</span><a href="{{ route('donation-schedules.create') }}" class="btn btn-danger mt-2">Create Activity</a></div>@endif
        </div></section>
    </div>
    <div class="col-xl-5">
        <section class="card h-100"><div class="card-header cbis-card-title"><span>Proposed Venue Location</span></div><div class="card-body p-0">
            <x-ui.event-map :events="$nextEvent ? collect([$nextEvent]) : collect()" />
            @if($nextEvent)<div class="p-3"><a href="{{ route('donation-schedules.show', $nextEvent) }}" class="btn btn-outline-danger w-100">View Activity & Map</a></div>@endif
        </div></section>
    </div>
</div>
<div class="row g-4"><div class="col-xl-5"><section class="card h-100"><div class="card-header cbis-card-title"><span>{{ today()->format('F Y') }}</span><a href="{{ route('donation-schedules.index') }}">Schedule</a></div><div class="card-body">
    <div class="cbis-calendar" aria-label="Activities this month">
        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<span class="weekday">{{ $day }}</span>@endforeach
        @for($blank = 1; $blank < today()->startOfMonth()->dayOfWeekIso; $blank++)<span aria-hidden="true"></span>@endfor
        @for($day = 1; $day <= today()->daysInMonth; $day++)
            @php($dayEvents = $calendarEvents->filter(fn ($event) => $event->event_date?->day === $day))
            @if($dayEvents->isNotEmpty())<a class="has-event {{ today()->day === $day ? 'is-today' : '' }}" href="{{ route('donation-schedules.index', ['event_date' => today()->day($day)->toDateString()]) }}" aria-label="{{ today()->day($day)->format('F j') }}: {{ $dayEvents->count() }} activities">{{ $day }}</a>
            @else<span class="{{ today()->day === $day ? 'is-today' : '' }}">{{ $day }}</span>@endif
        @endfor
    </div><p class="small text-muted mt-3 mb-0">Highlighted dates have activities. Select a date to view the schedule.</p>
</div></section></div><div class="col-xl-7">
<section class="card h-100"><div class="card-header cbis-card-title"><span>Recent Decisions and Activities</span><a href="{{ route('notifications.index') }}">Notifications</a></div><div class="card-body p-0"><div class="cbis-list-stack cbis-list-flush">
@forelse($events as $event)<a class="cbis-list-row" href="{{ route('donation-schedules.show', $event) }}"><span class="cbis-list-icon"><x-ui.icon name="calendar" /></span><span><strong>{{ $event->title }}</strong><small>{{ $event->event_date?->format('M d, Y') }} · {{ $event->venue }}</small></span><span class="cbis-inline-status {{ $event->approval_status === 'approved' ? 'cbis-tone-success' : ($event->approval_status === 'rejected' ? 'cbis-tone-danger' : 'cbis-tone-warning') }}">{{ str($event->approval_status)->title() }}</span></a>@empty<div class="cbis-empty-state"><strong>No activities yet</strong><span>Your activity history and QAO decisions will appear here.</span></div>@endforelse
</div></div></section>
</div></div>
@endsection
