@extends('layouts.app')
@section('hide-navbar', true)
@section('title', 'Create account | LEMU')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
<main class="auth-page">
    <section class="auth-brand-panel" aria-label="LEMU student registration">
        <a href="{{ url('/') }}" class="auth-brand-link"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><span><strong>LEMU</strong><small>Student Learning Platform</small></span></a>
        <div class="auth-brand-copy">
            <span class="auth-kicker">Start preparing</span>
            <h1>Your review path begins here.</h1>
            <p>Create a student account to access reviewers, quizzes, readiness analytics, and personalized weak-topic practice.</p>
            <div class="auth-feature-row"><span><i class="bi bi-journal-text"></i> Reviewers</span><span><i class="bi bi-ui-checks"></i> Quizzes</span><span><i class="bi bi-speedometer2"></i> Readiness</span></div>
        </div>
        <small>&copy; {{ date('Y') }} LEMU</small>
    </section>

    <section class="auth-form-panel">
        <a href="{{ url('/') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to home</a>
        <div class="auth-card">
            <div class="auth-mobile-brand"><img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU logo"><strong>LEMU</strong></div>
            <p class="auth-kicker">Student registration</p>
            <h2>Create your account</h2>
            <p class="auth-subtitle">Enter your details below, or create an account with Google.</p>
            @if(!empty($googleRegistration))
                <div class="auth-alert success" role="status">
                    <strong>Verified Google account</strong><br>
                    {{ $googleRegistration['email'] }}
                </div>
            @endif

            <form action="{{ route('register.submit') }}" method="POST">
                @csrf
                <div class="auth-field">
                    <label for="name">Full name</label>
                    <div class="auth-input"><i class="bi bi-person"></i><input type="text" id="name" name="name" value="{{ old('name', !empty($googleRegistration) ? trim($googleRegistration['first_name'].' '.($googleRegistration['last_name'] ?? '')) : '') }}" placeholder="Enter your full name" autocomplete="name" required></div>
                </div>
                <div class="auth-field">
                    <label for="email">Email address</label>
                    <div class="auth-input"><i class="bi bi-envelope"></i><input type="email" id="email" name="email" value="{{ old('email', $googleRegistration['email'] ?? '') }}" placeholder="name@example.com" autocomplete="email" {{ !empty($googleRegistration) ? 'readonly' : '' }} required></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="auth-field">
                            <label for="password">Password</label>
                            <div class="auth-input"><i class="bi bi-lock"></i><input type="password" id="password" name="password" placeholder="8+ characters, uppercase & symbol" autocomplete="new-password" minlength="8" required></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-field">
                            <label for="password_confirmation">Confirm password</label>
                            <div class="auth-input"><i class="bi bi-lock"></i><input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" minlength="8" required></div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="auth-field">
                            <label for="department_id">College</label>
                            <div class="auth-input"><i class="bi bi-building"></i><select id="department_id" name="department_id" data-old-dept="{{ old('department_id') }}" data-old-course="{{ old('course_id') }}" required><option value="">Select college</option>@foreach($departments as $department)<option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>@endforeach</select></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-field">
                            <label for="course_id">Course / Program</label>
                            <div class="auth-input"><i class="bi bi-mortarboard"></i><select id="course_id" name="course_id" required disabled><option value="">Select college first</option></select></div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="auth-submit">Create account <i class="bi bi-arrow-right"></i></button>
            </form>

            <p class="auth-switch">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
            <div class="auth-divider"><span>or</span></div>
            <a href="{{ route('google.redirect') }}" class="auth-google-button">
                <i class="bi bi-google" aria-hidden="true"></i> Continue with Google
            </a>
        </div>
        <small class="auth-footer">Student access · Academic Support · {{ date('Y') }}</small>
    </section>
</main>
<script src="{{ asset('js/auth/register.js') }}"></script>
@endsection
