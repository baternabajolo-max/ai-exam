@extends('layouts.app')
@section('hide-navbar', true)
@section('title', ($setupMode ? 'Set up your password' : 'Set a new password').' | LEMU')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
<main class="auth-page">
    <section class="auth-brand-panel" aria-label="LEMU {{ $setupMode ? 'faculty password setup' : 'password reset' }}">
        <a href="{{ route('landing') }}" class="auth-brand-link"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><span><strong>LEMU</strong><small>Student Learning Platform</small></span></a>
        <div class="auth-brand-copy">
            <span class="auth-kicker">Account security</span>
            <h1>{{ $setupMode ? 'Set up your password.' : 'Choose a new password.' }}</h1>
            <p>Use a strong password that is unique to your LEMU staff account.</p>
        </div>
        <small>&copy; {{ date('Y') }} LEMU</small>
    </section>

    <section class="auth-form-panel">
        <a href="{{ route('login') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to sign in</a>
        <div class="auth-card">
            <div class="auth-mobile-brand"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><strong>LEMU</strong></div>
            <p class="auth-kicker">{{ $setupMode ? 'Faculty account setup' : 'Password recovery' }}</p>
            <h2>{{ $setupMode ? 'Create your password' : 'Set a new password' }}</h2>
            <p class="auth-subtitle">{{ $setupMode ? 'Choose a password to finish setting up your faculty account.' : 'Enter and confirm your new faculty or admin password.' }} Use at least 8 characters, including an uppercase letter and a symbol.</p>

            <form action="{{ $setupMode ? route('password.setup.store') : route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="auth-field">
                    <label for="password">{{ $setupMode ? 'Password' : 'New password' }}</label>
                    <div class="auth-input"><i class="bi bi-lock"></i><input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required></div>
                </div>
                <div class="auth-field">
                    <label for="password_confirmation">{{ $setupMode ? 'Confirm password' : 'Confirm new password' }}</label>
                    <div class="auth-input"><i class="bi bi-shield-lock"></i><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required></div>
                </div>
                <button type="submit" class="auth-submit">{{ $setupMode ? 'Set password' : 'Save new password' }} <i class="bi bi-arrow-right"></i></button>
            </form>
            <p class="auth-switch">Student account? <a href="{{ route('google.redirect') }}">Continue with Google</a></p>
        </div>
        <small class="auth-footer">{{ $setupMode ? 'Secure faculty account setup' : 'Secure faculty and admin recovery' }} · {{ date('Y') }}</small>
    </section>
</main>
@endsection
