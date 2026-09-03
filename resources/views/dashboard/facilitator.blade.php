@extends('layouts.app')

@section('content')
<h1 class="cbis-page-title">Activity Dashboard</h1>
<p class="cbis-page-subtitle">Manage your facility's activities and follow QAO approval decisions.</p>
<a href="{{ route('donation-schedules.index') }}" class="btn btn-danger mb-3">Manage Events</a>
<div class="card">
    <div class="card-header">Recent Activity Schedules</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Activity</th><th>Date</th><th>Approval</th></tr></thead>
            <tbody>
                @forelse($events as $event)
                    <tr><td>{{ $event->title }}</td><td>{{ $event->event_date?->format('M d, Y') }}</td><td>{{ ucfirst($event->approval_status) }}</td></tr>
                @empty
                    <tr><td colspan="3">No activity schedules yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
