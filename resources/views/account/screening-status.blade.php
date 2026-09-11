@if($donor)
<section class="card card-body my-3" aria-label="Donation screening">
    <h2 class="h6">Donation screening</h2>
    <strong>{{ $donor->screening_label }}</strong>
    @if($screening = $donor->latestScreening)
        <small class="text-muted">Recorded {{ $screening->created_at->format('M d, Y') }}</small>
        @if($screening->donor_message)<p class="mb-1">{{ $screening->donor_message }}</p>@endif
        @if($screening->review_on)<small>Review date: {{ $screening->review_on->format('M d, Y') }}. This date does not automatically clear you to donate.</small>@endif
    @endif
    <details class="cbis-help mt-2"><summary>What does my screening status mean?</summary>
        <dl class="mt-2 mb-0">
            <dt>Awaiting screening</dt><dd>Blood Bank Staff have not recorded a screening decision yet.</dd>
            <dt>Eligible at this screening</dt><dd>Blood Bank Staff recorded eligibility for this assessment. Each donation still requires screening.</dd>
            <dt>Deferred</dt><dd>Donation cannot proceed based on this screening. Follow the staff message and any review date.</dd>
        </dl>
    </details>
    <small class="text-muted mt-2">Blood Bank Staff assess eligibility for each donation. A recorded decision is not permanent clearance.</small>
</section>
@endif
