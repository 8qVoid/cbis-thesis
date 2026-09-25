@extends('layouts.app')
@section('content')
<div class="cbis-page-heading"><div><div class="cbis-eyebrow">Blood release</div><h1 class="cbis-page-title mb-0">{{ 'Release #'.$bloodRelease->id }}</h1></div><a href="{{ route('blood-releases.index') }}" class="btn btn-outline-secondary">Back to records</a></div>
<section class="card"><div class="card-body"><dl class="cbis-record-details mb-0">
<div><dt>Blood type</dt><dd>{{ $bloodRelease->inventory->blood_type ?? 'Not recorded' }}</dd></div>
<div><dt>Component</dt><dd>{{ $bloodRelease->inventory?->component_label ?? 'Not recorded' }}</dd></div>
<div><dt>Units released</dt><dd>{{ $bloodRelease->units_released }}</dd></div>
<div><dt>Patient</dt><dd>{{ $bloodRelease->patient_name ?: 'Not recorded' }}</dd></div>
<div><dt>Release date</dt><dd>{{ $bloodRelease->released_at?->format('M j, Y · g:i A') }}</dd></div>
</dl></div></section>
@endsection
