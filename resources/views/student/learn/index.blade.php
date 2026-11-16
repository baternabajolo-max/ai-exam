@extends('layouts.app')

@push('styles')
<!-- Reuse the Admin Module Grid CSS -->
<link rel="stylesheet" href="{{ asset('css/admin/modules/index.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* ==========================================================
       LAYOUT & ELEVATION ENHANCEMENTS (ORIGINAL PALETTE PRESERVED)
    ========================================================== */
    .learning-modules-page {
        background-color: #f8fafc;
        min-height: calc(100vh - 72px);
    }

    /* Search input & clear button handling */
    .search-input-wrapper {
        position: relative;
        flex-grow: 1;
    }

    .search-input-wrapper input {
        border-radius: 9999px;
        padding-left: 2.75rem;
        padding-right: 2.5rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .search-icon-fixed {
        position: absolute;
        top: 50%;
        left: 1rem;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .search-clear-link {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        text-decoration: none;
        transition: opacity 0.15s ease;
    }

    .search-clear-link:hover {
        opacity: 0.75;
    }

    /* General Test Card Polish */
    .general-test-card {
        border-radius: 1rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.2s ease;
    }

    .test-icon-badge {
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        background: rgba(var(--bs-primary-rgb, 37, 99, 235), 0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    /* Module Grid & Card Transitions */
    .modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .module-card {
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
        background: #ffffff;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.1) !important;
    }

    .module-card-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .module-image-area {
        position: relative;
        width: 100%;
        height: 180px;
        background-color: #f1f5f9;
        overflow: hidden;
    }

    .module-image-area img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .module-card:hover .module-image-area img {
        transform: scale(1.04);
    }

    .module-content {
        padding: 1.25rem 1.5rem 1.5rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .module-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        line-height: 1.35;
    }

    .module-description {
        font-size: 0.875rem;
        color: #64748b;
        line-height: 1.5;
        margin-bottom: 1.25rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .module-action-link {
        font-size: 0.85rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: transform 0.15s ease;
    }

    .module-card:hover .module-action-link {
        transform: translateX(4px);
    }

    /* Empty State Container */
    .empty-state-box {
        border-radius: 1rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-left: 5px solid #1e293b !important;
    }
</style>
@endpush

@section('content')
<div class="learning-modules-page py-4">
    <div class="container">

        {{-- Header & Search --}}
        <div class="row align-items-center mb-4 g-3">
            <div class="col-md-6">
                <h1 class="h3 fw-bold mb-1 text-dark">Learning Modules</h1>
                <p class="text-muted mb-0 small">Select a module to start reviewing the topics.</p>
            </div>

            <div class="col-md-6">
                <form action="{{ route('student.learn.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <div class="search-input-wrapper">
                        <i class="bi bi-search text-muted search-icon-fixed"></i>
                        <input type="text" 
                               name="search" 
                               class="form-control bg-white border-0 shadow-sm ps-5 py-2" 
                               placeholder="Search modules..." 
                               value="{{ $search ?? '' }}"
                               aria-label="Search modules">
                        @if(!empty($search))
                            <a href="{{ route('student.learn.index') }}" class="text-muted search-clear-link" title="Clear search">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-dark fw-semibold rounded-pill px-4 shadow-sm">
                        Search
                    </button>
                </form>
            </div>
        </div>

        {{-- General Test Card --}}
        <div class="card general-test-card border-0 shadow-sm mb-4">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="test-icon-badge">
                        <i class="bi bi-clipboard2-pulse text-primary"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-1 text-dark">General Test</h2>
                        <p class="text-muted mb-0 small">30 multiple-choice questions. Your first test sets a baseline; future tests focus on topics where your recent general-test scores show lower accuracy, using new wording for those concepts.</p>
                    </div>
                </div>

                <a href="{{ route('student.tests.generate-general') }}"
                   data-ai-loading
                   data-ai-loading-title="Preparing your general test"
                   data-ai-loading-message="Setting up your personalized assessment. The AI will generate questions from your course materials next."
                   class="btn btn-primary rounded-pill px-4 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2 text-nowrap">
                    <i class="bi bi-clipboard2-check"></i>
                    <span>Take general test</span>
                </a>
            </div>
        </div>

        {{-- Modules Grid --}}
        <div class="modules-grid">
            @forelse($modules as $module)
                <div class="module-card shadow-sm">
                    <a href="{{ route('student.learn.show', $module) }}" class="module-card-link">
                        <div class="module-image-area">
                            @if($module->logo_path)
                                <img src="{{ asset('storage/' . $module->logo_path) }}" alt="{{ $module->title }}">
                            @else
                                <img src="{{ asset('images/default-placeholder.png') }}" alt="Default Module Image">
                            @endif
                        </div>

                        <div class="module-content">
                            <h3 class="module-title text-dark">{{ $module->title }}</h3>
                            <p class="module-description">{{ $module->description ?? 'No description provided.' }}</p>
                            
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Course review</span>
                                <span class="module-action-link text-primary small">
                                    <span>Explore Topics</span>
                                    <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div style="grid-column: 1 / -1;">
                    <div class="card empty-state-box border-0 text-center py-5 shadow-sm bg-white">
                        <div class="card-body">
                            <div class="fs-1 text-muted mb-3">
                                <i class="bi bi-search"></i>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-1">No Modules Found</h3>
                            <p class="text-muted small mb-3">
                                @if(!empty($search))
                                    No modules matched your search term "{{ $search }}".
                                @else
                                    There are currently no learning modules available.
                                @endif
                            </p>
                            @if(!empty($search))
                                <a href="{{ route('student.learn.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    Clear Search
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection