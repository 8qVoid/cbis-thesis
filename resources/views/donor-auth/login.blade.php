@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">Donor Login</div>
            <div class="card-body">
                <form method="POST" action="{{ route('donor.login.store') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="donorRemember"><label class="form-check-label" for="donorRemember">Remember me</label></div>
                    <button class="btn btn-danger w-100" type="submit">Login</button>
                </form>
                <hr>
                <a href="{{ route('donor.register') }}" class="btn btn-outline-secondary w-100">Create Donor Online Account</a>
            </div>
        </div>
    </div>
</div>
@endsection
