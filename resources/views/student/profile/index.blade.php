@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/student/student.css') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* -------------------------------------------------------------
        DESIGN SYSTEM TOKENS & TYPOGRAPHY OVERRIDES
    ------------------------------------------------------------- */
    :root {
        --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        --card-radius: 1.5rem;
        --transition-smooth: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        --transition-bounce: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    body {
        font-family: var(--font-main) !important;
        background-color: #f8fafc;
    }

    html[data-bs-theme="dark"] body {
        background-color: #0f172a;
    }

    .tracking-tight { letter-spacing: -0.025em; }
    .tracking-wide { letter-spacing: 0.05em; }

    /* -------------------------------------------------------------
        1. HERO BANNER: 3D GLASSMORPHISM & NEON GRADIENT
    ------------------------------------------------------------- */
    .hero-card-wrapper {
        border-radius: var(--card-radius);
        perspective: 1200px;
        transition: var(--transition-smooth);
    }

    .hero-banner-3d {
        align-items: flex-end;
        display: flex;
        min-height: 320px;
        position: relative;
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 35%, #312e81 70%, #4338ca 100%);
        border-radius: var(--card-radius);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 24px 48px -12px rgba(30, 27, 75, 0.35), 
                    inset 0 1px 0 rgba(255, 255, 255, 0.25);
        transition: var(--transition-smooth);
    }

    .profile-hero-content {
        width: 100%;
    }

    @media (max-width: 767.98px) {
        .hero-banner-3d {
            min-height: 360px;
        }
    }

    .hero-banner-3d::before {
        content: '';
        position: absolute;
        top: -60%;
        left: -30%;
        width: 160%;
        height: 220%;
        background: 
            radial-gradient(circle at 25% 25%, rgba(236, 72, 153, 0.28) 0%, transparent 45%),
            radial-gradient(circle at 75% 75%, rgba(59, 130, 246, 0.32) 0%, transparent 50%),
            radial-gradient(circle at 50% 10%, rgba(99, 102, 241, 0.2) 0%, transparent 40%);
        pointer-events: none;
        animation: ambientGlow 12s ease-in-out infinite alternate;
    }

    @keyframes ambientGlow {
        0% { transform: rotate(0deg) scale(1); }
        100% { transform: rotate(6deg) scale(1.05); }
    }

    .hero-glass-pill {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 9999px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        transition: var(--transition-smooth);
    }

    .hero-glass-pill:hover {
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 255, 255, 0.35);
        transform: translateY(-2px);
    }

    /* Edit Toggle Button */
    .profile-edit-toggle {
        align-items: center;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 50%;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.28), 0 0 0 4px rgba(255, 255, 255, 0.15);
        color: #4338ca;
        display: inline-flex;
        height: 46px;
        justify-content: center;
        position: absolute;
        right: 1.25rem;
        top: 1.25rem;
        transition: var(--transition-bounce);
        width: 46px;
        z-index: 10;
        cursor: pointer;
    }

    .profile-edit-toggle:hover,
    .profile-edit-toggle[aria-expanded="true"] {
        background: #ffffff;
        color: #312e81;
        transform: scale(1.12) rotate(12deg);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.35), 0 0 0 6px rgba(99, 102, 241, 0.25);
    }

    /* Camera Triggers & Overlays */
    .profile-camera-trigger {
        align-items: center;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        border: 2px solid rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        color: #ffffff;
        display: inline-flex;
        height: 44px;
        justify-content: center;
        left: 50%;
        opacity: 0;
        pointer-events: none;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%) scale(0.75);
        transition: var(--transition-bounce);
        visibility: hidden;
        width: 44px;
        z-index: 8;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        cursor: pointer;
    }

    .profile-camera-trigger:hover {
        background: #4338ca;
        color: #ffffff;
        transform: translate(-50%, -50%) scale(1.12);
        box-shadow: 0 10px 25px rgba(67, 56, 201, 0.5);
    }

    .profile-edit-mode .profile-camera-trigger {
        opacity: 1;
        pointer-events: auto;
        transform: translate(-50%, -50%) scale(1);
        visibility: visible;
    }

    .profile-edit-mode .avatar-ring-3d > img,
    .profile-edit-mode .avatar-ring-3d > span {
        filter: brightness(0.6) blur(1px);
        transition: var(--transition-smooth);
    }

    .profile-camera-form {
        display: none;
    }

    .profile-background-camera {
        left: auto;
        right: 1.5rem;
        top: 50%;
        transform: translateY(-50%) scale(0.75);
    }

    .profile-background-camera:hover,
    .profile-edit-mode .profile-background-camera {
        transform: translateY(-50%) scale(1);
    }

    /* Avatar 3D Ring */
    .avatar-ring-3d {
        position: relative;
        width: 112px;
        height: 112px;
        border-radius: 50%;
        padding: 4px;
        background: linear-gradient(135deg, #f43f5e 0%, #8b5cf6 50%, #06b6d4 100%);
        box-shadow: 0 12px 30px -6px rgba(139, 92, 246, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.2);
        transition: var(--transition-bounce);
    }

    .avatar-ring-3d:hover {
        transform: scale(1.04) rotate(-3deg);
        box-shadow: 0 16px 36px -4px rgba(139, 92, 246, 0.7);
    }

    .avatar-ring-3d img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #1e1b4b;
    }

    /* -------------------------------------------------------------
        2. GAMIFIED ACHIEVEMENT BADGE CARDS
    ------------------------------------------------------------- */
    .badge-section-card {
        background: #ffffff;
        border-radius: var(--card-radius);
        border: 1px solid #e2e8f0;
        transition: var(--transition-smooth);
    }

    html[data-bs-theme="dark"] .badge-section-card {
        background: #1e293b;
        border-color: #334155;
    }

    .badge-card-3d {
        position: relative;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        padding: 1.5rem 1rem;
        transition: var(--transition-bounce);
        transform-style: preserve-3d;
        perspective: 1000px;
    }

    html[data-bs-theme="dark"] .badge-card-3d {
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        border-color: #334155;
    }

    .badge-card-3d:hover {
        transform: translateY(-8px) rotateX(6deg) rotateY(-3deg);
        box-shadow: 0 20px 36px -12px rgba(99, 102, 241, 0.25);
        border-color: #6366f1;
    }

    .badge-icon-glow {
        width: 68px;
        height: 68px;
        border-radius: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        box-shadow: 0 10px 20px -4px rgba(99, 102, 241, 0.3);
        transition: var(--transition-bounce);
        color: #4f46e5;
    }

    .badge-card-3d:hover .badge-icon-glow {
        transform: scale(1.12) translateZ(12px);
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff !important;
        box-shadow: 0 14px 28px -4px rgba(79, 70, 229, 0.45);
    }

    .extra-small {
        font-size: 0.75rem;
    }

    /* Faculty-profile layout parity */
    .student-dashboard {
        background: transparent;
    }

    .hero-card-wrapper,
    .badge-section-card,
    .student-account-card {
        border: 1px solid rgba(234, 179, 8, 0.25) !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 15px 35px -10px rgba(74, 14, 23, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03) !important;
    }

    .hero-banner-3d {
        align-items: center;
        min-height: 200px;
        border: 0;
        border-radius: 1.5rem 1.5rem 0 0;
        background: linear-gradient(135deg, #2c070e 0%, #4a0e17 60%, #0f172a 100%);
        box-shadow: none;
    }

    .student-profile-summary {
        background: #fff;
        min-height: 116px;
        padding: 2rem 2rem 1.75rem 9.5rem;
        position: relative;
    }

    .student-profile-summary .avatar-ring-3d {
        position: absolute;
        left: 2rem;
        bottom: 1.25rem;
        width: 110px;
        height: 110px;
    }

    .student-profile-summary .profile-camera-trigger {
        left: calc(2rem + 82px);
        top: auto;
        bottom: 1.25rem;
    }

    .student-profile-summary .profile-edit-toggle {
        right: 1.5rem;
        top: 1.25rem;
    }

    html[data-bs-theme="dark"] .student-profile-summary {
        background: #1e293b;
    }

    .hero-card-wrapper.profile-edit-mode .profile-camera-trigger {
        opacity: 1;
        pointer-events: auto;
        transform: translate(-50%, -50%) scale(1);
        visibility: visible;
    }

    .student-profile-summary > .profile-camera-trigger {
        bottom: 1.25rem;
        left: calc(2rem + 82px);
        top: auto;
    }

    @media (max-width: 575.98px) {
        .student-profile-summary > .profile-camera-trigger {
            bottom: auto;
            left: calc(50% + 38px);
            top: -18px;
        }
    }

    @media (max-width: 575.98px) {
        .student-profile-summary {
            padding: 5.25rem 1.25rem 1.25rem;
            text-align: center;
        }

        .student-profile-summary .avatar-ring-3d {
            left: 50%;
            bottom: auto;
            top: -55px;
            transform: translateX(-50%);
        }

        .student-profile-summary .profile-camera-trigger {
            left: calc(50% + 38px);
            top: -18px;
            bottom: auto;
        }
    }
</style>
@endpush

@section('content')
<main class="container student-dashboard py-4">

    <!-- 1. HERO BANNER HEADER -->
    <section class="card border-0 mb-4 shadow-sm hero-card-wrapper">
        <div
            id="student-profile-hero"
            class="hero-banner-3d"
            @if($student->background_picture_path)
                style="background-image: linear-gradient(135deg, rgba(15, 23, 42, .88), rgba(30, 27, 75, .75), rgba(67, 56, 201, .65)), url('{{ asset('storage/'.$student->background_picture_path) }}'); background-position: center; background-size: cover;"
            @endif
        >
            <!-- Background Camera Trigger -->
            <button type="button" class="profile-camera-trigger profile-background-camera" data-file-input="student-background-picture" aria-label="Change background picture" title="Change background picture">
                <i class="bi bi-camera-fill" aria-hidden="true"></i>
            </button>
        </div>

        <div class="student-profile-summary">
            <div class="avatar-ring-3d">
                @if($student->profile_picture_path)
                    <img src="{{ asset('storage/'.$student->profile_picture_path) }}" alt="{{ $student->name }}">
                @else
                    <span class="d-flex h-100 w-100 align-items-center justify-content-center rounded-circle bg-white text-primary fw-bold fs-1 shadow-sm" aria-label="{{ $student->name }} profile picture">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </span>
                @endif
            </div>
            <button type="button" class="profile-camera-trigger" data-file-input="student-profile-picture" aria-label="Change profile picture" title="Change profile picture">
                <i class="bi bi-camera-fill" aria-hidden="true"></i>
            </button>

            <button type="button" id="student-profile-edit-toggle" class="profile-edit-toggle" aria-label="Edit profile pictures" aria-expanded="false">
                <i class="bi bi-pencil-fill fs-6" aria-hidden="true"></i>
            </button>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3">
                <div>
                    <h1 class="h4 fw-extrabold text-dark mb-1 tracking-tight">{{ $student->name }}</h1>
                    <p class="text-secondary small mb-2">{{ $student->email }} · Immutable</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill px-3 py-2 bg-danger-subtle text-danger border border-danger-subtle">Student</span>
                        @if($student->department)
                            <span class="badge rounded-pill px-3 py-2 bg-warning-subtle text-dark border border-warning-subtle">{{ $student->department->name }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-semibold align-self-sm-start">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Hidden Upload Forms -->
    <form id="student-profile-picture-form" class="profile-camera-form" action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input id="student-profile-picture" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp">
    </form>
    <form id="student-background-picture-form" class="profile-camera-form" action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input id="student-background-picture" type="file" name="background_picture" accept="image/jpeg,image/png,image/webp">
    </form>

    <section class="card student-account-card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1">Student account settings</h2>
            <p class="text-muted small mb-0">Update your name. Surname is optional, and your verified Google email cannot be changed here.</p>
        </div>
        <form action="{{ route('student.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="student-first-name">First name</label>
                    <input class="form-control" id="student-first-name" name="first_name" value="{{ old('first_name', $student->first_name) }}" autocomplete="given-name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="student-last-name">Surname (optional)</label>
                    <input class="form-control" id="student-last-name" name="last_name" value="{{ old('last_name', $student->last_name) }}" autocomplete="family-name">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="student-email">Verified Google email</label>
                    <input class="form-control" id="student-email" type="email" value="{{ $student->email }}" readonly>
                </div>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill px-4 mt-3">Save name</button>
        </form>
    </section>

    <!-- 2. ACHIEVEMENTS SHOWCASE GRID -->
    <section class="card badge-section-card border-0 shadow-sm rounded-4 p-4 p-md-4.5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2 tracking-tight">
                    <i class="bi bi-trophy-fill text-warning fs-4"></i>
                    <span>Unlocked Achievements</span>
                </h2>
                <span class="text-muted small fw-medium">{{ $student->achievements->count() }} milestones completed</span>
            </div>
            <a href="{{ route('student.achievements') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs">
                View All Badges <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-3">
            @forelse($student->achievements->take(5) as $achievement)
                <div class="col-6 col-md-4 col-lg">
                    <div class="badge-card-3d text-center h-100 d-flex flex-column align-items-center justify-content-center">
                        <div class="badge-icon-glow mb-3">
                            <img src="{{ asset('images/badges/' . $achievement->icon) }}" alt="{{ $achievement->title }}" class="img-fluid" style="max-height: 40px;" onerror="this.remove();">
                            <i class="bi bi-award-fill fs-2"></i>
                        </div>
                        <strong class="d-block text-dark small fw-bold mb-1 tracking-tight">{{ $achievement->title }}</strong>
                        <span class="text-muted extra-small d-block text-truncate w-100 px-2 fw-medium">{{ $achievement->description ?? 'Unlocked' }}</span>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 rounded-4 bg-body-tertiary border border-dashed">
                    <i class="bi bi-award fs-1 text-muted d-block mb-2"></i>
                    <p class="text-muted small mb-0 fw-medium">No badges unlocked yet. Take practice exams to earn milestones!</p>
                </div>
            @endforelse
        </div>
    </section>

</main>
@endsection

@push('scripts')
<script src="{{ asset('js/student/student.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hero = document.getElementById('student-profile-hero');
        const editToggle = document.getElementById('student-profile-edit-toggle');
        const profileCard = editToggle.closest('.hero-card-wrapper');

        editToggle.addEventListener('click', function () {
            const editMode = hero.classList.toggle('profile-edit-mode');
            profileCard.classList.toggle('profile-edit-mode', editMode);
            editToggle.setAttribute('aria-expanded', String(editMode));
        });

        document.querySelectorAll('.profile-camera-trigger').forEach(function (cameraButton) {
            cameraButton.addEventListener('click', function () {
                document.getElementById(cameraButton.dataset.fileInput).click();
            });
        });

        ['student-profile-picture', 'student-background-picture'].forEach(function (inputId) {
            const input = document.getElementById(inputId);
            input.addEventListener('change', function () {
                if (input.files.length > 0) {
                    input.form.requestSubmit();
                }
            });
        });
    });
</script>
@endpush