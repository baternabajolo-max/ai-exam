@extends('layouts.app')

@push('styles')
<style>
    /* ==========================================================
       LUXURY BURGUNDY & GOLD STUDENT PROFILE THEME
    ========================================================== */
    :root {
        --department-color: {{ $student->department?->color_theme ?: '#4a0e17' }};
        --lemu-burgundy: #4a0e17;
        --lemu-burgundy-dark: #2c070e;
        --lemu-gold: #eab308;
        --lemu-gold-light: #fef08a;
    }

    @keyframes luxuryFadeUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes cardFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-4px); }
    }

    .animate-entrance {
        animation: luxuryFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .student-profile-page {
        background: #f8fafc;
        min-height: calc(100vh - 72px);
    }

    /* 3D Perspective & Floating Hero */
    .profile-hero-3d {
        background: linear-gradient(135deg, var(--lemu-burgundy-dark) 0%, var(--department-color) 60%, #0f172a 100%);
        border-radius: 1.75rem;
        color: #fff;
        min-height: 190px;
        overflow: hidden;
        position: relative;
        border: 1px solid rgba(234, 179, 8, 0.3);
        box-shadow: 0 20px 40px -10px rgba(74, 14, 23, 0.25), inset 0 -20px 30px -15px rgba(0, 0, 0, 0.5);
    }

    .profile-hero-3d::after {
        background: linear-gradient(135deg, rgba(234, 179, 8, 0.08), rgba(0, 0, 0, 0.3));
        border-radius: inherit;
        content: '';
        inset: 0;
        position: absolute;
    }

    .profile-hero-inner {
        position: relative;
        z-index: 1;
    }

    .student-profile-page .profile-avatar {
        background: #fff;
        border: 4px solid rgba(234, 179, 8, 0.6);
        border-radius: 50%;
        color: var(--department-color);
        height: 120px;
        object-fit: cover;
        width: 120px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .student-profile-page .profile-avatar-fallback {
        align-items: center;
        display: flex;
        font-size: 2.8rem;
        font-weight: 800;
        justify-content: center;
    }

    .student-profile-page .hero-meta {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .student-profile-page .hero-name {
        font-size: clamp(1.75rem, 2.2vw, 2.5rem);
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.1;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .student-profile-page .hero-pill {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(234, 179, 8, 0.35);
        border-radius: 999px;
        color: #fef08a;
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    /* 3D Section Cards */
    .section-card-3d {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.25) !important;
        border-radius: 1.5rem;
        box-shadow: 0 12px 30px -8px rgba(74, 14, 23, 0.06), 0 4px 12px rgba(0, 0, 0, 0.02) !important;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        transform-style: preserve-3d;
    }

    .section-card-3d:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px -10px rgba(74, 14, 23, 0.12), 0 0 15px rgba(234, 179, 8, 0.15) !important;
        border-color: rgba(234, 179, 8, 0.5) !important;
    }

    .student-profile-page .section-title {
        align-items: center;
        display: flex;
        font-size: 1.15rem;
        font-weight: 800;
        gap: 0.75rem;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .student-profile-page .section-title i {
        color: var(--department-color);
        background: rgba(234, 179, 8, 0.15);
        padding: 0.55rem;
        border-radius: 0.85rem;
        font-size: 1.1rem;
        border: 1px solid rgba(234, 179, 8, 0.35);
    }

    .student-profile-page .badge-card {
        background: linear-gradient(135deg, #ffffff 0%, #fffbf0 100%);
        border: 1px solid rgba(234, 179, 8, 0.3);
        border-radius: 1rem;
        min-height: 105px;
        padding: 0.85rem;
        text-align: center;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px -2px rgba(74, 14, 23, 0.04);
    }

    .student-profile-page .badge-card:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 10px 20px -4px rgba(234, 179, 8, 0.2);
        border-color: #eab308;
    }

    .student-profile-page .badge-card img {
        height: 46px;
        object-fit: contain;
        width: 46px;
    }

    .student-profile-page .badge-card i {
        color: var(--department-color);
        font-size: 2.2rem;
    }

    .student-profile-page .performance-card {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.25);
        border-radius: 1.25rem;
        padding: 1.25rem;
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.01);
    }

    .student-profile-page .performance-shell {
        align-items: stretch;
        display: grid;
        gap: .75rem;
        grid-template-columns: 4rem minmax(0, 1fr);
        min-height: 250px;
    }

    .student-profile-page .performance-y-axis {
        align-self: start;
        color: #64748b;
        font-size: .7rem;
        height: 220px;
        min-height: 220px;
        position: relative;
        text-align: right;
    }

    .student-profile-page .performance-y-axis .axis-value {
        position: absolute;
        right: 0;
        transform: translateY(-50%);
        font-weight: 600;
    }

    .student-profile-page .performance-y-axis .axis-value-100 { top: 0; }
    .student-profile-page .performance-y-axis .axis-value-75 { top: 25%; }
    .student-profile-page .performance-y-axis .axis-value-50 { top: 50%; }
    .student-profile-page .performance-y-axis .axis-value-25 { top: 75%; }
    .student-profile-page .performance-y-axis .axis-value-0 { top: 100%; }

    .student-profile-page .performance-y-axis .axis-title {
        color: #475569;
        font-size: .68rem;
        font-weight: 800;
        left: -1.5rem;
        letter-spacing: .05em;
        position: absolute;
        top: 50%;
        transform: rotate(-90deg) translateX(-50%);
        transform-origin: left top;
        white-space: nowrap;
        text-transform: uppercase;
    }

    .student-profile-page .performance-plot {
        align-items: stretch;
        background: linear-gradient(to top, rgba(234, 179, 8, 0.08) 1px, transparent 1px);
        background-size: 100% 25%;
        border-bottom: 2px solid #cbd5e1;
        border-left: 2px solid #cbd5e1;
        display: flex;
        gap: clamp(.25rem, 1.5vw, 1rem);
        height: 220px;
        padding: .75rem clamp(.25rem, 1.5vw, 1rem) 0;
    }

    .student-profile-page .performance-day {
        align-items: center;
        display: flex;
        flex: 1 1 0;
        flex-direction: column;
        height: 100%;
        justify-content: flex-end;
        min-width: 0;
        position: relative;
    }

    .student-profile-page .performance-value {
        color: #0f172a;
        font-size: .7rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: .45rem;
        min-height: .7rem;
    }

    .student-profile-page .performance-bar {
        background: linear-gradient(
            180deg,
            var(--lemu-gold) 0%,
            var(--department-color) 100%
        );
        border-radius: .55rem .55rem 0 0;
        width: clamp(1.25rem, 65%, 2.75rem);
        box-shadow: 0 4px 10px rgba(74, 14, 23, 0.2);
    }

    .student-profile-page .performance-label {
        bottom: -1.7rem;
        color: #64748b;
        font-size: .68rem;
        font-weight: 700;
        position: absolute;
        white-space: nowrap;
    }

    .student-profile-page .performance-legend {
        color: #64748b;
        font-size: .75rem;
        margin-top: 1rem;
        font-weight: 500;
    }

    .student-profile-page .general-test-chart-scroll {
        overflow-x: auto;
    }

    .student-profile-page .general-test-chart {
        display: block;
        min-width: 0;
        width: 100%;
    }

    .student-profile-page .analytics-summary-card {
        background: #fff;
        border: 1px solid rgba(234, 179, 8, 0.25);
        border-radius: 1rem;
        box-shadow: 0 4px 12px -2px rgba(74, 14, 23, 0.04);
        height: 100%;
    }

    .student-profile-page .analytics-summary-value {
        color: var(--department-color);
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .student-profile-page .analytics-grid {
        stroke: #e2e8f0;
        stroke-dasharray: 4 4;
    }

    .student-profile-page .analytics-target {
        stroke: #d97706;
        stroke-dasharray: 6 5;
        stroke-width: 2;
    }

    .student-profile-page .activity-summary-card,
    .student-profile-page .readiness-summary-card {
        background: #fff;
        border: 1px solid rgba(234, 179, 8, 0.25);
        border-radius: 1rem;
        height: 100%;
        padding: 1rem;
    }

    .student-profile-page .activity-chart-scroll {
        overflow-x: auto;
    }

    .student-profile-page .activity-chart {
        display: block;
        min-width: 680px;
        width: 100%;
    }

    .student-profile-page .readiness-module-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        padding: .8rem 1rem;
    }

    .student-profile-page .test-row {
        border: 1px solid rgba(234, 179, 8, 0.2);
        border-radius: .85rem;
        font-size: .8rem;
        padding: .6rem .85rem;
        background: #fff;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    }

    .student-profile-page .test-row:hover {
        border-color: rgba(234, 179, 8, 0.5);
        transform: scale(1.01);
        box-shadow: 0 4px 12px rgba(74, 14, 23, 0.05);
    }

    .student-profile-page .module-card {
        cursor: pointer;
        min-height: 130px;
        padding: 1rem;
        background: #fff;
        border: 1px solid rgba(234, 179, 8, 0.25);
        border-radius: 1rem;
        transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
    }

    .student-profile-page .module-card:hover {
        border-color: #eab308;
        box-shadow: 0 10px 25px rgba(74, 14, 23, 0.1);
        transform: translateY(-3px);
    }

    .student-profile-page .module-logo {
        align-items: center;
        background: rgba(234, 179, 8, 0.15);
        border-radius: 50%;
        display: flex;
        height: 46px;
        justify-content: center;
        overflow: hidden;
        width: 46px;
        border: 1px solid rgba(234, 179, 8, 0.3);
    }

    .student-profile-page .module- logo img {
        height: 100%;
        object-fit: cover;
        width: 100%;
    }

    .student-profile-page .module-logo i {
        color: var(--department-color);
        font-size: 1.35rem;
    }

    .student-profile-page .module-points {
        color: #ca8a04;
        font-size: .78rem;
        font-weight: 700;
    }

    .student-profile-page .modal-header {
        background: linear-gradient(135deg, var(--lemu-burgundy-dark), var(--department-color));
        color: #fff;
        border-bottom: 1px solid rgba(234, 179, 8, 0.3);
    }

    .tracking-widest-caps {
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .btn-burgundy-gold {
        background: linear-gradient(135deg, #4a0e17 0%, #2c070e 100%);
        color: #fef08a;
        border: 1px solid rgba(234, 179, 8, 0.4);
        box-shadow: 0 4px 15px rgba(74, 14, 23, 0.25);
        transition: all 0.25s ease;
    }

    .btn-burgundy-gold:hover {
        background: linear-gradient(135deg, #5c121d, #380912);
        color: #ffffff;
        border-color: #eab308;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(234, 179, 8, 0.35);
    }
</style>
@endpush

@section('content')
<div class="student-profile-page py-4 animate-entrance">
    <div class="container">

        <a href="{{ $profileBackUrl }}" class="btn btn-sm btn-outline-dark rounded-pill px-3.5 mb-4 fw-semibold shadow-2xs d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            Back to student search
        </a>

        <!-- Profile Hero Banner -->
        <section class="profile-hero-3d mb-4 p-4 p-md-5">
            <div class="profile-hero-inner d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4">

                <div class="hero-meta">

                    @if($student->profile_picture_path)
                        <img
                            class="profile-avatar"
                            src="{{ asset('storage/'.$student->profile_picture_path) }}"
                            alt="{{ $student->name }}"
                        >
                    @else
                        <div class="profile-avatar profile-avatar-fallback bg-white text-dark">
                            {{ strtoupper(substr($student->name, 0, 1)) }}
                        </div>
                    @endif

                    <div>

                        <div class="d-flex flex-wrap gap-2 mb-2">

                            <span class="hero-pill px-3 py-1 d-inline-flex align-items-center gap-1">
                                <i class="bi bi-person-fill text-warning"></i>
                                {{ ucfirst($student->role) }}
                            </span>

                            <span class="hero-pill px-3 py-1 d-inline-flex align-items-center gap-1">
                                <i class="bi bi-building text-warning"></i>
                                {{ $student->department?->name ?? 'No department' }}
                            </span>
                            <span class="hero-pill px-3 py-1 d-inline-flex align-items-center gap-1">
                                <i class="bi bi-book text-warning"></i>
                                {{ $student->course?->name ?? 'No program' }}
                            </span>

                        </div>

                        <h1 class="hero-name">
                            {{ $student->name }}
                        </h1>

                        <p class="mb-0 text-light opacity-75 small mt-1 font-monospace">
                            <i class="bi bi-envelope me-1 text-warning"></i>
                            {{ $student->email }}
                        </p>

                    </div>
                </div>

                <div class="small text-light opacity-75 text-md-end bg-black bg-opacity-25 p-3 rounded-4 border border-warning border-opacity-25">
                    <div class="d-flex align-items-center justify-content-md-end gap-1.5 text-warning mb-1">
                        <i class="bi bi-calendar3"></i>
                        <span class="fw-semibold">Member since</span>
                    </div>
                    <strong class="text-white fs-6">
                        {{ $student->created_at?->format('M d, Y') }}
                    </strong>
                </div>

            </div>
        </section>

        <!-- Achievements Section -->
        <section class="section-card-3d p-4 mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h2 class="section-title mb-0">
                    <i class="bi bi-award-fill"></i>
                    Achievements
                </h2>

                <button
                    class="btn btn-sm btn-outline-dark rounded-pill px-3.5 fw-semibold shadow-2xs"
                    data-bs-toggle="modal"
                    data-bs-target="#achievementsModal"
                >
                    View all <i class="bi bi-arrow-right ms-1"></i>
                </button>

            </div>

            <p class="text-secondary small mb-3">
                <span class="fw-bold text-dark">{{ $student->achievements->count() }}</span>
                claimed badge{{ $student->achievements->count() === 1 ? '' : 's' }}
            </p>

            <div class="row g-3">

                @forelse($student->achievements->take(5) as $achievement)

                    <div class="col-6 col-md">

                        <div class="badge-card d-flex flex-column align-items-center justify-content-center">

                            @if($achievement->badge_image)
                                <img
                                    src="{{ asset('storage/'.$achievement->badge_image) }}"
                                    alt="{{ $achievement->title }}"
                                    class="mb-2"
                                >
                            @else
                                <i class="bi {{ $achievement->icon ?: 'bi-award' }} mb-1"></i>
                            @endif

                            <div class="fw-bold small text-slate-900 text-truncate w-100 px-1">
                                {{ $achievement->title }}
                            </div>

                        </div>

                    </div>

                @empty

                    <p class="text-secondary small mb-0 py-3 text-center">
                        No badges have been claimed yet.
                    </p>

                @endforelse

            </div>

        </section>

        @if($isFacultyViewer)
            @include('partials.student-readiness', [
                'readinessAudience' => "the student's",
                'readinessFocusUrl' => $weakestModuleRecord ? route('admin.modules.show', $weakestModuleRecord) : null,
            ])

            @include('partials.student-activity-analytics', [
                'analyticsUrl' => route('admin.students.activity.index', $student),
                'topicQuizzesUrl' => route('admin.students.activity.topics.quizzes', ['student' => $student, 'subtopic' => '__SUBTOPIC__']),
            ])
        @endif
        <!-- Statistics Section -->
        <section class="section-card-3d p-4 mb-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h2 class="section-title mb-0">
                    <i class="bi bi-bar-chart-fill"></i>
                    Statistics
                </h2>

            </div>

                @include('partials.general-test-performance-chart', [
                    'performanceCardClass' => 'performance-card mb-4',
                    'performanceTitle' => 'General-test performance',
                    'performanceAudience' => "The student's",
                    'performanceRoute' => $performanceRoute,
                    'performanceChartPrimaryColor' => 'var(--department-color)',
                ])

                <section class="performance-card mb-4" aria-labelledby="general-test-history-title">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h3 id="general-test-history-title" class="h5 fw-bold text-slate-900 mb-1">General-test history</h3>
                            <p class="text-secondary small mb-0">Every general-test attempt, with a review of each recorded response.</p>
                        </div>
                        <span class="badge rounded-pill bg-light text-secondary border">{{ $generalTestHistory->count() }} attempts</span>
                    </div>
                    <div class="row g-2">
                        @forelse($generalTestHistory as $result)
                            @php
                                $scorePercentage = $result->display_percentage;
                                $scoreTone = $scorePercentage >= 75 ? 'text-success' : 'text-danger';
                            @endphp
                            <div class="col-md-6">
                                <article class="test-row d-flex align-items-center gap-3 h-100">
                                    <span class="badge rounded-circle bg-warning-subtle text-dark border border-warning-subtle fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="flex-grow-1 min-w-0">
                                        <h4 class="small fw-semibold text-slate-900 text-truncate mb-1" title="{{ $result->test?->title ?? 'General Test' }}">
                                            {{ $result->test?->title ?? 'General Test' }}
                                        </h4>
                                        <small class="text-secondary">{{ $result->created_at->format('M d, Y · h:i A') }}</small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="{{ $scoreTone }}">{{ $scorePercentage }}%</strong>
                                        <small class="d-block text-secondary">{{ $result->score }}/{{ $result->display_total_questions }}</small>
                                    </div>
                                    <a
                                        class="btn btn-sm btn-outline-primary rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width: 36px; height: 36px;"
                                        href="{{ $isFacultyViewer
                                            ? route('admin.students.results.show', ['student' => $student, 'result' => $result])
                                            : route('super-admin.students.results.show', ['student' => $student, 'result' => $result]) }}"
                                        aria-label="View {{ $result->test?->title ?? 'General Test' }} results"
                                        title="View test results"
                                    >
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </article>
                            </div>
                        @empty
                            <p class="text-secondary small mb-0 py-3 text-center">No general-test attempts yet.</p>
                        @endforelse
                    </div>
                </section>

        </section>

        <!-- Modules Section -->
        <section class="section-card-3d p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h2 class="section-title mb-0">
                    <i class="bi bi-bookshelf"></i>
                    Modules
                </h2>

                <button
                    class="btn btn-sm btn-outline-dark rounded-pill px-3.5 fw-semibold shadow-2xs"
                    data-bs-toggle="modal"
                    data-bs-target="#modulesModal"
                >
                    View all <i class="bi bi-arrow-right ms-1"></i>
                </button>

            </div>

            <div class="row g-3">

                @foreach($modules->take(4) as $module)

                    @include(
                        'admin.students.partials.module-card',
                        [
                            'module' => $module,
                            'student' => $student
                        ]
                    )

                @endforeach

            </div>

        </section>

    </div>
</div>

<!-- Achievements Modal -->
<div class="modal fade" id="achievementsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header px-4 pt-4 pb-3">
                <h2 class="modal-title h5 fw-bold text-white d-flex align-items-center gap-2">
                    <i class="bi bi-award text-warning"></i>
                    Claimed Achievements
                </h2>
                <button
                    type="button"
                    class="btn-close btn-close-white shadow-none"
                    data-bs-dismiss="modal"
                ></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-3">
                    @forelse($student->achievements as $achievement)
                        <div class="col-md-4">
                            <div class="badge-card d-flex flex-column align-items-center justify-content-center p-3 h-100">
                                @if($achievement->badge_image)
                                    <img
                                        src="{{ asset('storage/'.$achievement->badge_image) }}"
                                        alt="{{ $achievement->title }}"
                                        class="mb-2"
                                    >
                                @else
                                    <i class="bi {{ $achievement->icon ?: 'bi-award' }} mb-1"></i>
                                @endif
                                <div class="fw-bold small text-slate-900 mt-1">
                                    {{ $achievement->title }}
                                </div>
                                <small class="text-secondary text-center mt-1" style="font-size: 0.75rem;">
                                    {{ $achievement->description }}
                                </small>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary text-center mb-0 py-4">
                            No badges have been claimed yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modules Modal -->
<div class="modal fade" id="modulesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header px-4 pt-4 pb-3">
                <h2 class="modal-title h5 fw-bold text-white d-flex align-items-center gap-2">
                    <i class="bi bi-bookshelf text-warning"></i>
                    Available Modules
                </h2>
                <button
                    type="button"
                    class="btn-close btn-close-white shadow-none"
                    data-bs-dismiss="modal"
                ></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-3">
                    @foreach($modules as $module)
                        @include(
                            'admin.students.partials.module-card',
                            [
                                'module' => $module,
                                'student' => $student,
                                'modal' => true
                            ]
                        )
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @if($isFacultyViewer)
        @include('partials.student-readiness-script')
        @include('partials.student-activity-script')
    @endif
@endpush

@endsection