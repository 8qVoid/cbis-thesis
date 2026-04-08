@extends('layouts.app')

@section('content')
<div class="card card-body">
    <h4>Centralized Blood Inventory System</h4>
    <p class="mb-3">Use the public portal or sign in for role-based access.</p>
    <div class="d-flex gap-2">
        <a href="{{ route('public.index') }}" class="btn btn-outline-danger">Public Portal</a>
        <a href="{{ route('login') }}" class="btn btn-danger">Login</a>
    </div>
</div>
@endsection
