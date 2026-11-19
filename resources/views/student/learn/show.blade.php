@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    /* ==========================================================
       SUBTOPICS & MODULE SHOWCASE
    ========================================================== */
    .module-detail-workspace {
        background-color: #f8fafc;
        min-height: calc(100vh - 72px);
    }

    /* Cancels the parent layout's top padding so the banner touches the navbar flush */
    .module-hero-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        padding: 2.75rem 0 3rem;
        position: relative;
        overflow: hidden;
        margin-top: -1.5rem !important; /* Exactly offsets layout py-4/my-4 without eating the navbar */
    }

    .module-hero-banner::after {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 450px;
        height: 450px;
        background: radial-gradient(circle, rgba(var(--bs-primary-rgb, 37, 99, 235), 0.15) 0%, transparent 70%);
        pointer-events: none;
    }

    .back-nav-link {
        color: #94a3b8;
        font-size: 0.825rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: color 0.15s ease, transform 0.15s ease;
    }

    .back-nav-link:hover {
        color: #ffffff;
        transform: translateX(-3px);
    }

    /* Module Topic Card Layout */
    .subtopic-item-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.5rem;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .subtopic-item-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.06) !important;
    }

    .subtopic-icon-wrap {
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 0.85rem;
        background: rgba(var(--bs-primary-rgb, 37, 99, 235), 0.08);
        color: var(--bs-primary, #2563eb);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    /* Empty State Container */
    .subtopic-empty-card {
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 1rem;
        padding: 3rem 1.5rem;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="module-detail-workspace">

    {{-- Hero Header (Flush with Navigation) --}}
    <div class="module-hero-banner mb-4">
        <div class="container">
            <div class="mb-3">
                <a href="{{ route('student.learn.index') }}" class="back-nav-link">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to all modules</span>
                </a>
            </div>

            <div class="row align-items-center">
                <div class="col-lg-9">
                    <span class="badge bg-light text-dark rounded-pill px-3 py-1 fw-semibold small mb-2 shadow-sm">
                        Module
                    </span>
                    <h1 class="fw-bold display-6 mb-2 text-white">{{ $module->title }}</h1>
                    <p class="text-light opacity-75 lead mb-0" style="font-size: 1rem; line-height: 1.6; max-width: 760px;">
                        {{ $module->description ?? 'No description provided for this module.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Topics List --}}
    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0">Topics to Review</h2>
                        <small class="text-muted">Master each topic to prepare for the general diagnostic assessment.</small>
                    </div>
                    <span class="badge bg-white border text-secondary rounded-pill px-3 py-1 fw-semibold small shadow-sm">
                        {{ $module->subtopics->count() }} {{ \Illuminate\Support\Str::plural('Topic', $module->subtopics->count()) }}
                    </span>
                </div>

                <div class="d-flex flex-column gap-3">
                    @forelse($module->subtopics as $subtopic)
                        @php
                            $earnedPoints = $subtopic->earned_points ?? 0;
                            $isPerfect = ($earnedPoints == 30);
                            $badgeClass = $isPerfect ? 'bg-warning text-dark' : ($earnedPoints > 0 ? 'bg-success text-white' : 'bg-secondary text-white');
                        @endphp
                        <div class="subtopic-item-card shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="subtopic-icon-wrap">
                                    <i class="bi bi-journal-bookmark-fill"></i>
                                </div>
                                <div>
                                    <h3 class="h6 fw-bold mb-1 text-dark">{{ $subtopic->title }}</h3>
                                    <span class="text-muted small d-block mb-2">Review material & interactive practice quiz</span>

                                    <!-- Points System Badge -->
                                    <span class="badge {{ $badgeClass }} px-3 py-1 rounded-pill shadow-sm align-middle">
                                        <i class="bi bi-star-fill {{ $isPerfect ? 'text-dark' : 'text-warning' }} me-1"></i>
                                        Points Earned: {{ $earnedPoints }} / 30
                                    </span>
                                </div>
                            </div>

                            <div class="w-100 w-md-auto text-end mt-2 mt-md-0">
                                <a href="{{ route('student.learn.review', $subtopic) }}" target="_blank" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                    <span>Start Review</span>
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 0.78rem;"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="subtopic-empty-card shadow-sm">
                            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-light text-muted mb-2" style="width: 3.5rem; height: 3.5rem;">
                                <i class="bi bi-folder-x fs-3"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-1">No Topics Available</h4>
                            <p class="text-muted small mb-0">No review topics are currently published under this module.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection