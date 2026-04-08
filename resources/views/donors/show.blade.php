@extends('layouts.app')
@section('content')
<h4>Donor Details</h4>
<div class="card card-body"><p><strong>Name:</strong> {{ $donor->full_name }}</p><p><strong>Blood Type:</strong> {{ $donor->blood_type }}</p><p><strong>Contact:</strong> {{ $donor->contact_number }}</p><p><strong>Email:</strong> {{ $donor->email }}</p><p><strong>Address:</strong> {{ $donor->address }}</p></div>
@endsection
