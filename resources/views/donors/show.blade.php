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
<h4>Donor Details</h4>
<div class="card card-body"><p><strong>Name:</strong> {{ $donor->full_name }}</p><p><strong>Blood Type:</strong> {{ $donor->blood_type }}</p><p><strong>Age:</strong> {{ $donor->birth_date?->age ?? 'Not recorded' }}</p><p><strong>Eligibility:</strong> <span class="cbis-inline-status {{ $screeningTone }}">{{ $donor->screening_label }}</span></p>@can('view detailed donors')<p><strong>Contact:</strong> {{ $donor->contact_number }}</p><p><strong>Email:</strong> {{ $donor->email ?: $donor->user?->email }}</p><p><strong>Address:</strong> {{ $donor->address }}</p>@else<p class="text-muted mb-0">Contact details and private records are restricted to Blood Bank Staff.</p>@endcan</div>
@can('manage donors')
@php($donorMeetsMinimumAge = \App\Support\DonationAgePolicy::isOldEnough($donor->birth_date))
<section class="card card-body mt-3 cbis-screening-panel">
    <div class="cbis-screening-panel-header">
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
    <form method="POST" action="{{ route('donors.screening', $donor) }}">
        @csrf @method('PATCH')
        <label for="screeningStatus" class="form-label">Decision after screening</label>
        <select id="screeningStatus" name="status" class="form-select mb-3">
            @foreach(['awaiting' => 'Awaiting screening', 'eligible' => 'Eligible at this screening', 'deferred' => 'Deferred'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $donor->latestScreening?->status ?? 'awaiting') === $value) @disabled($value === 'eligible' && ! $donorMeetsMinimumAge)>{{ $label }}{{ $value === 'eligible' && ! $donorMeetsMinimumAge ? ' — donor must be 18+' : '' }}</option>
            @endforeach
        </select>
        <label for="screeningMessage" class="form-label">Message to donor (required when deferred)</label>
        <textarea id="screeningMessage" name="donor_message" class="form-control mb-3" maxlength="1000">{{ old('donor_message') }}</textarea>
        <label for="screeningReview" class="form-label">Review date (optional)</label>
        <input id="screeningReview" type="date" name="review_on" min="{{ today()->toDateString() }}" value="{{ old('review_on') }}" class="form-control mb-3">
        <label class="d-block mb-3"><input type="checkbox" name="screening_confirmed" value="1" required> I am recording the authorized screening decision. The message will be visible to the donor.</label>
        <button class="btn btn-danger">Save Screening Decision</button>
    </form>
</section>
@endcan
@endsection
