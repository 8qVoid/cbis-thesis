@extends('layouts.app')
@section('content')
@php
    $screeningStatus = $donor->latestScreening?->status ?? 'awaiting';
    $screeningTone = match ($screeningStatus) {
        'eligible' => 'cbis-tone-success',
        'deferred' => 'cbis-tone-danger',
        default => 'cbis-tone-warning',
    };
@endphp
<div class="cbis-page-heading">
    <div><div class="cbis-eyebrow">Donor profile</div><h1 class="cbis-page-title mb-0">{{ $donor->full_name }}</h1><p class="cbis-page-subtitle">Identity, contact information, and current screening status.</p></div>
    <div class="d-flex flex-wrap gap-2"><a href="{{ route('donors.index') }}" class="btn btn-outline-secondary">Back to donors</a>@can('manage donors')<a href="{{ route('donors.edit', $donor) }}" class="btn btn-danger">Edit donor</a>@endcan</div>
</div>
<div class="cbis-donor-staff-summary" data-live-region="donor-details">
    <section class="card"><small>Blood type</small><strong>{{ $donor->blood_type }}</strong></section>
    <section class="card"><small>Age</small><strong>{{ $donor->birth_date?->age ?? '—' }}</strong><span>{{ $donor->birth_date?->format('M j, Y') ?? 'Birth date not recorded' }}</span></section>
    <section class="card"><small>Eligibility</small><span class="cbis-inline-status {{ $screeningTone }}">{{ $donor->screening_label }}</span></section>
    <section class="card"><small>Home facility</small><strong class="cbis-summary-text">{{ $donor->facility?->name ?? 'Not assigned' }}</strong></section>
</div>
<section class="card mt-3">
    <div class="card-header cbis-card-title"><span>Contact information</span></div>
    <div class="card-body">@can('view detailed donors')<dl class="cbis-record-details mb-0"><div><dt>Mobile number</dt><dd>{{ $donor->contact_number ?: 'Not recorded' }}</dd></div><div><dt>Email</dt><dd>{{ $donor->email ?: $donor->user?->email ?: 'Not recorded' }}</dd></div><div class="cbis-detail-span"><dt>Address</dt><dd>{{ $donor->address ?: 'Not recorded' }}</dd></div></dl>@else<p class="text-muted mb-0">Contact details and private records are restricted to Blood Bank Staff.</p>@endcan</div>
</section>
@can('manage donors')
@php($donorMeetsMinimumAge = \App\Support\DonationAgePolicy::isOldEnough($donor->birth_date))
<section class="card mt-3 cbis-screening-panel">
    <div class="card-body">
    <div class="cbis-screening-panel-header" data-live-region="donor-screening-header">
        <div>
            <small>Blood Bank Staff decision</small>
            <h2 class="h5">Record screening decision</h2>
            <p>Choose the result after checking the donor’s age, health condition, and screening requirements.</p>
        </div>
        <span class="cbis-inline-status {{ $screeningTone }}">{{ $donor->screening_label }}</span>
    </div>
    @include('account.screening-status')
    @unless($donorMeetsMinimumAge)
        <div class="alert alert-warning">This donor profile can stay active, but eligibility cannot be approved until the donor is at least {{ \App\Support\DonationAgePolicy::MINIMUM_AGE }}.</div>
    @endunless
    <form method="POST" action="{{ route('donors.screening', $donor) }}" class="cbis-compact-form">
        @csrf @method('PATCH')
        <div class="row g-3"><div class="col-lg-5"><label for="screeningStatus" class="form-label">Decision after screening</label>
        <select id="screeningStatus" name="status" class="form-select">
            @foreach(['awaiting' => 'Awaiting screening', 'eligible' => 'Eligible at this screening', 'deferred' => 'Deferred'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $donor->latestScreening?->status ?? 'awaiting') === $value) @disabled($value === 'eligible' && ! $donorMeetsMinimumAge)>{{ $label }}{{ $value === 'eligible' && ! $donorMeetsMinimumAge ? ' — donor must be 18+' : '' }}</option>
            @endforeach
        </select></div><div class="col-lg-7"><label for="screeningReview" class="form-label">Review date (optional)</label><input id="screeningReview" type="date" name="review_on" min="{{ today()->toDateString() }}" value="{{ old('review_on') }}" class="form-control"></div>
        <div class="col-12"><label for="screeningMessage" class="form-label">Message to donor (required when deferred)</label><textarea id="screeningMessage" name="donor_message" class="form-control" rows="3" maxlength="1000">{{ old('donor_message') }}</textarea></div></div>
        <div class="cbis-form-actions justify-content-between align-items-center"><label class="small mb-0"><input type="checkbox" name="screening_confirmed" value="1" required> I am recording the authorized decision. The donor will see this message.</label><button class="btn btn-danger">Save screening decision</button></div>
    </form>
    </div>
</section>
@endcan
@endsection
