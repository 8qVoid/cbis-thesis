@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <section class="card cbis-verification-card" aria-labelledby="verification-title">
            <div class="card-body">
                <span class="cbis-inline-status cbis-tone-warning mb-3">Awaiting email verification</span>
                <h1 id="verification-title" class="cbis-page-title">Verify your email</h1>
                <p class="mb-3">Your account is saved. Verify your email before using account services.</p>
                <div class="bg-light border rounded p-3 mb-3">
                    <span class="text-muted small d-block mb-1">Email address</span>
                    <strong class="text-break">{{ auth()->user()->email }}</strong>
                </div>

                @if(session('verification_warning'))
                    <div class="alert alert-warning small mb-3" role="alert">{{ session('verification_warning') }}</div>
                @endif

                <p class="small mb-2">Check your inbox, spam, or junk folder for the verification message. Open its secure link to activate your account.</p>
                <p class="text-muted small mb-0">Each link expires after {{ config('auth.verification.expire', 60) }} minutes. Your account stays available; request a new link if it expires or the email does not arrive.</p>
                <div class="cbis-form-actions">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger">Resend verification email</button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Use another account</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
