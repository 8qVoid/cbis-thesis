@extends('layouts.app')
@section('content')
<div class="row justify-content-center"><div class="col-md-7 col-lg-6"><section class="card cbis-verification-card"><div class="card-body">
    <div class="cbis-eyebrow">One last step</div>
    <h1 class="cbis-page-title">Verify your email</h1>
    <p>We sent a secure verification link to <strong>{{ auth()->user()->email }}</strong>. Open that link to activate your account.</p>
    <p class="text-muted small">The link expires after 60 minutes. Check your spam or junk folder if you do not see the message.</p>
    <div class="cbis-form-actions">
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-danger">Resend verification email</button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary">Use another account</button></form>
    </div>
</div></section></div></div>
@endsection
