@extends('layouts.app')
@section('content')
<h4>Donor Details</h4>
<div class="card card-body"><p><strong>Name:</strong> {{ $donor->full_name }}</p><p><strong>Blood Type:</strong> {{ $donor->blood_type }}</p><p><strong>Eligibility:</strong> {{ $donor->is_eligible ? 'Eligible' : 'Not confirmed' }}</p>@can('view detailed donors')<p><strong>Contact:</strong> {{ $donor->contact_number }}</p><p><strong>Email:</strong> {{ $donor->email ?: $donor->user?->email }}</p><p><strong>Address:</strong> {{ $donor->address }}</p>@else<p class="text-muted mb-0">Contact details and private records are restricted to Blood Bank Staff.</p>@endcan</div>
@endsection
