@extends('layouts.app')
@section('content')
<div class="card card-body"><p><strong>Blood Type:</strong> {{ $bloodInventory->blood_type }}</p><p><strong>Units:</strong> {{ $bloodInventory->units_available }}</p><p><strong>Status:</strong> {{ $bloodInventory->status }}</p></div>
@endsection
