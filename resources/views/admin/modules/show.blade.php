@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/super-admin/modules.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* ==========================================================
       LUXURY BURGUNDY & GOLD THEME (MATCHING LEMU LOGO)
    ========================================================== */
    :root {
        --lemu-burgundy: #4a0e17;
        --lemu-burgundy-dark: #2c070e;
        --lemu-gold: #eab308;
        --lemu-gold-light: #fef08a;
    }

    @keyframes luxuryFadeUp {
        0% { opacity: 0; transform: translateY(15px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    .animate-entrance {
        animation: luxuryFadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Immersive Luxury Hero Banner (Flushed UP to remove gap) */
    .module-hero-banner {
        background: linear-gradient(135deg, #2c070e 0%, #4a0e17 50%, #0f172a 100%);
        color: #ffffff;
        padding: 3rem 0 2.5rem;
        margin-top: -1.5rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid rgba(234, 179, 8, 0.2);
        box-shadow: 0 15px 30px -10px rgba(74, 14, 23, 0.3);
    }

    @media (min-width: 768px) {
        .module-hero-banner > .container {
            padding-inline: clamp(1.5rem, 2vw, 2rem);
        }
    }

    .module-back-link {
        color: #fef08a;
        transition: color .18s ease-out;
    }

    .module-back-link:hover,
    .module-back-link:focus-visible {
        color: #ffffff;
    }

    .module-back-link:focus-visible {
        outline: 2px solid #fef08a;
        outline-offset: 3px;
    }

    /* 3D Floating Subtopic Cards */
    .subtopic-item {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.2) !important;
        border-radius: 1.25rem !important;
        padding: 1.25rem 1.5rem !important;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 8px 20px -4px rgba(74, 14, 23, 0.05);
        transform-style: preserve-3d;
    }

    .subtopic-item:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 15px 30px -6px rgba(74, 14, 23, 0.12), 0 0 15px rgba(234, 179, 8, 0.2);
        border-color: rgba(234, 179, 8, 0.6) !important;
    }

    .subtopic-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(234, 179, 8, 0.12);
        color: #ca8a04;
        font-size: 1.35rem;
        flex-shrink: 0;
        border: 1px solid rgba(234, 179, 8, 0.25);
    }

    /* Sidebar Panel */
    .sidebar-panel {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.2);
        border-radius: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(74, 14, 23, 0.06);
    }

    /* Burgundy & Gold Buttons */
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

    /* Tracking typography */
    .tracking-widest-caps {
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .module-tabs {
        align-items: center;
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 999px;
        display: inline-flex;
        gap: .25rem;
        margin-top: 2rem;
        max-width: 100%;
        overflow-x: auto;
        padding: .25rem;
        position: relative;
        scrollbar-width: none;
    }

    .module-tabs::-webkit-scrollbar { display: none; }

    .module-tab {
        background: transparent;
        border: 0;
        border-radius: 999px;
        color: #0d6efd;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        flex: 0 0 auto;
        font-size: .925rem;
        font-weight: 700;
        min-height: 40px;
        padding: .55rem 1rem;
        position: relative;
        transition: color .2s ease;
        z-index: 1;
    }

    .module-tab:hover,
    .module-tab:focus-visible { color: #0a58ca; }

    .module-tab:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }

    .module-tab.is-active { color: #ffffff; }

    .module-tab-count {
        align-items: center;
        background: #e9ecef;
        border-radius: 999px;
        color: #475569;
        display: inline-flex;
        font-size: .75rem;
        font-variant-numeric: tabular-nums;
        font-weight: 700;
        justify-content: center;
        min-width: 1.35rem;
        padding: .1rem .35rem;
    }

    .module-tab.is-active .module-tab-count {
        background: #ffc107;
        color: #212529;
    }

    .module-tab-indicator {
        background: #0d6efd;
        border-radius: 999px;
        height: calc(100% - .5rem);
        left: 0;
        position: absolute;
        top: .25rem;
        transition: transform .22s ease-out, width .22s ease-out;
    }

    .module-tab-panel {
        opacity: 0;
        transform: translateY(5px);
        transition: opacity .2s ease-out, transform .2s ease-out;
    }

    .module-tab-panel.is-active {
        opacity: 1;
        transform: translateY(0);
    }

    .module-tab-panel[hidden] { display: none; }

    .module-ranking-card {
        background: #fff;
        border: 1px solid rgba(234, 179, 8, .2);
        border-radius: 1.25rem;
        box-shadow: 0 10px 25px -5px rgba(74, 14, 23, .06);
        overflow: hidden;
    }

    .module-ranking-table { margin-bottom: 0; min-width: 700px; }
    .module-student-table { min-width: 1120px; }
    .module-ranking-table thead th {
        background: #fbf8f6;
        border-bottom: 1px solid #eee5e2;
        color: #6b5558;
        font-size: .72rem;
        letter-spacing: .06em;
        padding: .9rem 1rem;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .module-ranking-table tbody td { padding: .9rem 1rem; vertical-align: middle; }
    .module-ranking-table tbody tr:last-child td { border-bottom: 0; }
    .module-ranking-table tbody tr[hidden] { display: none !important; }
    .module-ranking-table tbody tr.module-rank-divider td { border-top: 2px solid rgba(234, 179, 8, .45); }
    .module-tabular { font-variant-numeric: tabular-nums; }

    .module-rank-badge {
        align-items: center;
        background: #f3efed;
        border-radius: 999px;
        color: #5c4749;
        display: inline-flex;
        font-size: .8rem;
        font-weight: 800;
        height: 2rem;
        justify-content: center;
        width: 2rem;
    }
    .module-rank-badge--gold { background: #fef3c7; color: #92400e; }
    .module-rank-badge--silver { background: #e2e8f0; color: #334155; }
    .module-rank-badge--bronze { background: #ffedd5; color: #9a3412; }

    .module-student-row { cursor: pointer; transition: background-color .18s ease; }
    .module-student-row:hover,
    .module-student-row:focus-visible { background: #fffbeb; outline: none; }
    .module-student-controls { align-items: end; display: flex; flex-wrap: wrap; gap: .75rem; }
    .module-student-controls .form-control,
    .module-student-controls .form-select { min-height: 44px; }
    .module-student-controls .form-select { min-width: 150px; }
    .module-info-tooltip {
        align-items: center;
        background: transparent;
        border: 0;
        color: #806a6d;
        display: inline-flex;
        font-size: .85rem;
        justify-content: center;
        line-height: 1;
        min-height: 32px;
        min-width: 32px;
        padding: .25rem;
        vertical-align: middle;
    }
    .module-info-tooltip:hover,
    .module-info-tooltip:focus-visible { color: #4a0e17; }
    .tooltip .tooltip-inner { max-width: 320px; text-align: left; }
    .module-info-tooltip-popup { transition: opacity 150ms ease-out; }
    .module-activity-kpis {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .module-activity-kpi {
        background: #fbf8f6;
        border: 1px solid #eee5e2;
        border-radius: .875rem;
        min-width: 0;
        padding: 1rem;
    }
    .module-activity-kpi-label {
        align-items: center;
        color: #6b5558;
        display: flex;
        font-size: .72rem;
        font-weight: 700;
        gap: .25rem;
        justify-content: space-between;
        margin-bottom: .35rem;
    }
    .module-activity-kpi-label .module-info-tooltip { flex-shrink: 0; }
    .module-activity-kpi-value {
        color: #2c070e;
        font-size: 1rem;
        font-weight: 800;
        overflow-wrap: anywhere;
    }
    .module-topic-progress { height: .45rem; }
    .module-status-pill {
        border-radius: 999px;
        display: inline-flex;
        font-size: .75rem;
        font-weight: 700;
        padding: .3rem .65rem;
        white-space: nowrap;
    }
    .module-status-pill--completed,
    .module-status-pill--on-track { background: #dcfce7; color: #166534; }
    .module-status-pill--in-progress { background: #fef3c7; color: #92400e; }
    .module-status-pill--needs-review { background: #ffedd5; color: #9a3412; }
    .module-status-pill--inactive,
    .module-status-pill--not-started { background: #f1f5f9; color: #475569; }
    .module-topic-breakdown { min-width: 760px; }
    .module-attempt-history { min-width: 520px; }
    .module-chart-card {
        background: #fff;
        border: 1px solid #ece7e5;
        border-radius: 1rem;
        padding: 1rem;
    }
    .module-activity-tabs {
        border-bottom: 1px solid #ece7e5;
        display: flex;
        gap: .5rem;
        margin-bottom: 1rem;
        overflow-x: auto;
    }
    .module-activity-tab {
        background: transparent;
        border: 0;
        border-bottom: 3px solid transparent;
        color: #6b5558;
        flex: 0 0 auto;
        font-size: .875rem;
        font-weight: 700;
        min-height: 44px;
        padding: .5rem .85rem;
        transition: color .18s ease, border-color .18s ease;
    }
    .module-activity-tab.is-active { border-bottom-color: #4a0e17; color: #4a0e17; }
    .module-activity-tab:disabled { color: #a8a29e; cursor: not-allowed; }
    .module-activity-tab:focus-visible { outline: 2px solid #92400e; outline-offset: -2px; }
    .module-chart-unit-label {
        color: #6b5558;
        font-size: .75rem;
        font-weight: 700;
        margin-bottom: .5rem;
    }
    .module-chart-layout {
        align-items: start;
        display: grid;
        gap: .5rem;
        grid-template-columns: 3.25rem minmax(0, 1fr);
    }
    .module-chart-layout.is-calendar { grid-template-columns: minmax(0, 1fr); }
    .module-chart-stage { min-width: 0; width: 100%; }
    .module-chart-y-axis {
        align-items: flex-end;
        color: #6b5558;
        display: flex;
        flex-direction: column;
        font-size: .65rem;
        font-variant-numeric: tabular-nums;
        height: 8rem;
        justify-content: space-between;
        line-height: 1;
        margin-top: 1.25rem;
        padding-bottom: 0;
        text-align: right;
    }
    .module-chart-y-axis[hidden] { display: none; }
    .module-chart-bars {
        align-items: start;
        display: grid;
        grid-auto-columns: minmax(3.25rem, 1fr);
        grid-auto-flow: column;
        gap: .5rem;
        grid-template-columns: none;
        height: 10.25rem;
        overflow-x: auto;
        padding: 0 .1rem;
    }
    .module-chart-bar {
        align-items: center;
        display: flex;
        flex-direction: column;
        height: 10.25rem;
        min-width: 0;
        padding-top: 1.25rem;
        position: relative;
    }
    .module-chart-bar--interactive {
        background: transparent;
        border: 0;
        border-radius: .5rem;
        color: inherit;
        cursor: pointer;
        font: inherit;
        padding: 1.25rem .25rem 0;
    }
    .module-chart-bar--interactive:hover,
    .module-chart-bar--interactive.is-selected { background: #fffbeb; }
    .module-chart-bar--interactive:focus-visible,
    .module-calendar-cell:focus-visible { outline: 2px solid #92400e; outline-offset: 2px; }
    .module-chart-value {
        color: #5c4749;
        font-size: .62rem;
        font-variant-numeric: tabular-nums;
        left: 0;
        position: absolute;
        right: 0;
        text-align: center;
        top: 0;
        white-space: nowrap;
    }
    .module-chart-plot {
        align-items: end;
        border-bottom: 1px solid #ded8d5;
        display: flex;
        height: 8rem;
        justify-content: center;
        position: relative;
        width: 100%;
    }
    .module-chart-gridline {
        border-top: 1px solid #f0edeb;
        left: 0;
        pointer-events: none;
        position: absolute;
        right: 0;
        z-index: 0;
    }
    .module-chart-fill {
        background: linear-gradient(180deg, #d6a11d, #4a0e17);
        border-radius: .45rem .45rem 0 0;
        max-width: 2.5rem;
        min-height: 0;
        transform: scaleY(0);
        transform-origin: bottom;
        transition: transform .48s cubic-bezier(.2, .8, .2, 1);
        width: 72%;
        z-index: 1;
    }
    .module-chart-fill.is-animated { transform: scaleY(1); }
    .module-chart-label {
        color: #6b5558;
        font-size: .68rem;
        line-height: 1.25;
        margin-top: .45rem;
        max-width: 100%;
        overflow-wrap: anywhere;
        text-align: center;
    }
    .module-chart-view {
        opacity: 0;
        transform: translateY(4px);
        transition: opacity .18s ease-out, transform .18s ease-out;
    }
    .module-chart-view.is-active { opacity: 1; transform: translateY(0); }
    .module-chart-view[hidden] { display: none !important; }
    .module-activity-calendar {
        display: grid;
        gap: .5rem;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        margin-inline: auto;
        max-width: 28rem;
        width: 100%;
    }
    .module-activity-calendar .module-chart-label { text-align: center; }
    .module-calendar-cell {
        align-items: center;
        aspect-ratio: 1;
        background: #f3f4f6;
        border: 0;
        border-radius: .35rem;
        color: #475569;
        cursor: pointer;
        display: flex;
        font-size: .7rem;
        justify-content: center;
        min-height: 2.5rem;
        padding: 0;
        width: 100%;
    }
    .module-calendar-cell[data-level="1"] { background: #fef3c7; }
    .module-calendar-cell[data-level="2"] { background: #fcd34d; color: #422006; }
    .module-calendar-cell[data-level="3"] { background: #d6a11d; color: #fff; }
    .module-calendar-cell[data-level="4"] { background: #4a0e17; color: #fff; }
    .module-calendar-cell--selected {
        background: #4a0e17;
        box-shadow: inset 0 0 0 2px #92400e;
        color: #fff;
    }
    .module-calendar-cell--empty { background: transparent; }
    .module-calendar-cell--empty:focus-visible { outline: none; }
    .module-chart-error { color: #b91c1c; }

    #moduleStudentActivityModal .modal-dialog,
    #moduleStudentNoticeModal .modal-dialog {
        transform: scale(.96);
        transition: transform .22s ease-out;
    }
    #moduleStudentActivityModal.show .modal-dialog,
    #moduleStudentNoticeModal.show .modal-dialog { transform: scale(1); }

    @media (prefers-reduced-motion: reduce) {
        .module-tab-indicator,
        .module-tab-panel,
        .module-chart-fill,
        .module-activity-tab,
        .module-chart-view,
        .module-info-tooltip-popup,
        #moduleStudentActivityModal .modal-dialog,
        #moduleStudentNoticeModal .modal-dialog { transition: none; }
    }
    @media (max-width: 767.98px) {
        .module-activity-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 420px) {
        .module-activity-kpis { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endpush

@section('content')

{{-- Immersive Dark Burgundy Hero Banner --}}
<div class="module-hero-banner animate-entrance">
    <div class="container">
        <!-- Back Link -->
        <a href="{{ route('admin.modules.index') }}" class="module-back-link text-decoration-none small fw-bold mb-3 d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i> Back to Modules
        </a>

        <!-- Header Banner Grid -->
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h1 class="fw-extrabold display-6 mb-2 tracking-tight text-white">{{ $module->title }}</h1>
                <p class="text-light opacity-75 lead mb-0" style="font-size: 1.05rem; line-height: 1.6;">
                    {{ $module->description ?? 'No description provided for this module.' }}
                </p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                @if($module->logo_path)
                    <img src="{{ asset('storage/' . $module->logo_path) }}" alt="{{ $module->title }}" class="img-fluid rounded-4 shadow-lg border border-warning border-opacity-25" style="max-height: 160px; object-fit: cover;">
                @else
                    <div class="p-4 bg-black bg-opacity-25 rounded-4 text-warning d-inline-flex flex-column align-items-center justify-content-center border border-warning border-opacity-25" style="width: 140px; height: 140px;">
                        <i class="bi bi-shield-lock display-5"></i>
                    </div>
                @endif
            </div>
        </div>
        <nav class="module-tabs" id="moduleDetailTabs" role="tablist" aria-label="Module details">
            <span class="module-tab-indicator" aria-hidden="true"></span>
            <button type="button" class="module-tab is-active" id="module-topics-tab" role="tab" aria-selected="true" aria-controls="module-topics-panel" data-module-tab="topics">
                <span>Topics</span><span class="module-tab-count">{{ $module->subtopics->count() }}</span>
            </button>
            <button type="button" class="module-tab" id="module-leaderboards-tab" role="tab" aria-selected="false" aria-controls="module-leaderboards-panel" data-module-tab="leaderboards" tabindex="-1">
                <span>Leaderboards</span><span class="module-tab-count">{{ $leaderboard->count() }}</span>
            </button>
            <button type="button" class="module-tab" id="module-students-tab" role="tab" aria-selected="false" aria-controls="module-students-panel" data-module-tab="students" tabindex="-1">
                <span>Students</span><span class="module-tab-count">{{ $reviewStudents->count() }}</span>
            </button>
        </nav>
    </div>
</div>

<div class="container pb-5 animate-entrance">
    <section class="module-tab-panel is-active" id="module-topics-panel" role="tabpanel" aria-labelledby="module-topics-tab" data-tab-panel="topics">
    <div class="row g-4">
        <!-- Left Side: Module Topics -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-slate-900 h5">List of Topics</h4>
                    <span class="text-secondary small">Organized curriculum materials and lab guides</span>
                </div>
                <button class="btn btn-burgundy-gold fw-bold btn-sm px-4 py-2 rounded-pill shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#addSubtopicModal">
                    <i class="bi bi-plus-lg text-warning"></i> Add Topic
                </button>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($module->subtopics as $subtopic)
                    <div class="subtopic-item d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="subtopic-icon-box">
                                <i class="bi bi-file-earmark-richtext"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-slate-900">{{ $subtopic->title }}</h6>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5 rounded-2 fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-paperclip me-1"></i> Reviewer File
                                </span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center gap-1.5">
                            <a href="{{ asset('storage/' . $subtopic->file_path) }}" target="_blank" class="btn btn-sm btn-outline-dark px-3 rounded-pill fw-semibold d-inline-flex align-items-center gap-1 shadow-2xs">
                                <i class="bi bi-eye"></i> View File
                            </a>
                            
                            <button type="button" class="btn btn-sm btn-light border text-secondary rounded-circle p-2 d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 34px; height: 34px;" data-bs-toggle="modal" data-bs-target="#editSubtopicModal{{ $subtopic->id }}" title="Edit Topic">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <form
                                action="{{ route('admin.subtopics.destroy', $subtopic) }}"
                                method="POST"
                                class="d-inline delete-subtopic-form"
                                data-confirm-title="Delete this topic?"
                                data-confirm-text="The topic and its uploaded reviewer file will be permanently removed."
                                data-confirm-button="Delete topic"
                            >
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger rounded-circle p-2 d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 34px; height: 34px;" title="Delete Topic">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                @empty
                    <div class="card border-0 text-center py-5 bg-white shadow-sm rounded-4" style="border-left: 6px solid var(--lemu-burgundy) !important;">
                        <div class="card-body py-4">
                            <div class="display-6 text-warning mb-3 opacity-75">
                                <i class="bi bi-journal-x"></i>
                            </div>
                            <h5 class="fw-bold text-slate-900 mb-1">No Topics Added Yet</h5>
                            <p class="text-secondary small mb-0">Click the "Add Topic" button above to upload reviewer files and practice materials.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Side: Need to Know Sidebar -->
        <div class="col-lg-4">
            <div class="sidebar-panel p-4">
                <h5 class="fw-bold text-slate-900 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-lightbulb text-warning"></i> Need to know
                </h5>
                <p class="text-secondary small mb-4" style="line-height: 1.6;">
                    Upload reviewer documents, lab sheets, or reference files as topics under this module so students can access them directly from their dashboard.
                </p>
                <div class="p-3 bg-light rounded-3 border-start border-warning border-4">
                    <span class="fw-bold small text-slate-900 d-block mb-1 d-flex align-items-center gap-1.5">
                        <i class="bi bi-info-circle-fill text-dark"></i> Supported File Formats
                    </span>
                    <span class="text-secondary small">PDF, DOC, DOCX, PPT, PPTX, ZIP (Max 75 MB)</span>
                </div>
            </div>
        </div>
    </div>
    </section>

            <section class="module-tab-panel" id="module-leaderboards-panel" role="tabpanel" aria-labelledby="module-leaderboards-tab" data-tab-panel="leaderboards" hidden>
                <div class="module-ranking-card">
                    <div class="align-items-center d-flex flex-wrap gap-2 justify-content-between p-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1 text-slate-900">Quiz Points Leaderboard</h2>
                            <p class="mb-0 small text-secondary">Points are earned from every topic quiz attempt in this module.</p>
                        </div>
                        <div class="module-student-controls">
                            <div>
                                <label for="moduleLeaderboardTopicFilter" class="form-label fw-semibold small mb-1">Filter by Topic</label>
                                <select class="form-select" id="moduleLeaderboardTopicFilter">
                                    <option value="all">All Topics</option>
                                    @foreach($module->subtopics as $topic)
                                        <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <span class="badge rounded-pill bg-warning-subtle border border-warning-subtle text-dark px-3 py-2">{{ $leaderboard->count() }} students</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table module-ranking-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student Name</th>
                                    <th>Average Quiz Score (%)</th>
                                    <th class="text-end">
                                        <span class="align-items-center d-inline-flex justify-content-end">
                                            Total Points
                                            <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                    aria-label="About total points" title="Awards up to 50 pts per attempt based on score percentage (e.g., 8/10 = 40 pts). Points add up across attempts to reward consistent practice.">ⓘ</button>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="moduleLeaderboardRows">
                                @forelse($leaderboard as $student)
                                    @php
                                        $rankStyle = match ($student['rank']) {
                                            1 => 'module-rank-badge--gold',
                                            2 => 'module-rank-badge--silver',
                                            3 => 'module-rank-badge--bronze',
                                            default => '',
                                        };
                                    @endphp
                                    <tr data-leaderboard-row
                                        data-rank="{{ $student['rank'] }}"
                                        data-name="{{ mb_strtolower($student['name']) }}"
                                        data-topic-stats="{{ json_encode($student['topic_stats'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                        @class(['module-rank-divider' => $student['rank'] === 11])
                                        @if($student['rank'] > 15) hidden @endif>
                                        <td><span class="module-rank-badge {{ $rankStyle }} module-tabular" data-rank-badge>{{ $student['rank'] }}</span></td>
                                        <td class="fw-semibold text-slate-900">{{ $student['name'] }}</td>
                                        <td class="module-tabular" data-average-score>{{ $student['average_score'] === null ? 'No attempts' : number_format($student['average_score'], 2).'%' }}</td>
                                        <td class="fw-bold module-tabular text-end" data-total-points>{{ number_format($student['total_points'], 2) }} pts</td>
                                    </tr>
                                @empty
                                    <tr><td class="py-5 text-center text-secondary" colspan="4">No students are enrolled in this module's academic program.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($leaderboard->count() > 15)
                        <div class="border-top p-3 text-center">
                            <button class="btn btn-outline-dark rounded-pill px-4" id="viewAllLeaderboard" type="button" aria-expanded="false">View All</button>
                        </div>
                    @endif
                </div>
            </section>

            <section class="module-tab-panel" id="module-students-panel" role="tabpanel" aria-labelledby="module-students-tab" data-tab-panel="students" hidden>
                <div class="module-ranking-card">
                    <div class="align-items-center d-flex flex-wrap gap-3 justify-content-between p-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1 text-slate-900">Student Study Activity</h2>
                            <p class="mb-0 small text-secondary">Track verified study time, topic completion, and quiz attempts.</p>
                        </div>
                        <div class="module-student-controls">
                            <div>
                                <label for="moduleStudentSearch" class="form-label fw-semibold small mb-1">Search student</label>
                                <input type="search" class="form-control" id="moduleStudentSearch" placeholder="Type a student name..." autocomplete="off">
                            </div>
                            <div>
                                <label for="moduleStudentTopicFilter" class="form-label fw-semibold small mb-1">Filter by Topic</label>
                                <select class="form-select" id="moduleStudentTopicFilter">
                                    <option value="all">All Topics</option>
                                    @foreach($module->subtopics as $topic)
                                        <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="moduleStudentSort" class="form-label fw-semibold small mb-1">Sort by</label>
                                <select class="form-select" id="moduleStudentSort">
                                    <option value="rank">By Rank</option>
                                    <option value="name">A–Z</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table module-ranking-table module-student-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student Name</th>
                                    <th>
                                        Verified Study Time
                                        <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                aria-label="About verified study time" title="Counts active reading time (capped at 30m per topic) once at least 1 quiz attempt is completed on that topic. Inactive tabs and repeated same-spot clicks pause automatically.">ⓘ</button>
                                    </th>
                                    <th>
                                        Topics Completed
                                        <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                aria-label="About topic completion" title="Marked Completed once a student reviews the topic (>= 5m) and scores >= 75% on its quiz.">ⓘ</button>
                                    </th>
                                    <th>Quiz Attempts</th>
                                    <th>Last Active</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="moduleStudentRows">
                                @forelse($reviewStudents as $student)
                                    <tr @class(['module-student-row', 'module-rank-divider' => $student['rank'] === 11])
                                        data-review-student
                                        data-rank="{{ $student['rank'] }}"
                                        data-name="{{ mb_strtolower($student['name']) }}"
                                        data-student-name="{{ $student['name'] }}"
                                        data-topic-stats="{{ json_encode($student['topic_stats'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                        data-activity-url="{{ $student['activity_url'] }}"
                                        data-overview-url="{{ $student['overview_url'] }}"
                                        data-notice-url="{{ $student['notice_url'] }}"
                                        tabindex="0" role="button" aria-label="View study activity for {{ $student['name'] }}"
                                        @if($student['rank'] > 15) hidden @endif>
                                        <td><span class="module-rank-badge module-tabular" data-rank-badge>{{ $student['rank'] }}</span></td>
                                        <td class="fw-semibold text-slate-900">{{ $student['name'] }}</td>
                                        <td class="fw-semibold module-tabular" data-verified-time>
                                            @if($student['verified_seconds'] >= 3600)
                                                {{ intdiv($student['verified_seconds'], 3600) }}h {{ intdiv($student['verified_seconds'] % 3600, 60) }}m
                                            @else
                                                {{ intdiv($student['verified_seconds'], 60) }}m
                                            @endif
                                        </td>
                                        <td class="module-tabular" data-topic-completion>{{ $student['topics_completed'] }} / {{ $student['topic_count'] }} Topics</td>
                                        <td class="module-tabular" data-quiz-attempts>{{ $student['quiz_attempts'] }}</td>
                                        <td class="module-tabular" data-last-active>{{ $student['last_active_at'] ?? 'Never' }}</td>
                                        <td><span class="module-status-pill" data-status-pill>—</span></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="min-height: 44px;" data-student-notice>Message</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="py-5 text-center text-secondary" colspan="8">No students are enrolled in this module's academic program.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($reviewStudents->count() > 15)
                        <div class="border-top p-3 text-center" id="moduleStudentsViewAllWrap">
                            <button class="btn btn-outline-dark rounded-pill px-4" id="viewAllModuleStudents" type="button" aria-expanded="false">View All</button>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="modal fade" id="moduleStudentActivityModal" data-course-title="{{ $module->title }}" tabindex="-1" aria-labelledby="moduleStudentActivityTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1" id="moduleStudentActivityTitle">Student Study Activity</h2>
                            <p class="small text-secondary mb-0" id="moduleActivityContext"></p>
                        </div>
                        <span class="module-status-pill ms-auto me-3" id="moduleActivityStatus"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close study activity"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="module-activity-kpis mb-4" id="moduleActivityKpis">
                            <article class="module-activity-kpi">
                                <h3 class="module-activity-kpi-label">
                                    <span data-kpi-label="0">Verified Study Time</span>
                                    <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                            aria-label="About verified study time" title="Counts active reading time after at least one quiz attempt, capped at 30 minutes per topic.">ⓘ</button>
                                </h3>
                                <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="0">—</p>
                                <div class="progress module-topic-progress mt-2" data-kpi-progress hidden>
                                    <div class="progress-bar bg-danger" data-kpi-progress-bar role="progressbar" aria-label="Topic study time limit"></div>
                                </div>
                            </article>
                            <article class="module-activity-kpi">
                                <h3 class="module-activity-kpi-label">
                                    <span data-kpi-label="1">Topics Completed</span>
                                    <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                            aria-label="About topic completion" title="Completed requires at least 5 minutes of active study and a quiz score of 75% or higher. A selected topic also shows In Progress or Not Started.">ⓘ</button>
                                </h3>
                                <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="1">—</p>
                            </article>
                            <article class="module-activity-kpi">
                                <h3 class="module-activity-kpi-label">
                                    <span data-kpi-label="2">Average Quiz Score</span>
                                    <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                            aria-label="About quiz score" title="Shows the average across quiz attempts. For a selected topic, the card shows both the best and average score.">ⓘ</button>
                                </h3>
                                <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="2">—</p>
                            </article>
                            <article class="module-activity-kpi">
                                <h3 class="module-activity-kpi-label">
                                    <span data-kpi-label="3">Last Active</span>
                                    <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                            aria-label="About last active" title="Most recent review session or quiz attempt. For a selected topic, the card also shows its quiz-attempt count.">ⓘ</button>
                                </h3>
                                <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="3">—</p>
                            </article>
                        </div>
                        <div class="mb-3">
                            <h3 class="h6 fw-bold mb-1" id="moduleChartStageTitle">Monthly Active Study Time</h3>
                            <p class="small text-secondary mb-0" id="moduleChartStageHint">Select a month to view its daily activity.</p>
                        </div>
                        <nav class="module-activity-tabs" id="moduleActivityDrilldownTabs" role="tablist" aria-label="Student activity drill-down">
                            <button type="button" class="module-activity-tab is-active" role="tab" id="moduleMonthsTab" aria-selected="true" aria-controls="moduleActivityChartPanel" data-activity-stage="months">Months</button>
                            <button type="button" class="module-activity-tab" role="tab" id="moduleDaysTab" aria-selected="false" aria-controls="moduleActivityChartPanel" data-activity-stage="days" disabled tabindex="-1">Daily activity</button>
                            <button type="button" class="module-activity-tab" role="tab" id="moduleTopicsTab" aria-selected="false" aria-controls="moduleActivityChartPanel" data-activity-stage="topics" disabled tabindex="-1">Topics</button>
                        </nav>
                        <div class="module-chart-card mb-4">
                            <div id="moduleActivityChartPanel" role="tabpanel" aria-labelledby="moduleMonthsTab" tabindex="0">
                                <p class="module-chart-unit-label" id="moduleChartUnitLabel">Minutes (min)</p>
                                <div class="module-chart-layout">
                                    <div class="module-chart-y-axis" id="moduleChartYAxis" aria-label="Chart scale"></div>
                                    <div class="module-chart-stage">
                                        <div id="moduleMonthlyActivityChart" class="module-chart-bars module-chart-view is-active" role="group" aria-label="Monthly active study time chart. Select a month to view daily activity."></div>
                                        <div id="moduleActivityCalendar" class="module-activity-calendar module-chart-view" aria-label="Daily study activity calendar" hidden></div>
                                        <div id="moduleTopicsActivityChart" class="module-chart-bars module-chart-view" role="img" aria-label="Reviewed time by topic chart" hidden></div>
                                    </div>
                                </div>
                                <p class="small text-secondary mt-2 mb-0" id="moduleCalendarLabel" aria-live="polite"></p>
                            </div>
                        </div>
                        <section class="mb-4" id="moduleTopicBreakdown">
                            <h3 class="h6 fw-bold mb-3">Topic-by-Topic Breakdown</h3>
                            <div class="table-responsive">
                                <table class="table module-ranking-table module-topic-breakdown">
                                    <thead>
                                        <tr>
                                            <th>Topic</th>
                                            <th>Verified Time</th>
                                            <th>Quiz Attempts</th>
                                            <th>Best Score</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="moduleTopicBreakdownRows">
                                        <tr><td class="py-4 text-center text-secondary" colspan="5">Loading topic progress…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        <section class="mb-4" id="moduleTopicAttemptHistory" hidden>
                            <h3 class="h6 fw-bold mb-3">Topic Quiz Attempt History</h3>
                            <div class="table-responsive">
                                <table class="table module-ranking-table module-attempt-history">
                                    <thead>
                                        <tr><th>Attempt #</th><th>Score</th><th>Points Earned</th><th>Date / Time</th></tr>
                                    </thead>
                                    <tbody id="moduleTopicAttemptRows"></tbody>
                                </table>
                            </div>
                        </section>
                        <p class="module-chart-error mt-3 mb-0" id="moduleActivityError" role="alert" hidden></p>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button type="button" class="btn btn-danger rounded-pill px-4" id="moduleActivitySendNotice">Send Notice</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="moduleStudentNoticeModal" data-course-title="{{ $module->title }}" tabindex="-1" aria-labelledby="moduleStudentNoticeTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                    <div class="modal-header border-0 px-4 pt-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1" id="moduleStudentNoticeTitle">Send Student Notice</h2>
                            <p class="small text-secondary mb-0">To <span class="fw-semibold" id="moduleNoticeStudentName"></span> · <span data-notice-course-title>{{ $module->title }}</span></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close notice"></button>
                    </div>
                    <form id="moduleStudentNoticeForm" method="POST">
                        @csrf
                        <div class="modal-body px-4">
                            <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Notice presets">
                                <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-notice-preset="Great study progress!">Great study progress!</button>
                                <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-notice-preset="Reminder to review topics">Reminder to review topics</button>
                                <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-notice-preset="Please catch up on quizzes">Please catch up on quizzes</button>
                            </div>
                            <label class="form-label fw-semibold" for="moduleNoticeMessage">Notice</label>
                            <textarea class="form-control" id="moduleNoticeMessage" name="message" rows="4" maxlength="2000" placeholder="Write a one-way notice..." required></textarea>
                            <div class="form-text">The student can read this notice but cannot reply.</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 px-4 pb-4">
                            <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger rounded-pill px-4">Send Notice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@foreach($module->subtopics as $subtopic)
    <div class="modal fade" id="editSubtopicModal{{ $subtopic->id }}" tabindex="-1" aria-labelledby="editSubtopicModalLabel{{ $subtopic->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-white border-bottom-0 px-4 pt-4" style="background: var(--lemu-burgundy);">
                    <div>
                        <h5 class="modal-title fw-bold text-white" id="editSubtopicModalLabel{{ $subtopic->id }}">Edit Topic</h5>
                        <small class="text-warning-subtle">Modify the topic title or replace its reviewer file.</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form action="{{ route('admin.subtopics.update', $subtopic) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-slate-700">Title</label>
                            <input type="text" name="title" class="form-control bg-light border-0 py-2.5 rounded-3" value="{{ $subtopic->title }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-slate-700">Replace Reviewer File</label>
                            <input type="file" name="file" class="form-control bg-light border-0 py-2.5 rounded-3">
                            <div class="form-text text-muted small mt-1">Leave empty to keep current file. Maximum file size: 75 MB.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-burgundy-gold rounded-pill px-4 fw-semibold shadow-sm">Update Topic</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Add Module Topic Modal -->
<div class="modal fade" id="addSubtopicModal" tabindex="-1" aria-labelledby="addSubtopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-bottom-0 px-4 pt-4" style="background: var(--lemu-burgundy);">
                <div>
                    <h5 class="modal-title fw-bold text-white" id="addSubtopicModalLabel">Add Topic</h5>
                    <small class="text-warning-subtle">Upload a reviewer file for students to study.</small>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <form action="{{ route('admin.modules.subtopics.store', $module) }}" method="POST" enctype="multipart/form-data"
                      data-reviewer-upload>
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-slate-700">Title</label>
                        <input type="text" name="title" class="form-control bg-light border-0 py-2.5 rounded-3" placeholder="e.g., The Wide-Open Security Group" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-slate-700">Reviewer File</label>
                        <input type="file" name="file" class="form-control bg-light border-0 py-2.5 rounded-3" required>
                        <div class="form-text text-muted small mt-1">Maximum file size: 75 MB.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-burgundy-gold rounded-pill px-4 fw-semibold shadow-sm">Upload Reviewer File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="reviewer-upload-progress" class="reviewer-upload-progress" role="dialog" aria-modal="true"
     aria-labelledby="reviewer-upload-progress-title" aria-describedby="reviewer-upload-progress-message" hidden>
    <div class="reviewer-upload-progress-card">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Uploading topic material...</span>
        </div>
        <h2 class="h5 fw-bold mb-2" id="reviewer-upload-progress-title">Uploading topic material</h2>
        <p class="text-secondary small mb-0" id="reviewer-upload-progress-message">Please wait while your reviewer file is uploaded.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabBar = document.getElementById('moduleDetailTabs');
        const tabs = Array.from(tabBar.querySelectorAll('[data-module-tab]'));
        const tabIndicator = tabBar.querySelector('.module-tab-indicator');
        const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

        function positionTabIndicator(tab) {
            tabIndicator.style.width = `${tab.offsetWidth}px`;
            tabIndicator.style.transform = `translateX(${tab.offsetLeft}px)`;
        }

        function activateTab(tab, moveFocus = false) {
            tabs.forEach(function (item) {
                const isActive = item === tab;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', String(isActive));
                item.tabIndex = isActive ? 0 : -1;
            });

            panels.forEach(function (panel) {
                const isActive = panel.dataset.tabPanel === tab.dataset.moduleTab;
                if (isActive) {
                    panel.hidden = false;
                    requestAnimationFrame(() => panel.classList.add('is-active'));
                } else {
                    panel.classList.remove('is-active');
                    window.setTimeout(() => {
                        if (!panel.classList.contains('is-active')) {
                            panel.hidden = true;
                        }
                    }, 210);
                }
            });

            positionTabIndicator(tab);
            if (moveFocus) {
                tab.focus();
            }
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', () => activateTab(tab));
            tab.addEventListener('keydown', function (event) {
                let targetIndex = null;
                if (event.key === 'ArrowRight') {
                    targetIndex = (index + 1) % tabs.length;
                } else if (event.key === 'ArrowLeft') {
                    targetIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === 'Home') {
                    targetIndex = 0;
                } else if (event.key === 'End') {
                    targetIndex = tabs.length - 1;
                }

                if (targetIndex !== null) {
                    event.preventDefault();
                    activateTab(tabs[targetIndex], true);
                }
            });
        });
        positionTabIndicator(tabs[0]);
        window.addEventListener('resize', () => positionTabIndicator(tabs.find((tab) => tab.classList.contains('is-active'))));

        const leaderboardRows = Array.from(document.querySelectorAll('[data-leaderboard-row]'));
        const leaderboardTopicFilter = document.getElementById('moduleLeaderboardTopicFilter');
        const viewAllLeaderboard = document.getElementById('viewAllLeaderboard');
        const studentRowsContainer = document.getElementById('moduleStudentRows');
        const studentRows = Array.from(document.querySelectorAll('[data-review-student]'));
        const studentSearch = document.getElementById('moduleStudentSearch');
        const studentTopicFilter = document.getElementById('moduleStudentTopicFilter');
        const studentSort = document.getElementById('moduleStudentSort');
        const viewAllStudents = document.getElementById('viewAllModuleStudents');
        let showAllLeaderboard = false;
        let showAllStudents = false;

        function parseTopicStats(row) {
            return JSON.parse(row.dataset.topicStats || '{}');
        }

        function getStudentStats(row, topicId) {
            const topics = Object.values(parseTopicStats(row));
            const selectedTopics = topicId === 'all'
                ? topics
                : Object.values(parseTopicStats(row)).filter((topic) => String(topic.id) === topicId);
            const stats = selectedTopics.reduce(function (summary, topic) {
                summary.points += Number(topic.points) || 0;
                summary.score_percentage_sum += Number(topic.score_percentage_sum) || 0;
                summary.quiz_attempts += Number(topic.quiz_attempts) || 0;
                summary.verified_seconds += Number(topic.verified_seconds) || 0;
                summary.study_seconds += Number(topic.study_seconds) || 0;
                summary.topics_completed += topic.completed ? 1 : 0;
                if (topic.last_active_at && (!summary.last_active_at || topic.last_active_at > summary.last_active_at)) {
                    summary.last_active_at = topic.last_active_at;
                }
                return summary;
            }, {
                points: 0,
                score_percentage_sum: 0,
                quiz_attempts: 0,
                verified_seconds: 0,
                study_seconds: 0,
                topics_completed: 0,
                topic_count: selectedTopics.length,
                last_active_at: null,
            });
            stats.average_score = stats.quiz_attempts > 0
                ? stats.score_percentage_sum / stats.quiz_attempts
                : null;
            stats.best_score = selectedTopics.reduce((best, topic) => Math.max(best, Number(topic.best_score) || 0), 0);
            stats.status = selectedTopics.length === 0
                ? 'Not Started'
                : selectedTopics[0].status || 'Not Started';
            stats.completed = stats.topics_completed === stats.topic_count && stats.topic_count > 0;
            return stats;
        }

        function formatStudyDuration(seconds) {
            const minutes = Math.floor(Math.max(0, Number(seconds) || 0) / 60);
            return minutes >= 60 ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : `${minutes}m`;
        }

        function formatLastActive(dateValue) {
            if (!dateValue) {
                return 'Never';
            }
            const activeAt = new Date(dateValue);
            if (Number.isNaN(activeAt.getTime())) {
                return 'Never';
            }
            const elapsedHours = Math.max(0, Math.floor((Date.now() - activeAt.getTime()) / 3600000));
            if (elapsedHours < 1) {
                return `${Math.max(1, Math.floor((Date.now() - activeAt.getTime()) / 60000))}m ago`;
            }
            if (elapsedHours < 24) {
                return `${elapsedHours}h ago`;
            }
            return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(activeAt);
        }

        function getStatus(stats) {
            const activeAt = stats.last_active_at ? new Date(stats.last_active_at) : null;
            if (!activeAt || Number.isNaN(activeAt.getTime()) || Date.now() - activeAt.getTime() > 14 * 86400000) {
                return 'Inactive';
            }
            return stats.completed ? 'On Track' : 'Needs Review';
        }

        function applyStatusPill(pill, status) {
            pill.className = `module-status-pill module-status-pill--${status.toLocaleLowerCase().replaceAll(' ', '-')}`;
            pill.textContent = status;
        }

        function applyRank(row, rank) {
            row.dataset.rank = String(rank);
            row.classList.toggle('module-rank-divider', rank === 11);
            const badge = row.querySelector('[data-rank-badge]');
            badge.textContent = String(rank);
            badge.classList.remove('module-rank-badge--gold', 'module-rank-badge--silver', 'module-rank-badge--bronze');
            if (rank <= 3) {
                badge.classList.add(['module-rank-badge--gold', 'module-rank-badge--silver', 'module-rank-badge--bronze'][rank - 1]);
            }
        }

        function updateLeaderboard() {
            const topicId = leaderboardTopicFilter.value;
            const rankedRows = leaderboardRows.map((row) => ({
                row,
                stats: getStudentStats(row, topicId),
            })).sort((first, second) => second.stats.points - first.stats.points
                || first.row.dataset.name.localeCompare(second.row.dataset.name));

            rankedRows.forEach(function (item, index) {
                const rank = index + 1;
                applyRank(item.row, rank);
                item.row.querySelector('[data-average-score]').textContent = item.stats.average_score === null
                    ? 'No attempts'
                    : `${item.stats.average_score.toFixed(2)}%`;
                item.row.querySelector('[data-total-points]').textContent = `${item.stats.points.toFixed(2)} pts`;
                item.row.hidden = !showAllLeaderboard && rank > 15;
                document.getElementById('moduleLeaderboardRows').appendChild(item.row);
            });

            if (viewAllLeaderboard) {
                viewAllLeaderboard.setAttribute('aria-expanded', String(showAllLeaderboard));
                viewAllLeaderboard.textContent = showAllLeaderboard ? 'Show Top 15' : 'View All';
                viewAllLeaderboard.parentElement.hidden = rankedRows.length <= 15;
            }
        }

        function updateStudentRows() {
            const topicId = studentTopicFilter.value;
            const searchTerm = studentSearch.value.trim().toLocaleLowerCase();
            const rankedRows = studentRows.map((row) => ({
                row,
                stats: getStudentStats(row, topicId),
            })).sort((first, second) => second.stats.verified_seconds - first.stats.verified_seconds
                || first.row.dataset.studentName.localeCompare(second.row.dataset.studentName));
            rankedRows.forEach((item, index) => {
                item.rank = index + 1;
                applyRank(item.row, item.rank);
            });

            const visibleOrder = studentSort.value === 'name'
                ? [...rankedRows].sort((first, second) => first.row.dataset.studentName.localeCompare(second.row.dataset.studentName))
                : rankedRows;

            visibleOrder.forEach(function (item, index) {
                const row = item.row;
                const stats = item.stats;
                const status = getStatus(stats);
                row.querySelector('[data-verified-time]').textContent = formatStudyDuration(stats.verified_seconds);
                row.querySelector('[data-topic-completion]').textContent = topicId === 'all'
                    ? `${stats.topics_completed} / ${stats.topic_count} Topics`
                    : stats.status;
                row.querySelector('[data-quiz-attempts]').textContent = String(stats.quiz_attempts);
                row.querySelector('[data-last-active]').textContent = formatLastActive(stats.last_active_at);
                applyStatusPill(row.querySelector('[data-status-pill]'), status);
                studentRowsContainer.appendChild(row);
                const matches = row.dataset.name.includes(searchTerm);
                row.hidden = !matches || (!searchTerm && !showAllStudents && index >= 15);
            });

            if (viewAllStudents) {
                viewAllStudents.parentElement.hidden = searchTerm.length > 0 || rankedRows.length <= 15;
                viewAllStudents.setAttribute('aria-expanded', String(showAllStudents));
                viewAllStudents.textContent = showAllStudents ? 'Show Top 15' : 'View All';
            }
        }

        leaderboardTopicFilter.addEventListener('change', function () {
            showAllLeaderboard = false;
            updateLeaderboard();
        });
        if (viewAllLeaderboard) {
            viewAllLeaderboard.addEventListener('click', function () {
                showAllLeaderboard = !showAllLeaderboard;
                updateLeaderboard();
            });
        }

        studentSearch.addEventListener('input', function () {
            if (!studentSearch.value.trim()) {
                showAllStudents = false;
            }
            updateStudentRows();
        });
        studentTopicFilter.addEventListener('change', function () {
            showAllStudents = false;
            updateStudentRows();
        });
        studentSort.addEventListener('change', updateStudentRows);
        if (viewAllStudents) {
            viewAllStudents.addEventListener('click', function () {
                showAllStudents = !showAllStudents;
                updateStudentRows();
            });
        }

        updateLeaderboard();
        updateStudentRows();

        const activityModalElement = document.getElementById('moduleStudentActivityModal');
        const activityModal = bootstrap.Modal.getOrCreateInstance(activityModalElement);
        const noticeModalElement = document.getElementById('moduleStudentNoticeModal');
        const noticeModal = bootstrap.Modal.getOrCreateInstance(noticeModalElement);
        const activityContext = document.getElementById('moduleActivityContext');
        const activityStatus = document.getElementById('moduleActivityStatus');
        const activityCourseTitle = activityModalElement.dataset.courseTitle;
        const activityTabs = Array.from(document.querySelectorAll('[data-activity-stage]'));
        const activityChartPanel = document.getElementById('moduleActivityChartPanel');
        const activityChartLayout = document.querySelector('.module-chart-layout');
        const chartStageTitle = document.getElementById('moduleChartStageTitle');
        const chartStageHint = document.getElementById('moduleChartStageHint');
        const chartUnitLabel = document.getElementById('moduleChartUnitLabel');
        const chartYAxis = document.getElementById('moduleChartYAxis');
        const monthlyChart = document.getElementById('moduleMonthlyActivityChart');
        const activityCalendar = document.getElementById('moduleActivityCalendar');
        const calendarLabel = document.getElementById('moduleCalendarLabel');
        const topicsChart = document.getElementById('moduleTopicsActivityChart');
        const activityError = document.getElementById('moduleActivityError');
        const activityNoticeButton = document.getElementById('moduleActivitySendNotice');
        const activityKpiLabels = Array.from(document.querySelectorAll('[data-kpi-label]'));
        const activityKpiValues = Array.from(document.querySelectorAll('[data-kpi-value]'));
        const activityKpiProgress = document.querySelector('[data-kpi-progress]');
        const activityKpiProgressBar = document.querySelector('[data-kpi-progress-bar]');
        const topicBreakdown = document.getElementById('moduleTopicBreakdown');
        const topicBreakdownRows = document.getElementById('moduleTopicBreakdownRows');
        const topicAttemptHistory = document.getElementById('moduleTopicAttemptHistory');
        const topicAttemptRows = document.getElementById('moduleTopicAttemptRows');
        let activeStudentRow = null;
        let activityRequestId = 0;
        let overviewRequestId = 0;
        let selectedActivityMonth = null;
        let selectedActivityDate = null;
        let selectedActivityTopicId = 'all';
        let currentActivityStage = 'months';
        const activityChartScales = { months: null, topics: null };
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            bootstrap.Tooltip.getOrCreateInstance(element, {
                container: 'body',
                customClass: 'module-info-tooltip-popup',
            });
        });

        function formatStudyDuration(seconds) {
            const totalSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
            const minutes = Math.floor(totalSeconds / 60);
            if (minutes === 0 && totalSeconds > 0) {
                return `${totalSeconds}s`;
            }
            if (minutes >= 60) {
                return `${Math.floor(minutes / 60)}h ${minutes % 60}m`;
            }

            return `${minutes}m`;
        }

        function setActivityKpi(index, label, value) {
            activityKpiLabels.find((element) => element.dataset.kpiLabel === String(index)).textContent = label;
            activityKpiValues.find((element) => element.dataset.kpiValue === String(index)).textContent = value;
        }

        function renderTopicBreakdown(topics) {
            topicBreakdownRows.replaceChildren();
            if (topics.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.className = 'py-4 text-center text-secondary';
                cell.colSpan = 5;
                cell.textContent = 'No topics have been added to this module yet.';
                row.appendChild(cell);
                topicBreakdownRows.appendChild(row);
                return;
            }

            topics.forEach(function (topic) {
                const row = document.createElement('tr');
                const values = [
                    topic.title,
                    `${formatStudyDuration(topic.verified_seconds)} / 30m`,
                    String(topic.quiz_attempts),
                    topic.best_score === null ? 'No attempts' : `${Number(topic.best_score).toFixed(2)}%`,
                ];
                values.forEach(function (value) {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    row.appendChild(cell);
                });
                const statusCell = document.createElement('td');
                const statusPill = document.createElement('span');
                applyStatusPill(statusPill, topic.status);
                statusCell.appendChild(statusPill);
                row.appendChild(statusCell);
                topicBreakdownRows.appendChild(row);
            });
        }

        function renderTopicAttempts(attempts) {
            topicAttemptRows.replaceChildren();
            if (attempts.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.className = 'py-4 text-center text-secondary';
                cell.colSpan = 4;
                cell.textContent = 'No quiz attempts have been recorded for this topic.';
                row.appendChild(cell);
                topicAttemptRows.appendChild(row);
                return;
            }

            attempts.forEach(function (attempt) {
                const row = document.createElement('tr');
                const values = [
                    String(attempt.attempt),
                    `${Number(attempt.score).toFixed(2)}%`,
                    `${Number(attempt.points).toFixed(2)} pts`,
                    new Intl.DateTimeFormat(undefined, {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                    }).format(new Date(attempt.created_at)),
                ];
                values.forEach(function (value) {
                    const cell = document.createElement('td');
                    cell.className = 'module-tabular';
                    cell.textContent = value;
                    row.appendChild(cell);
                });
                topicAttemptRows.appendChild(row);
            });
        }

        function renderActivityOverview(data) {
            const topicId = selectedActivityTopicId;
            const selectedTopic = data.selected_topic;
            activityContext.textContent = topicId === 'all'
                ? `${data.student.name} · ${data.module.title} (All Topics)`
                : `${data.student.name} · ${data.module.title} → ${selectedTopic.title}`;
            applyStatusPill(activityStatus, data.status);
            activityKpiProgress.hidden = topicId === 'all';
            if (topicId === 'all') {
                setActivityKpi(0, 'Verified Study Time', formatStudyDuration(data.verified_seconds));
                setActivityKpi(1, 'Topics Completed', `${data.topics_completed} / ${data.topic_count} Topics`);
                setActivityKpi(2, 'Average Quiz Score', data.average_score === null ? 'No attempts' : `${Number(data.average_score).toFixed(2)}%`);
                setActivityKpi(3, 'Last Active', formatLastActive(data.last_active_at));
                topicBreakdown.hidden = false;
                topicAttemptHistory.hidden = true;
                renderTopicBreakdown(data.topics);
                return;
            }

            const topic = selectedTopic;
            setActivityKpi(0, 'Topic Study Time', `${formatStudyDuration(topic.verified_seconds)} / 30m cap`);
            setActivityKpi(1, 'Topic Status', topic.status);
            setActivityKpi(2, 'Quiz Score', topic.quiz_attempts === 0
                ? 'No attempts'
                : `Best: ${Number(topic.best_score).toFixed(2)}% · Avg: ${Number(topic.average_score).toFixed(2)}%`);
            setActivityKpi(3, 'Attempts & Last Active', `${topic.quiz_attempts} · ${formatLastActive(topic.last_active_at)}`);
            activityKpiProgressBar.style.width = `${Math.min(100, (topic.verified_seconds / 1800) * 100)}%`;
            activityKpiProgressBar.setAttribute('aria-valuenow', String(Math.min(1800, topic.verified_seconds)));
            activityKpiProgressBar.setAttribute('aria-valuemax', '1800');
            topicBreakdown.hidden = true;
            topicAttemptHistory.hidden = false;
            renderTopicAttempts(data.attempts);
        }

        function setActivityStage(stage) {
            if (
                (stage === 'days' && !selectedActivityMonth)
                || (stage === 'topics' && (!selectedActivityDate || selectedActivityTopicId !== 'all'))
            ) {
                return;
            }

            currentActivityStage = stage;
            const stageViews = {
                months: monthlyChart,
                days: activityCalendar,
                topics: topicsChart,
            };
            const activeTab = activityTabs.find((tab) => tab.dataset.activityStage === stage);
            activityTabs.find((tab) => tab.dataset.activityStage === 'topics').hidden = selectedActivityTopicId !== 'all';
            activityTabs.forEach(function (tab) {
                const isActive = tab === activeTab;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', String(isActive));
                tab.tabIndex = isActive ? 0 : -1;
                tab.disabled = tab.dataset.activityStage === 'days'
                    ? !selectedActivityMonth
                    : tab.dataset.activityStage === 'topics'
                        && (!selectedActivityDate || selectedActivityTopicId !== 'all');
            });

            Object.entries(stageViews).forEach(function ([viewStage, view]) {
                const isActive = viewStage === stage;
                if (isActive) {
                    view.hidden = false;
                    requestAnimationFrame(() => view.classList.add('is-active'));
                } else {
                    view.classList.remove('is-active');
                    view.hidden = true;
                }
            });

            const isCalendar = stage === 'days';
            activityChartLayout.classList.toggle('is-calendar', isCalendar);
            const stageScale = activityChartScales[stage];
            chartYAxis.hidden = isCalendar || !stageScale;
            chartUnitLabel.hidden = isCalendar || !stageScale;
            calendarLabel.hidden = stage === 'months';
            if (stageScale) {
                renderYAxis(stageScale);
            }
            activityChartPanel.setAttribute('aria-labelledby', activeTab.id);

            const stageContent = {
                months: ['Monthly Active Study Time', 'Select a month to view its daily activity.'],
                days: [`Daily Activity · ${calendarLabel.textContent}`, 'Select a date to view its topic breakdown.'],
                topics: ['Topics Breakdown', `Verified review time on ${selectedActivityDate}.`],
            };
            chartStageTitle.textContent = stageContent[stage][0];
            chartStageHint.textContent = stageContent[stage][1];
        }

        function getChartScale(records, secondsForRecord) {
            const maximumSeconds = Math.max(...records.map(secondsForRecord), 0);
            const isHours = maximumSeconds >= 3600;
            const unitSeconds = isHours ? 3600 : 60;
            const maximumValue = maximumSeconds / unitSeconds;
            const step = isHours
                ? (maximumValue <= 2 ? 0.5 : maximumValue <= 5 ? 1 : 2)
                : maximumValue <= 30 ? 5 : 10;
            const scaleMaximum = Math.max(
                isHours ? step * 2 : 10,
                Math.ceil(maximumValue / step) * step,
            );
            const tickCount = Math.round(scaleMaximum / step);
            const ticks = Array.from({ length: tickCount + 1 }, (_, index) =>
                Number((scaleMaximum - index * step).toFixed(2)),
            );

            return {
                unitSeconds,
                scaleMaximum,
                ticks,
                unitLabel: isHours ? 'Hours (h)' : 'Minutes (min)',
            };
        }

        function renderYAxis(scale) {
            chartUnitLabel.textContent = scale.unitLabel;
            chartYAxis.replaceChildren();
            scale.ticks.forEach(function (tick) {
                const label = document.createElement('span');
                label.textContent = String(tick);
                chartYAxis.appendChild(label);
            });
        }

        function renderBars(container, records, nameForRecord, secondsForRecord, onSelectRecord = null, selectedKey = null, keyForRecord = null) {
            container.replaceChildren();
            const scale = getChartScale(records, secondsForRecord);
            records.forEach(function (record, index) {
                const seconds = Number(secondsForRecord(record)) || 0;
                const bar = document.createElement(onSelectRecord ? 'button' : 'div');
                bar.className = onSelectRecord ? 'module-chart-bar module-chart-bar--interactive' : 'module-chart-bar';
                if (onSelectRecord) {
                    bar.type = 'button';
                    const isSelected = keyForRecord(record) === selectedKey;
                    bar.classList.toggle('is-selected', isSelected);
                    bar.setAttribute('aria-pressed', String(isSelected));
                    bar.setAttribute('aria-label', `${nameForRecord(record)}: ${formatStudyDuration(seconds)} reviewed. Select month.`);
                    bar.addEventListener('click', () => onSelectRecord(record));
                }

                const value = document.createElement('span');
                value.className = 'module-chart-value';
                value.textContent = formatStudyDuration(seconds);

                const plot = document.createElement('div');
                plot.className = 'module-chart-plot';
                scale.ticks.slice(1, -1).forEach(function (_, tickIndex) {
                    const gridline = document.createElement('span');
                    gridline.className = 'module-chart-gridline';
                    gridline.style.top = `${((tickIndex + 1) / (scale.ticks.length - 1)) * 100}%`;
                    plot.appendChild(gridline);
                });
                const fill = document.createElement('div');
                fill.className = 'module-chart-fill';
                fill.style.height = `${Math.max(0, (seconds / scale.unitSeconds / scale.scaleMaximum) * 100)}%`;
                fill.style.transitionDelay = `${index * 40}ms`;
                fill.title = `${nameForRecord(record)}: ${formatStudyDuration(seconds)}`;
                fill.setAttribute('aria-label', fill.title);
                plot.appendChild(fill);

                const label = document.createElement('span');
                label.className = 'module-chart-label module-tabular';
                label.textContent = nameForRecord(record);
                bar.append(value, plot, label);
                container.appendChild(bar);
                requestAnimationFrame(() => requestAnimationFrame(() => fill.classList.add('is-animated')));
            });

            if (records.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'small text-secondary m-auto';
                empty.textContent = 'No verified study time recorded yet.';
                container.appendChild(empty);
            }

            return scale;
        }

        function renderCalendar(calendar, selectedDate, onSelectDate) {
            activityCalendar.replaceChildren();
            const weekdays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
            weekdays.forEach(function (weekday) {
                const label = document.createElement('span');
                label.className = 'module-chart-label fw-bold mt-0';
                label.textContent = weekday;
                activityCalendar.appendChild(label);
            });

            for (let index = 0; index < calendar.first_weekday; index += 1) {
                const spacer = document.createElement('span');
                spacer.className = 'module-calendar-cell module-calendar-cell--empty';
                spacer.setAttribute('aria-hidden', 'true');
                activityCalendar.appendChild(spacer);
            }

            calendar.days.forEach(function (day) {
                const cell = document.createElement('button');
                const activeSeconds = Number(day.active_seconds) || 0;
                const level = activeSeconds === 0 ? 0 : activeSeconds < 600 ? 1 : activeSeconds < 1800 ? 2 : activeSeconds < 3600 ? 3 : 4;
                cell.type = 'button';
                cell.className = `module-calendar-cell module-tabular${day.date === selectedDate ? ' module-calendar-cell--selected' : ''}`;
                cell.dataset.level = String(level);
                cell.textContent = day.day;
                cell.title = `${day.date}: ${formatStudyDuration(activeSeconds)} reviewed`;
                cell.setAttribute('aria-label', `${day.date}: ${formatStudyDuration(activeSeconds)} reviewed. View topic breakdown.`);
                cell.setAttribute('aria-pressed', String(day.date === selectedDate));
                cell.addEventListener('click', () => onSelectDate(day.date));
                activityCalendar.appendChild(cell);
            });
            calendarLabel.textContent = calendar.label;
        }

        function selectActivityMonth(month) {
            selectedActivityMonth = month;
            selectedActivityDate = null;
            activityChartScales.topics = null;
            activityCalendar.replaceChildren();
            const [year, monthNumber] = month.split('-').map(Number);
            calendarLabel.textContent = new Date(year, monthNumber - 1, 1)
                .toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
            topicsChart.replaceChildren();
            setActivityStage('days');
            loadActivity(month);
        }

        function selectActivityDate(date) {
            selectedActivityDate = date;
            if (selectedActivityTopicId === 'all') {
                activityChartScales.topics = null;
                topicsChart.replaceChildren();
                setActivityStage('topics');
            } else {
                setActivityStage('days');
            }
            loadActivity(selectedActivityMonth, date);
        }

        function renderActivity(data, month, date) {
            activityChartScales.months = renderBars(
                monthlyChart,
                data.months,
                (record) => record.label,
                (record) => record.active_seconds,
                (record) => selectActivityMonth(record.month),
                month === 'all' ? null : month,
                (record) => record.month,
            );

            if (month === 'all') {
                activityChartScales.topics = null;
                setActivityStage('months');
                return;
            }

            renderCalendar(data.calendar, date, selectActivityDate);

            if (date && selectedActivityTopicId === 'all') {
                activityChartScales.topics = renderBars(
                    topicsChart,
                    data.topics,
                    (topic) => topic.title,
                    (topic) => topic.active_seconds,
                );
                setActivityStage('topics');
                return;
            }

            activityChartScales.topics = null;
            topicsChart.replaceChildren();
            setActivityStage('days');
        }

        async function loadActivity(month = 'all', date = null) {
            if (!activeStudentRow) {
                return;
            }
            const requestId = ++activityRequestId;
            activityError.hidden = true;
            if (month === 'all') {
                monthlyChart.replaceChildren();
                activityCalendar.replaceChildren();
                topicsChart.replaceChildren();
                activityChartScales.months = null;
                activityChartScales.topics = null;
                selectedActivityMonth = null;
                selectedActivityDate = null;
                setActivityStage('months');
            }

            try {
                const url = new URL(activeStudentRow.dataset.activityUrl, window.location.origin);
                if (month !== 'all') {
                    url.searchParams.set('month', month);
                }
                if (date) {
                    url.searchParams.set('date', date);
                }
                if (selectedActivityTopicId !== 'all') {
                    url.searchParams.set('subtopic_id', selectedActivityTopicId);
                }
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error('Student study activity could not be loaded.');
                }
                const data = await response.json();
                if (requestId !== activityRequestId) {
                    return;
                }

                selectedActivityMonth = month === 'all' ? null : month;
                selectedActivityDate = date;
                renderActivity(data, month, date);
            } catch (error) {
                if (requestId === activityRequestId) {
                    activityError.textContent = error.message;
                    activityError.hidden = false;
                }
            }
        }

        function suggestedNotice(row, topicId) {
            const stats = getStudentStats(row, topicId);
            if (topicId !== 'all') {
                const topic = Object.values(parseTopicStats(row)).find((item) => String(item.id) === topicId);
                if (stats.status === 'Completed') {
                    return `Great work completing ${topic.title}! Keep up the excellent study progress.`;
                }
                if (stats.status === 'In Progress') {
                    return `Please continue reviewing ${topic.title} and complete its quiz when you are ready.`;
                }
                return `Please start reviewing ${topic.title} and complete its topic quiz.`;
            }

            const status = getStatus(stats);
            if (status === 'On Track') {
                return 'Great study progress! Keep reviewing your module topics and practicing with quizzes.';
            }
            if (status === 'Inactive') {
                return 'Please resume reviewing your module topics and complete the related quizzes.';
            }
            return 'Reminder to review your module topics and complete the related quizzes.';
        }

        function openNotice(row, topicId = 'all') {
            document.getElementById('moduleStudentNoticeForm').action = row.dataset.noticeUrl;
            document.getElementById('moduleNoticeStudentName').textContent = row.dataset.studentName;
            document.getElementById('moduleNoticeMessage').value = suggestedNotice(row, topicId);
            noticeModal.show();
        }

        async function loadStudentOverview(row, topicId) {
            const requestId = ++overviewRequestId;
            const url = new URL(row.dataset.overviewUrl, window.location.origin);
            if (topicId !== 'all') {
                url.searchParams.set('subtopic_id', topicId);
            }

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error('Student module progress could not be loaded.');
                }
                const data = await response.json();
                if (requestId === overviewRequestId && activeStudentRow === row) {
                    renderActivityOverview(data);
                }
            } catch (error) {
                if (requestId === overviewRequestId && activeStudentRow === row) {
                    activityError.textContent = error.message;
                    activityError.hidden = false;
                }
            }
        }

        studentRowsContainer.addEventListener('click', function (event) {
            const row = event.target.closest('[data-review-student]');
            if (!row) {
                return;
            }
            if (event.target.closest('[data-student-notice]')) {
                event.stopPropagation();
                openNotice(row, studentTopicFilter.value);
                return;
            }

            activeStudentRow = row;
            selectedActivityTopicId = studentTopicFilter.value;
            activityContext.textContent = `${row.dataset.studentName} · ${activityCourseTitle} (${selectedActivityTopicId === 'all' ? 'All Topics' : 'Selected Topic'})`;
            activityNoticeButton.dataset.noticeUrl = row.dataset.noticeUrl;
            activityNoticeButton.dataset.studentName = row.dataset.studentName;
            activityNoticeButton.dataset.topicId = selectedActivityTopicId;
            selectedActivityMonth = null;
            selectedActivityDate = null;
            activityChartScales.months = null;
            activityChartScales.topics = null;
            activityCalendar.replaceChildren();
            topicsChart.replaceChildren();
            setActivityStage('months');
            topicBreakdown.hidden = selectedActivityTopicId !== 'all';
            topicAttemptHistory.hidden = selectedActivityTopicId === 'all';
            activityModal.show();
            loadStudentOverview(row, selectedActivityTopicId);
            loadActivity('all');
        });

        studentRowsContainer.addEventListener('keydown', function (event) {
            const row = event.target.closest('[data-review-student]');
            if (row && event.target === row && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                row.click();
            }
        });

        activityTabs.forEach(function (tab) {
            tab.addEventListener('click', () => setActivityStage(tab.dataset.activityStage));
            tab.addEventListener('keydown', function (event) {
                const enabledTabs = activityTabs.filter((item) => !item.disabled);
                const currentIndex = enabledTabs.indexOf(tab);
                let targetIndex = null;
                if (event.key === 'ArrowRight') {
                    targetIndex = (currentIndex + 1) % enabledTabs.length;
                } else if (event.key === 'ArrowLeft') {
                    targetIndex = (currentIndex - 1 + enabledTabs.length) % enabledTabs.length;
                } else if (event.key === 'Home') {
                    targetIndex = 0;
                } else if (event.key === 'End') {
                    targetIndex = enabledTabs.length - 1;
                }

                if (targetIndex !== null) {
                    event.preventDefault();
                    enabledTabs[targetIndex].focus();
                    setActivityStage(enabledTabs[targetIndex].dataset.activityStage);
                }
            });
        });

        activityNoticeButton.addEventListener('click', function () {
            const row = activeStudentRow;
            const topicId = this.dataset.topicId || 'all';
            if (!row) {
                return;
            }
            activityModalElement.addEventListener('hidden.bs.modal', function openNoticeAfterActivity() {
                activityModalElement.removeEventListener('hidden.bs.modal', openNoticeAfterActivity);
                openNotice(row, topicId);
            });
            activityModal.hide();
        });

        noticeModalElement.querySelectorAll('[data-notice-preset]').forEach(function (preset) {
            preset.addEventListener('click', function () {
                const textarea = document.getElementById('moduleNoticeMessage');
                textarea.value = preset.dataset.noticePreset;
                textarea.focus();
            });
        });

        activityModalElement.addEventListener('hidden.bs.modal', () => {
            activeStudentRow = null;
            activityRequestId += 1;
            overviewRequestId += 1;
            selectedActivityMonth = null;
            selectedActivityDate = null;
            selectedActivityTopicId = 'all';
            monthlyChart.replaceChildren();
            activityCalendar.replaceChildren();
            topicsChart.replaceChildren();
            activityChartScales.months = null;
            activityChartScales.topics = null;
            topicBreakdown.hidden = false;
            topicAttemptHistory.hidden = true;
            setActivityStage('months');
        });

        const uploadForm = document.querySelector('[data-reviewer-upload]');
        const uploadProgress = document.getElementById('reviewer-upload-progress');

        if (uploadForm && uploadProgress) {
            uploadForm.addEventListener('submit', function (event) {
                if (uploadForm.dataset.uploading === 'true') {
                    event.preventDefault();

                    return;
                }

                uploadForm.dataset.uploading = 'true';
                uploadForm.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    button.disabled = true;
                });
                uploadProgress.hidden = false;
            });
        }
    });
</script>

<style>
    .reviewer-upload-progress {
        align-items: center;
        background: rgba(15, 23, 42, .55);
        display: flex;
        inset: 0;
        justify-content: center;
        padding: 1rem;
        position: fixed;
        z-index: 2000;
    }

    .reviewer-upload-progress[hidden] {
        display: none;
    }

    .reviewer-upload-progress-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, .25);
        max-width: 24rem;
        padding: 2rem;
        text-align: center;
        width: 100%;
    }
</style>
@endsection