@if($donor)
<section class="card card-body my-3" aria-label="Donation screening">
    <h2 class="h6">Donation screening</h2>
    <strong>{{ $donor->screening_label }}</strong>
    @if($screening = $donor->latestScreening)
        <small class="text-muted">Recorded {{ $screening->created_at->format('M d, Y') }}</small>
        @if($screening->donor_message)<p class="mb-1">{{ $screening->donor_message }}</p>@endif
        @if($screening->review_on)<small>Review date: {{ $screening->review_on->format('M d, Y') }}. This date does not automatically clear you to donate.</small>@endif
    @endif
    <small class="text-muted mt-2">Staff must assess eligibility for each donation. This is a recorded screening decision, not permanent clearance.</small>
</section>
@endif
