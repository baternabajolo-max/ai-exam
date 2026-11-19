@extends('layouts.app')
@section('hide-navbar', true)
@section('title', 'Sign in | LEMU')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
<main class="auth-page">
    <section class="auth-brand-panel" aria-label="LEMU student platform">
        <a href="{{ url('/') }}" class="auth-brand-link"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><span><strong>LEMU</strong><small>Student Learning Platform</small></span></a>
        <div class="auth-brand-copy">
            <span class="auth-kicker">Board exam readiness</span>
            <h1>Continue your learning journey.</h1>
            <p>Review module materials, take quizzes, and monitor the topics that need your attention.</p>
            <div class="auth-progress-card"><div class="auth-progress-head"><span>Student readiness</span><strong>78%</strong></div><div class="auth-progress"><span></span></div></div>
        </div>
        <small>&copy; {{ date('Y') }} LEMU</small>
    </section>

    <section class="auth-form-panel">
        <a href="{{ url('/') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to home</a>
        <div class="auth-card">
            <div class="auth-mobile-brand"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><strong>LEMU</strong></div>
            <p class="auth-kicker">Welcome back</p>
            <h2>Sign in to your account</h2>
            <p class="auth-subtitle">Sign in with the email and password for your LEMU account, or continue with Google.</p>

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="auth-field"><label for="email">Email address</label><div class="auth-input"><i class="bi bi-envelope"></i><input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus></div></div>
                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input"><i class="bi bi-lock"></i><input type="password" id="password" name="password" placeholder="Enter your password" required></div>
                    <div class="auth-forgot-password">
                        <a class="auth-help" href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                </div>
                <div class="auth-options">
                    <span><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Stay signed in on this device until logout.</span>
                    <a href="mailto:support@lemu.edu">Need help?</a>
                </div>
                <button type="submit" class="auth-submit">Sign in <i class="bi bi-arrow-right"></i></button>
                <p class="auth-switch">New to LEMU? <a href="{{ route('register') }}">Create an account</a></p>
            </form>
            <div class="auth-divider"><span>or</span></div>
            <a href="{{ route('google.redirect') }}" class="auth-google-button">
                <i class="bi bi-google" aria-hidden="true"></i> Continue with Google
            </a>
        </div>
        <small class="auth-footer">Student access · Academic Support · {{ date('Y') }}</small>
    </section>
</main>
@endsection
