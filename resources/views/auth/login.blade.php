@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">Unified Login</div>
            <div class="card-body">
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email or Philippine Mobile Number</label>
                        <input
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            class="form-control"
                            placeholder="name@example.com or +63 917 123 4567"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button class="btn btn-danger w-100" type="submit">Sign in</button>
                </form>
                <hr>
                <a href="{{ route('donor.register') }}" class="btn btn-outline-secondary w-100">Donor Online Registration</a>
            </div>
        </div>
    </div>
</div>
@endsection
