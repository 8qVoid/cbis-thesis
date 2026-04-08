@extends('layouts.app')
@section('content')
<div class="card card-body"><p><strong>Facility:</strong> {{ $bloodBankLocation->facility->name ?? '-' }}</p><p><strong>Address:</strong> {{ $bloodBankLocation->address }}</p><p><strong>Coordinates:</strong> {{ $bloodBankLocation->latitude }}, {{ $bloodBankLocation->longitude }}</p></div>
@endsection
