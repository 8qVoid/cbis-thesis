@extends('layouts.app')
@section('content')
<h4>{{ $facility->name }}</h4>
<div class="card card-body"><p><strong>Code:</strong> {{ $facility->code }}</p><p><strong>Type:</strong> {{ $facility->type }}</p><p><strong>Contact:</strong> {{ $facility->contact_person }} / {{ $facility->contact_number }}</p><p><strong>Email:</strong> {{ $facility->email }}</p><p><strong>Address:</strong> {{ $facility->address }}</p></div>
@endsection
