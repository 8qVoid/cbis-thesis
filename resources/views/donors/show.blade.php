@extends('layouts.app')
@section('content')
<h4>Donor Details</h4>
<div class="card card-body"><p><strong>Name:</strong> {{ $donor->full_name }}</p><p><strong>Blood Type:</strong> {{ $donor->blood_type }}</p><p><strong>Eligibility:</strong> {{ $donor->screening_label }}</p>@can('view detailed donors')<p><strong>Contact:</strong> {{ $donor->contact_number }}</p><p><strong>Email:</strong> {{ $donor->email ?: $donor->user?->email }}</p><p><strong>Address:</strong> {{ $donor->address }}</p>@else<p class="text-muted mb-0">Contact details and private records are restricted to Blood Bank Staff.</p>@endcan</div>
@can('manage donors')
<section class="card card-body mt-3">
    <h2 class="h5">Record screening decision</h2>
    @include('account.screening-status')
    <form method="POST" action="{{ route('donors.screening', $donor) }}">
        @csrf @method('PATCH')
        <label for="screeningStatus" class="form-label">Decision after screening</label>
        <select id="screeningStatus" name="status" class="form-select mb-3">
            @foreach(['awaiting' => 'Awaiting screening', 'eligible' => 'Eligible at this screening', 'deferred' => 'Deferred'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $donor->latestScreening?->status ?? 'awaiting') === $value)>{{ $label }}</option>
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
