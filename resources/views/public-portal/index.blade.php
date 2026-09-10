@extends('layouts.app')
@section('content')
<section class="cbis-landing-hero">
    <div class="cbis-landing-copy">
        <p class="cbis-landing-eyebrow">Philippine Red Cross · Negros Occidental</p>
        <h1>Blood services made easier to find and access.</h1>
        <p class="cbis-landing-lead">Find approved donation activities, register as a donor, or request blood through a clear and guided process.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('donor.register', ['service' => 'donor']) }}" class="btn btn-danger">Donate blood</a>
            <a href="{{ route('donor.register', ['service' => 'patient']) }}" class="btn btn-outline-danger">Request blood</a>
            <a href="{{ route('public.map') }}" class="btn btn-outline-secondary">View events &amp; map</a>
        </div>
    </div>
    <div class="cbis-landing-map-card">
        <div class="cbis-landing-map-heading">
            <span>Approved donation activities</span>
            <a href="{{ route('public.map') }}">Explore full map <span aria-hidden="true">→</span></a>
        </div>
        <x-ui.event-map :events="$schedules" id="landing-event-map" />
    </div>
</section>

<section class="cbis-landing-services" aria-labelledby="services-heading">
    <div class="cbis-landing-section-heading">
        <p class="cbis-landing-eyebrow">Simple process</p>
        <h2 id="services-heading">How CBIS works</h2>
        <p>One account lets you use donor services, patient services, or both when needed.</p>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <article class="cbis-landing-service-card h-100">
                <span class="cbis-landing-step">1</span>
                <h3>Create one account</h3>
                <p>Choose Donor, Patient, or both during registration. Your account shares basic profile details while records stay separate.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="cbis-landing-service-card h-100">
                <span class="cbis-landing-step">2</span>
                <h3>Use the right service</h3>
                <p>Donors can view approved activities. Patients can submit a blood reservation with an ID and doctor’s blood request.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="cbis-landing-service-card h-100">
                <span class="cbis-landing-step">3</span>
                <h3>Follow verified updates</h3>
                <p>Track your request status or browse QAO-approved donation activities on the public map before you visit.</p>
            </article>
        </div>
    </div>
</section>

@if($schedules->isNotEmpty())
    <section class="cbis-landing-events" aria-labelledby="events-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <p class="cbis-landing-eyebrow">Verified activities</p>
                <h2 id="events-heading">Upcoming public events</h2>
            </div>
            <a href="{{ route('public.map') }}" class="btn btn-outline-danger">View all events</a>
        </div>
        <div class="row g-3">
            @foreach($schedules->take(3) as $schedule)
            <div class="col-md-4">
                <article class="cbis-landing-event-card h-100">
                    <span class="cbis-event-type">{{ $schedule->event_type_label }}</span>
                    <h3>{{ $schedule->title }}</h3>
                    <dl>
                        <div><dt>Date</dt><dd>{{ $schedule->event_date?->format('F j, Y') }}</dd></div>
                        <div><dt>Time</dt><dd>{{ $schedule->time_range_label }}</dd></div>
                        <div><dt>Venue</dt><dd>{{ $schedule->venue ?: ($schedule->facility?->name ?? 'To be announced') }}</dd></div>
                    </dl>
                    <a href="{{ route('donor.events.join', $schedule) }}" class="btn btn-outline-danger btn-sm">View activity</a>
                </article>
            </div>
            @endforeach
        </div>
    </section>
@endif
@endsection
