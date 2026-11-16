@extends('layouts.app')

@section('content')
<style>
    :root {
        --subtle-border: #E2E8F0;
        --surface-card: #FFFFFF;
        --surface-subtle: #F8FAFC;
        --slate-900: #0F172A;
        --slate-800: #1E293B;
        --slate-600: #475569;
        --slate-400: #94A3B8;
        --indigo-primary: #4F46E5;
        --indigo-hover: #4338CA;
    }

    .hover-lift {
        transition: transform 160ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 160ms cubic-bezier(0.16, 1, 0.3, 1), border-color 160ms ease;
    }
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.03) !important;
        border-color: #CBD5E1 !important;
    }

    .remediation-card {
        transition: all 180ms ease;
        border: 1px solid #FFE4E6;
        background: #FFFFFF;
    }
    .remediation-card:hover {
        border-color: #FDA4AF;
        box-shadow: 0 4px 14px 0 rgba(225, 29, 72, 0.08);
    }

    .btn-action-primary {
        background-color: var(--indigo-primary);
        border: 1px solid var(--indigo-primary);
        color: #FFFFFF;
        transition: all 140ms ease;
    }
    .btn-action-primary:hover, .btn-action-primary:focus-visible {
        background-color: var(--indigo-hover);
        border-color: var(--indigo-hover);
        color: #FFFFFF;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.22);
    }

    .table-test-row {
        transition: background-color 120ms ease;
    }
    .table-test-row:hover {
        background-color: #F8FAFC;
    }

    .badge-soft-indigo {
        background-color: #EEF2FF;
        color: #4338CA;
    }
    .badge-soft-amber {
        background-color: #FEF3C7;
        color: #92400E;
    }
    .badge-soft-rose {
        background-color: #FFE4E6;
        color: #9F1239;
    }
</style>

<div class="container-xl py-4 py-lg-5">
    @php
        $weakAreas = \App\Models\WeakArea::where('user_id', auth()->id())->get();
        $totalTests = count($tests);
        $generalTestsCount = collect($tests)->where('is_general', true)->count();
    @endphp

    <!-- Dashboard Top Bar / Breadcrumb Header -->
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge badge-soft-indigo px-2.5 py-1 rounded-pill fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.06em;">
                    Adaptive Exam Engine
                </span>
                <span class="text-muted" style="font-size: 0.8rem;">•</span>
                <span class="text-muted small fw-medium">Student Performance Hub</span>
            </div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--slate-900); letter-spacing: -0.025em;">
                Assessments &amp; Adaptive Learning
            </h1>
            <p class="text-muted small mb-0">Master target competencies, review flagged questions, and simulate timed board exams.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.learn.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 fw-medium shadow-sm bg-white" style="border-color: #CBD5E1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                <span>Back to Learn</span>
            </a>
        </div>
    </header>

    <!-- Diagnostic KPI Bar -->
    <section class="row g-3 mb-4" aria-label="Summary Statistics">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 hover-lift" style="border-color: var(--subtle-border) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Needs Remediation</span>
                    <div class="rounded-2 p-2 d-flex align-items-center justify-content-center" style="background-color: #FFF1F2; color: #E11D48;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>
                        </svg>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0" style="color: var(--slate-900);">{{ $weakAreas->count() }}</h3>
                    <span class="text-muted small">topics</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 hover-lift" style="border-color: var(--subtle-border) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Available Tests</span>
                    <div class="rounded-2 p-2 d-flex align-items-center justify-content-center" style="background-color: #EEF2FF; color: #4F46E5;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/>
                        </svg>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0" style="color: var(--slate-900);">{{ $totalTests }}</h3>
                    <span class="text-muted small">{{ $generalTestsCount }} general</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 hover-lift" style="border-color: var(--subtle-border) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Mastery Benchmark</span>
                    <div class="rounded-2 p-2 d-flex align-items-center justify-content-center" style="background-color: #ECFDF5; color: #059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                        </svg>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold text-success mb-0">85%</h3>
                    <span class="text-muted small">pass req.</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 hover-lift" style="border-color: var(--subtle-border) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Target Time Window</span>
                    <div class="rounded-2 p-2 d-flex align-items-center justify-content-center" style="background-color: #F8FAFC; color: #64748B;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0" style="color: var(--slate-900);">2.0</h3>
                    <span class="text-muted small">hours max retake</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Weak Areas / Remediation Section -->
    @if($weakAreas->isNotEmpty())
        <section class="card border-0 shadow-sm rounded-3 mb-5 overflow-hidden" aria-labelledby="weak-areas-title" style="border: 1px solid #FECDD3 !important;">
            <div class="card-header border-0 py-3.5 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3" style="background-color: #FFF1F2; border-bottom: 1px solid #FFE4E6 !important;">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="d-inline-flex p-2 rounded-2" style="background-color: #FFE4E6; color: #E11D48;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>
                        </svg>
                    </span>
                    <div>
                        <h2 id="weak-areas-title" class="h6 fw-bold mb-0 text-danger-emphasis">
                            Identified Weak Areas
                        </h2>
                        <span class="text-muted small">Diagnostic system identified topics below target proficiency</span>
                    </div>
                </div>
                <div class="badge badge-soft-rose px-3 py-1.5 rounded-pill fw-semibold border border-danger-subtle" style="font-size: 0.78rem;">
                    Requires 85%+ on 2-Hour Retake
                </div>
            </div>

            <div class="card-body p-3 p-md-4 bg-white">
                <div class="row g-3">
                    @foreach($weakAreas as $area)
                        <div class="col-12">
                            <div class="remediation-card rounded-3 p-3 p-md-3.5 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="rounded-circle p-1.5 mt-0.5 flex-shrink-0 d-flex align-items-center justify-content-center" style="background-color: #FEE2E2; color: #DC2626; width: 28px; height: 28px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="h6 fw-semibold mb-1" style="color: var(--slate-900);">
                                            {{ $area->question_summary }}
                                        </h3>
                                        <div class="text-muted small">
                                            Targeted retake recommended to reinforce conceptual foundation.
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto justify-content-start justify-content-md-end flex-shrink-0">
                                    <a href="{{ route('student.tests.generate-targeted', ['topic' => urlencode($area->question_summary), 'question_type' => 'multiple_choice']) }}"
                                       data-ai-loading
                                       data-ai-loading-title="Preparing your targeted test"
                                       data-ai-loading-message="Setting up a multiple-choice test for this weak topic. The AI will generate questions from your reviewer materials next."
                                       class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5 px-3 py-2 rounded-2 fw-medium shadow-sm bg-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/>
                                        </svg>
                                        <span>Multiple-Choice</span>
                                    </a>

                                    <a href="{{ route('student.tests.generate-targeted', ['topic' => urlencode($area->question_summary), 'question_type' => 'identification']) }}"
                                       data-ai-loading
                                       data-ai-loading-title="Preparing your targeted test"
                                       data-ai-loading-message="Setting up an identification test for this weak topic. The AI will generate questions from your reviewer materials next."
                                       class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5 px-3 py-2 rounded-2 fw-medium shadow-sm bg-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                        <span>Identification</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Available / Recent Tests Section -->
    <section class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white" aria-labelledby="tests-section-title" style="border: 1px solid var(--subtle-border) !important;">
        <div class="card-header bg-white border-bottom py-3.5 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-color: var(--subtle-border) !important;">
            <div>
                <h2 id="tests-section-title" class="h6 fw-bold mb-0" style="color: var(--slate-900);">
                    Available / Recent Tests
                </h2>
                <span class="text-muted small">Choose an exam session to measure current retention</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary rounded-pill px-3 py-1.5 fw-medium border">
                    {{ $totalTests }} Active {{ Str::plural('Test', $totalTests) }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background-color: var(--surface-subtle); border-bottom: 1px solid var(--subtle-border);">
                    <tr>
                        <th scope="col" class="py-3 px-4 text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Assessment Details</th>
                        <th scope="col" class="py-3 px-3 text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Test Mode</th>
                        <th scope="col" class="py-3 px-3 text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Time Limit</th>
                        <th scope="col" class="py-3 px-4 text-end text-uppercase fw-semibold text-muted small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tests as $test)
                        <tr class="table-test-row">
                            <td class="py-3.5 px-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 p-2.5 d-none d-sm-flex align-items-center justify-content-center flex-shrink-0" style="background-color: #F1F5F9; color: #475569; width: 40px; height: 40px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="color: var(--slate-900); font-size: 0.95rem;">
                                            {{ $test->title }}
                                        </div>
                                        <div class="text-muted small mt-0.5 d-flex align-items-center gap-2">
                                            <span class="d-inline-flex align-items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                                </svg>
                                                {{ $test->time_limit ? $test->time_limit . ' mins' : 'Untimed' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                @if($test->is_general)
                                    <span class="badge badge-soft-indigo rounded-pill fw-medium px-2.5 py-1.5 border border-indigo-subtle" style="font-size: 0.76rem;">
                                        General (30 Items)
                                    </span>
                                @else
                                    <span class="badge badge-soft-amber rounded-pill fw-medium px-2.5 py-1.5 border border-warning-subtle" style="font-size: 0.76rem;">
                                        Targeted: {{ $test->topic }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-3">
                                <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span>{{ $test->time_limit ? $test->time_limit . ' Minutes' : 'No Time Limit' }}</span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-end">
                                <a href="{{ route('student.tests.show', $test->id) }}" 
                                   class="btn btn-sm btn-action-primary d-inline-flex align-items-center gap-1.5 px-3.5 py-2 rounded-2 fw-semibold shadow-sm">
                                    <span>Take Test</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-5 text-center">
                                <div class="d-flex flex-column align-items-center justify-content-center p-4">
                                    <div class="rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="background-color: var(--surface-subtle); color: var(--slate-400); width: 64px; height: 64px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
                                        </svg>
                                    </div>
                                    <h6 class="fw-bold mb-1" style="color: var(--slate-900);">No Tests Available Yet</h6>
                                    <p class="text-muted small mb-0" style="max-width: 380px;">
                                        Complete your assessment to begin. Your diagnostic evaluations and targeted review sessions will populate here.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection