@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-3">
            <h1 class="cbis-page-title mb-0">Confirm Event Registration</h1>
            <p class="cbis-page-subtitle">Registering means you plan to attend. The actual blood donation is still confirmed by facility staff on site.</p>
        </div>

        <div class="card cbis-card">
            <div class="card-body">
                <h2 class="h4 mb-3">{{ $donationSchedule->title }}</h2>
                <dl class="row mb-4">
                    <dt class="col-sm-4">Facility</dt>
                    <dd class="col-sm-8">{{ $donationSchedule->facility?->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Date</dt>
                    <dd class="col-sm-8">{{ $donationSchedule->event_date?->toFormattedDateString() ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Time</dt>
                    <dd class="col-sm-8">{{ $donationSchedule->time_range_label }}</dd>

                    <dt class="col-sm-4">Venue</dt>
                    <dd class="col-sm-8">{{ $donationSchedule->venue }}</dd>
                </dl>

                <form method="POST" action="{{ route('donor.events.register', $donationSchedule) }}">
                    @csrf
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-danger" type="submit">Yes, Register Me</button>
                        <a href="{{ route('public.map') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
