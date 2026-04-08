@extends('layouts.app')
@section('content')
<div class="card card-body"><p><strong>Blood Type:</strong> {{ $bloodRelease->inventory->blood_type ?? '-' }}</p><p><strong>Units:</strong> {{ $bloodRelease->units_released }}</p><p><strong>Patient:</strong> {{ $bloodRelease->patient_name }}</p></div>
@endsection
