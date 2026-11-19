@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
@endpush

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/modules/index.css') }}">

<style>
    /* ==========================================================
       LUXURY BURGUNDY & GOLD THEME (MATCHING LEMU LOGO)
    ========================================================== */
    :root {
        --lemu-burgundy: #4a0e17;
        --lemu-burgundy-dark: #2c070e;
        --lemu-gold: #eab308;
        --lemu-gold-light: #fef08a;
        --lemu-gold-gradient: linear-gradient(135deg, #fef08a 0%, #eab308 50%, #ca8a04 100%);
    }

    /* Staggered Entrance & Floating Keyframes */
    @keyframes luxuryFadeUp {
        0% { opacity: 0; transform: translateY(20px) scale(0.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes cardFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-6px); }
    }

    .animate-entrance {
        animation: luxuryFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* ==========================================================
       3D PERSPECTIVE & FLOATING MODULE CARDS
    ========================================================== */
    .modules-perspective-scene {
        perspective: 1400px;
    }

    .module-card-3d {
        border: 1px solid rgba(234, 179, 8, 0.15) !important;
        border-radius: 1.5rem !important;
        background: #ffffff;
        box-shadow: 0 15px 35px -10px rgba(74, 14, 23, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03) !important;
        transform-style: preserve-3d;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.4s ease, border-color 0.4s ease;
        position: relative;
        overflow: hidden;
        animation: cardFloat 5s ease-in-out infinite;
    }

    /* Stagger floating delays for multiple cards */
    .modules-grid > div:nth-child(even) .module-card-3d {
        animation-delay: 2.5s;
    }

    .module-card-3d:hover {
        transform: translateY(-10px) rotateX(4deg) rotateY(-2deg) scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(74, 14, 23, 0.2), 0 0 20px rgba(234, 179, 8, 0.25) !important;
        border-color: rgba(234, 179, 8, 0.5) !important;
        animation-play-state: paused;
    }

    /* Absolute Delete Button Refinement */
    .delete-btn-absolute {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(44, 7, 14, 0.8);
        color: #fef08a;
        border: 1px solid rgba(234, 179, 8, 0.4);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        z-index: 10;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.25s ease;
        backdrop-filter: blur(4px);
    }

    .module-card-3d:hover .delete-btn-absolute {
        opacity: 1;
        transform: scale(1);
    }

    .delete-btn-absolute:hover {
        background: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
        transform: scale(1.1);
    }

    /* Module Image Area with Zoom */
    .module-image-area {
        height: 190px;
        width: 100%;
        overflow: hidden;
        background: #0f172a;
        border-top-left-radius: 1.5rem;
        border-top-right-radius: 1.5rem;
        position: relative;
    }

    .module-image-area::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(44, 7, 14, 0.4), transparent 60%);
        pointer-events: none;
    }

    .module-image-area img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .module-card-3d:hover .module-image-area img {
        transform: scale(1.08);
    }

    .module-content {
        padding: 1.5rem;
        background: linear-gradient(180deg, #ffffff 0%, #fffdfa 100%);
    }

    .module-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #2c070e;
        margin-bottom: 0.5rem;
        letter-spacing: -0.02em;
    }

    .module-description {
        font-size: 0.875rem;
        color: #64748b;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 0;
    }

    /* Primary Burgundy Button with Gold Glow */
    .btn-burgundy-gold {
        background: linear-gradient(135deg, #4a0e17 0%, #2c070e 100%);
        color: #fef08a;
        border: 1px solid rgba(234, 179, 8, 0.4);
        box-shadow: 0 4px 15px rgba(74, 14, 23, 0.25);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .btn-burgundy-gold:hover {
        background: linear-gradient(135deg, #5c121d, #380912);
        color: #ffffff;
        border-color: #eab308;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(234, 179, 8, 0.35);
    }

    /* Tracking typography */
    .tracking-widest-caps {
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 800;
    }
</style>

<div class="container pt-2 pb-5 animate-entrance">
    
    {{-- Modern Header Section (Flushed UP to remove gap) --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-extrabold mb-1 text-slate-900 tracking-tightest h3">Modules</h2>
            <p class="text-secondary small mb-0 font-medium">Manage comprehensive learning tracks made up of bite-sized labs.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($courses->count() >= 2)
                <div class="dropdown">
                    <button class="btn btn-light border fw-semibold px-4 py-2.5 rounded-pill d-inline-flex align-items-center gap-2"
                            type="button"
                            id="chooseCourseDropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <i class="bi bi-journal-bookmark"></i>
                        <span>{{ $selectedCourseId ? $courses->firstWhere('id', $selectedCourseId)?->name : 'Choose Program' }}</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="chooseCourseDropdown">
                        <li>
                            <a class="dropdown-item {{ $selectedCourseId === null ? 'active' : '' }}"
                               href="{{ route('admin.modules.index') }}">All programs</a>
                        </li>
                        @foreach($courses as $course)
                            <li>
                                <a class="dropdown-item {{ $selectedCourseId === $course->id ? 'active' : '' }}"
                                   href="{{ route('admin.modules.index', ['course_id' => $course->id]) }}">
                                    {{ $course->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if($courses->isNotEmpty())
                <button class="btn btn-burgundy-gold fw-bold px-4 py-2.5 rounded-pill d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createModuleModal">
                    <i class="bi bi-plus-lg text-warning"></i>
                    <span>Add New Module</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Modules Grid with 3D Perspective Scene --}}
    <div class="modules-grid modules-perspective-scene">
        @forelse($modules ?? [] as $module)
            <div class="module-card-3d">
                
                {{-- Absolute Delete Button triggering Dialog Modal --}}
                <button type="button" class="delete-btn-absolute shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteModuleModal{{ $module->id }}" onclick="event.preventDefault(); event.stopPropagation();" title="Delete Module">
                    <i class="bi bi-x-lg"></i>
                </button>

                {{-- Entire card links to module detail view --}}
                <a href="{{ route('admin.modules.show', $module) }}" class="module-card-link text-decoration-none">
                    <div class="module-image-area">
                        @if($module->logo_path)
                            <img src="{{ asset('storage/' . $module->logo_path) }}" alt="{{ $module->title }}">
                        @else
                            <img src="{{ asset('images/default-placeholder.png') }}" alt="Default Module Image">
                        @endif
                    </div>
                    
                    <div class="module-content">
                        <h5 class="module-title">{{ $module->title }}</h5>
                        <p class="module-description">{{ $module->description ?? 'No description provided for this module.' }}</p>
                    </div>
                </a>

                <div class="d-flex align-items-center justify-content-between gap-2 px-4 pb-4">
                    <span class="badge rounded-pill {{ $module->course_id ? 'text-bg-primary' : 'text-bg-secondary' }}">
                        {{ $module->course?->name ?? 'Program not assigned' }}
                    </span>
                </div>
            </div>

        @empty
            <div style="grid-column: 1 / -1;">
                <div class="card border-0 text-center py-6 shadow-sm rounded-4 bg-white" style="border-left: 6px solid var(--lemu-burgundy) !important;">
                    <div class="card-body py-5">
                        <div class="display-6 text-warning mb-3 opacity-75">
                            <i class="bi bi-folder2-open"></i>
                        </div>
                        <h5 class="fw-bold text-slate-900 mb-1">No Modules Found</h5>
                        <p class="text-secondary small mb-0">Click "Add New Module" to create a learning module for a program in your college.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

@foreach($modules ?? [] as $module)
    <div class="modal fade" id="deleteModuleModal{{ $module->id }}" tabindex="-1" aria-labelledby="deleteModuleModalLabel{{ $module->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-white border-bottom-0 px-4 pt-4" style="background: var(--lemu-burgundy);">
                    <h5 class="modal-title fw-bold" id="deleteModuleModalLabel{{ $module->id }}">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center bg-white">
                    <div class="display-6 mb-3 text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Are you sure?</h5>
                    <p class="text-secondary small mb-0 px-2">
                        This will permanently delete the <strong>{{ $module->title }}</strong> module and all of its associated topic files. This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer bg-light border-top-0 px-4 pb-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('admin.modules.destroy', $module) }}" method="POST" class="m-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm">Yes, Delete Module</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- Create Module Modal with Logo Upload --}}
@if($courses->isNotEmpty())
    <div class="modal fade" id="createModuleModal" tabindex="-1" aria-labelledby="createModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 px-4 pt-4 text-white" style="background: var(--lemu-burgundy);">
                    <div>
                        <h5 class="modal-title fw-bold text-white" id="createModuleModalLabel">Add New Module</h5>
                        <small class="text-warning-subtle">Create a brand new module for an academic program.</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form action="{{ route('admin.modules.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-slate-700">Module Name</label>
                            <input type="text" name="title" class="form-control bg-light border-0 py-2.5 rounded-3" value="{{ old('title') }}" placeholder="e.g., Network Security" required>
                        </div>

                        <div class="mb-3">
                            <label for="newModuleCourse" class="form-label fw-bold small text-slate-700">Academic program</label>
                            <select id="newModuleCourse" name="course_id" class="form-select bg-light border-0 py-2.5 rounded-3" required>
                                <option value="">Choose a program</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected((int) old('course_id', $selectedCourseId) === $course->id)>{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-slate-700">Module Logo / Banner Image</label>
                            <input type="file" name="logo" class="form-control bg-light border-0 py-2.5 rounded-3" accept="image/*">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-slate-700">Description</label>
                            <textarea name="description" class="form-control bg-light border-0 rounded-3 p-3" rows="4" placeholder="Brief description of what this module covers...">{{ old('description') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-burgundy-gold rounded-pill px-4 fw-semibold shadow-sm">Create Module</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection