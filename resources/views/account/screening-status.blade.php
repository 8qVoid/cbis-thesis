@if($donor)
@php
    $screeningStatus = $donor->latestScreening?->status ?? 'awaiting';
    $screeningTone = match ($screeningStatus) {
        'eligible' => 'cbis-tone-success',
        'deferred' => 'cbis-tone-danger',
        default => 'cbis-tone-warning',
    };
@endphp
<section class="card card-body my-3 cbis-screening-card" aria-label="Donation screening">
    <div class="cbis-screening-card-header">
        <div>
            <small>Donation screening</small>
            <h2>Current eligibility</h2>
        </div>
        <span class="cbis-inline-status {{ $screeningTone }}">{{ $donor->screening_label }}</span>
    </div>
    @unless(\App\Support\DonationAgePolicy::isOldEnough($donor->birth_date))
        <div class="alert alert-warning mt-3 mb-2">Your donor profile is saved. Donation eligibility starts at age {{ \App\Support\DonationAgePolicy::MINIMUM_AGE }} after Blood Bank Staff screening.</div>
    @endunless
    @if($screening = $donor->latestScreening)
        <small class="text-muted">Recorded {{ $screening->created_at->format('M d, Y') }}</small>
        @if($screening->donor_message)<p class="mb-1">{{ $screening->donor_message }}</p>@endif
        @if($screening->review_on)<small>Review date: {{ $screening->review_on->format('M d, Y') }}. This date does not automatically clear you to donate.</small>@endif
    @endif
    <details class="cbis-help mt-2"><summary>What does my screening status mean?</summary>
        <dl class="mt-2 mb-0">
            <dt>Awaiting screening</dt><dd>Blood Bank Staff still need to screen this donor before any donation can be accepted.</dd>
            <dt>Eligible at this screening</dt><dd>Blood Bank Staff recorded eligibility for this assessment. Each donation still requires screening.</dd>
            <dt>Deferred</dt><dd>Donation cannot proceed based on this screening. Follow the staff message and any review date.</dd>
        </dl>
    </details>
    <small class="text-muted mt-2">Blood Bank Staff assess eligibility before donation. A recorded decision is not permanent clearance.</small>
</section>
@endif
