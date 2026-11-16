@extends('layouts.app')
@section('hide-navbar', true)
@section('title', 'Forgot password | LEMU')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
<main class="auth-page">
    <section class="auth-brand-panel" aria-label="LEMU account recovery">
        <a href="{{ route('landing') }}" class="auth-brand-link"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><span><strong>LEMU</strong><small>Student Learning Platform</small></span></a>
        <div class="auth-brand-copy">
            <span class="auth-kicker">Account security</span>
            <h1>Recover your faculty or admin account.</h1>
            <p>We will email a secure password link to the address connected to a faculty or admin account.</p>
        </div>
        <small>&copy; {{ date('Y') }} LEMU</small>
    </section>

    <section class="auth-form-panel">
        <a href="{{ route('login') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to sign in</a>
        <div class="auth-card">
            <div class="auth-mobile-brand"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><strong>LEMU</strong></div>
            <p class="auth-kicker">Password recovery</p>
            <h2>Forgot your password?</h2>
            <p class="auth-subtitle">Enter your faculty or admin email to receive a secure reset link.</p>

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="auth-field">
                    <label for="email">Faculty or admin email address</label>
                    <div class="auth-input"><i class="bi bi-envelope"></i><input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" required autofocus></div>
                </div>
                <button type="submit" class="auth-submit">Email recovery link <i class="bi bi-arrow-right"></i></button>
            </form>
            <p class="auth-switch">Student account? <a href="{{ route('google.redirect') }}">Continue with Google</a></p>
        </div>
        <small class="auth-footer">Secure faculty and admin account recovery · {{ date('Y') }}</small>
    </section>
</main>
@endsection
