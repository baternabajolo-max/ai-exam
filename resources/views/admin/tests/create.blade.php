@extends('layouts.app')

@section('content')
@php
    $isFacultyRequest = auth()->user()->role === 'admin';
@endphp
<style>
    .exam-builder__hero {
        background:
            radial-gradient(circle at 88% 25%, rgb(255 255 255 / 18%) 0 5rem, transparent 5.1rem),
            linear-gradient(125deg, #102a56 0%, #2948a3 58%, #6953cf 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.5rem;
        box-shadow: 0 1.25rem 2.5rem rgb(35 54 115 / 24%), inset 0 1px rgb(255 255 255 / 18%);
        color: #fff;
        isolation: isolate;
        overflow: hidden;
        padding: clamp(1.5rem, 4vw, 2.75rem);
        position: relative;
        transform-style: preserve-3d;
    }

    .exam-builder__hero::after {
        background: linear-gradient(145deg, rgb(255 255 255 / 14%), transparent 58%);
        content: "";
        inset: 0;
        pointer-events: none;
        position: absolute;
        z-index: -1;
    }

    .exam-builder__hero-icon {
        align-items: center;
        animation: exam-builder-float 4s ease-in-out infinite;
        background: rgb(255 255 255 / 13%);
        border: 1px solid rgb(255 255 255 / 24%);
        border-radius: 1.25rem;
        box-shadow: 0 1rem 2rem rgb(10 22 58 / 22%), inset 0 1px rgb(255 255 255 / 24%);
        display: inline-flex;
        font-size: 2rem;
        height: 4.5rem;
        justify-content: center;
        transform: translateZ(24px) rotate(-5deg);
        width: 4.5rem;
    }

    .exam-builder__eyebrow {
        color: #c7d2fe;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .exam-builder__program-card,
    .exam-builder__question-card,
    .exam-builder__bank-summary {
        border: 1px solid #e2e8f0;
        border-radius: 1.15rem;
        box-shadow: 0 .75rem 1.75rem rgb(15 23 42 / 7%), inset 0 1px #fff;
    }

    .exam-builder__program-card {
        background: linear-gradient(145deg, #fff, #f8faff);
    }

    .exam-builder__question-card {
        background: linear-gradient(145deg, #fff, #fbfcff);
        cursor: pointer;
        min-width: 0;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .exam-builder__question-text {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        overflow: hidden;
        overflow-wrap: anywhere;
    }

    .exam-builder__question-copy {
        min-width: 0;
    }

    .exam-builder__question-card:hover {
        border-color: #a5b4fc;
        box-shadow: 0 1rem 1.75rem rgb(79 70 229 / 12%), inset 0 1px #fff;
        transform: perspective(900px) translateY(-3px) rotateX(1deg);
    }

    .exam-builder__question-card:has(.js-exam-question:checked) {
        background: linear-gradient(145deg, #eef2ff, #fff);
        border-color: #818cf8;
    }

    .exam-builder__bank-summary {
        background: linear-gradient(145deg, #fff, #f8faff);
    }

    .exam-builder__bank-summary-icon {
        align-items: center;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: .9rem;
        color: #4338ca;
        display: inline-flex;
        flex: 0 0 auto;
        height: 2.75rem;
        justify-content: center;
        width: 2.75rem;
    }

    .exam-builder__create-button {
        box-shadow: 0 .55rem 1.1rem rgb(79 70 229 / 22%);
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .exam-builder__create-button:hover:not(:disabled) {
        box-shadow: 0 .8rem 1.4rem rgb(79 70 229 / 30%);
        transform: translateY(-2px);
    }

    .exam-builder__modal {
        max-height: min(90dvh, 58rem);
        width: 100%;
    }

    .exam-builder__modal-dialog {
        max-width: min(62rem, calc(100vw - 2rem));
    }

    .exam-builder__modal form {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }

    .exam-builder__modal .modal-header,
    .exam-builder__modal .modal-footer {
        background: var(--bs-modal-bg);
        flex: 0 0 auto;
        position: relative;
        z-index: 1;
    }

    .exam-builder__modal .modal-header {
        border-bottom: 1px solid #e8eef5;
    }

    .exam-builder__modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding-block: 1.25rem;
        scroll-padding-block: 1rem;
        scrollbar-gutter: stable;
    }

    .exam-builder__modal .form-label {
        font-size: .9rem;
        font-weight: 600;
    }

    .exam-builder__modal .form-control,
    .exam-builder__modal .form-select {
        font-size: .95rem;
        min-height: 2.85rem;
    }

    .exam-builder__modal textarea.form-control {
        min-height: auto;
    }

    .exam-builder__modal .form-control:focus,
    .exam-builder__modal .form-select:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 .22rem rgb(99 102 241 / 15%);
    }

    .exam-builder__modal .modal-footer {
        border-top: 1px solid var(--bs-modal-header-border-color);
        box-shadow: 0 -.5rem 1.25rem rgb(15 23 42 / 6%);
        justify-content: flex-end;
    }

    .exam-builder__details-heading {
        align-items: center;
        display: flex;
        gap: .55rem;
    }

    .exam-builder__details-heading i {
        align-items: center;
        background: #eef2ff;
        border-radius: .65rem;
        color: #4338ca;
        display: inline-flex;
        height: 2rem;
        justify-content: center;
        width: 2rem;
    }

    .exam-builder__pool-summary {
        display: grid;
        gap: .75rem;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.3fr) minmax(0, .95fr);
    }

    .exam-builder__pool-metric {
        background: var(--bs-body-bg);
        border: 1px solid #e2e8f0;
        border-radius: .9rem;
        box-shadow: 0 .3rem .8rem rgb(15 23 42 / 5%);
        display: flex;
        flex-direction: column;
        gap: .5rem;
        min-width: 0;
        padding: .9rem 1rem;
    }

    .exam-builder__pool-metric--selected {
        background: linear-gradient(145deg, #eef2ff, #fff);
        border-color: #c7d2fe;
    }

    .exam-builder__pool-label {
        color: #64748b;
        font-size: .8rem;
        font-weight: 650;
    }

    .exam-builder__pool-value {
        color: #0f172a;
        font-size: 1.1rem;
        font-variant-numeric: tabular-nums;
        font-weight: 750;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .exam-builder__pool-detail {
        color: #64748b;
        font-size: .78rem;
    }

    .exam-builder__pool-breakdown {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }

    .exam-builder__pool-difficulty {
        align-items: center;
        border: 1px solid transparent;
        border-radius: .55rem;
        display: inline-flex;
        font-size: .78rem;
        font-weight: 600;
        gap: .3rem;
        line-height: 1.2;
        min-height: 2rem;
        padding: .35rem .55rem;
        white-space: nowrap;
    }

    .exam-builder__pool-difficulty--easy {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .exam-builder__pool-difficulty--moderate {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    .exam-builder__pool-difficulty--hard {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .exam-builder__auto-pick-button {
        min-height: 2.85rem;
        white-space: nowrap;
    }

    .exam-builder__selection-feedback {
        min-height: 1.5rem;
    }

    .exam-builder__selection-feedback p {
        max-width: 70ch;
    }

    .exam-builder__clear-selection {
        min-height: 2.75rem;
        white-space: nowrap;
    }

    .exam-builder__question-picker {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 21rem), 1fr));
        border-radius: .9rem;
        max-height: 17rem;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: .75rem;
        scrollbar-gutter: stable;
    }

    .exam-builder__question-picker .exam-builder__question-card {
        box-shadow: none;
        min-height: 100%;
        padding: 1rem !important;
    }

    .exam-builder__question-picker .exam-builder__question-card:hover {
        transform: none;
    }

    .exam-builder__modal .form-switch {
        min-height: 4.1rem;
    }

    @media (max-width: 575.98px) {
        .exam-builder__modal-dialog {
            margin: .5rem;
            max-width: none;
        }

        .exam-builder__modal {
            max-height: calc(100dvh - 1rem);
        }

        .exam-builder__modal .modal-header,
        .exam-builder__modal .modal-body,
        .exam-builder__modal .modal-footer {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .exam-builder__modal .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .exam-builder__modal .modal-footer .btn {
            width: 100%;
        }

        .exam-builder__pool-metric {
            padding: .75rem;
        }
    }

    @media (max-width: 767.98px) {
        .exam-builder__pool-summary {
            grid-template-columns: 1fr;
        }
    }

    @keyframes exam-builder-float {
        0%, 100% { transform: translate3d(0, 0, 24px) rotate(-5deg); }
        50% { transform: translate3d(0, -7px, 24px) rotate(-2deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        .exam-builder__hero-icon { animation: none; }
        .exam-builder__question-card,
        .exam-builder__create-button { transition: none; }
    }

    html[data-bs-theme="dark"] .exam-builder__program-card,
    html[data-bs-theme="dark"] .exam-builder__question-card,
    html[data-bs-theme="dark"] .exam-builder__bank-summary {
        background: linear-gradient(145deg, #1e293b, #172033);
        border-color: #334155;
        box-shadow: 0 .75rem 1.75rem rgb(0 0 0 / 18%), inset 0 1px rgb(255 255 255 / 4%);
    }

    html[data-bs-theme="dark"] .exam-builder__question-card:has(.js-exam-question:checked) {
        background: linear-gradient(145deg, #262f57, #1e293b);
        border-color: #818cf8;
    }

    html[data-bs-theme="dark"] .exam-builder__modal .modal-header {
        border-bottom-color: #334155;
    }

    html[data-bs-theme="dark"] .exam-builder__details-heading i {
        background: #262f57;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .exam-builder__bank-summary-icon {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .exam-builder__question-picker {
        background: #111827;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .exam-builder__pool-metric {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 .3rem .8rem rgb(0 0 0 / 18%);
    }

    html[data-bs-theme="dark"] .exam-builder__pool-metric--selected {
        background: linear-gradient(145deg, #262f57, #1e293b);
        border-color: #4855a3;
    }

    html[data-bs-theme="dark"] .exam-builder__pool-label,
    html[data-bs-theme="dark"] .exam-builder__pool-detail {
        color: #94a3b8;
    }

    html[data-bs-theme="dark"] .exam-builder__pool-value {
        color: #f8fafc;
    }

    html[data-bs-theme="dark"] .exam-builder__pool-difficulty--easy {
        background: #12362f;
        border-color: #1d5a4b;
        color: #a7f3d0;
    }

    html[data-bs-theme="dark"] .exam-builder__pool-difficulty--moderate {
        background: #3b2e14;
        border-color: #6b4d18;
        color: #fde68a;
    }

    html[data-bs-theme="dark"] .exam-builder__pool-difficulty--hard {
        background: #3b1f26;
        border-color: #6b2b38;
        color: #fecaca;
    }

</style>

<main class="container py-4 exam-builder">
    <header class="exam-builder__hero d-flex flex-wrap justify-content-between align-items-center gap-4 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3 gap-md-4">
            <span class="exam-builder__hero-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
            <div>
                <a href="{{ route($isFacultyRequest ? 'admin.tests.index' : 'super-admin.tests.index') }}" class="small text-white-50 text-decoration-none">&larr; Examinations</a>
                <div class="exam-builder__eyebrow mt-2">{{ $isFacultyRequest ? 'Faculty exam request' : 'Timed assessment' }}</div>
                <h1 class="display-6 fw-bold mt-1 mb-2">{{ $isFacultyRequest ? 'Build an examination' : 'Create timed examination' }}</h1>
                <p class="mb-0 text-white-50">
                    {{ $isFacultyRequest ? 'Choose a program, prepare your questions, and send the exam for Admin approval.' : 'Bring program questions together into a scheduled examination.' }}
                </p>
            </div>
        </div>
        <a href="{{ route($isFacultyRequest ? 'admin.question-bank.index' : 'super-admin.question-bank.index') }}" class="btn btn-light rounded-pill px-4">
            <i class="bi bi-journal-text me-1" aria-hidden="true"></i> Manage question bank
        </a>
    </header>

    <section class="exam-builder__program-card mb-4" aria-labelledby="exam-setup-heading">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 p-4">
            <div>
                <span class="badge rounded-pill {{ $isFacultyRequest ? 'text-bg-warning' : 'text-bg-primary' }} mb-2">
                    {{ $isFacultyRequest ? 'Requires Admin approval' : 'Admin-only timed assessment' }}
                </span>
                <h2 class="h4 fw-bold mb-1" id="exam-setup-heading">{{ $isFacultyRequest ? 'Choose a program' : 'Start with a college' }}</h2>
                <p class="text-muted mb-0">
                    {{ $isFacultyRequest ? 'Select a program in your college to prepare your exam for Admin approval.' : 'Choose a college, then select one of its programs to include Faculty questions in the exam builder.' }}
                </p>
            </div>
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-end gap-2">
                @if ($isFacultyRequest)
                    <form id="exam-course-form" method="GET" action="{{ route('admin.tests.create') }}">
                        <label class="form-label fw-semibold small mb-1" for="exam-course">Program</label>
                        <select id="exam-course" name="course_id" class="form-select form-select-lg" required @disabled($courses->isEmpty())>
                            @if ($courses->isEmpty())
                                <option value="">No programs available</option>
                            @else
                                <option value="">Choose a program</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @if ($courses->isNotEmpty())
                            <noscript><button class="btn btn-outline-primary mt-2" type="submit">Load program questions</button></noscript>
                        @endif
                    </form>
                @else
                    <form id="exam-department-form" method="GET" action="{{ route('super-admin.tests.create') }}">
                        <label class="form-label fw-semibold small mb-1" for="exam-department">College</label>
                        <select id="exam-department" name="department_id" class="form-select form-select-lg" required>
                            <option value="">Choose a college</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) $selectedDepartmentId === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="btn btn-outline-primary mt-2" type="submit">Load programs</button></noscript>
                    </form>
                    <form id="exam-course-form" method="GET" action="{{ route('super-admin.tests.create') }}">
                        <input type="hidden" name="department_id" value="{{ $selectedDepartmentId }}">
                        <label class="form-label fw-semibold small mb-1" for="exam-course">Program</label>
                        <select
                            id="exam-course"
                            name="course_id"
                            class="form-select form-select-lg"
                            required
                            @disabled($selectedDepartmentId === null || $courses->isEmpty())
                        >
                            @if ($selectedDepartmentId === null)
                                <option value="">Choose a college first</option>
                            @elseif ($courses->isEmpty())
                                <option value="">No programs available</option>
                            @else
                                <option value="">Choose a program</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @if ($selectedDepartmentId !== null && $courses->isNotEmpty())
                            <noscript><button class="btn btn-outline-primary mt-2" type="submit">Load program questions</button></noscript>
                        @endif
                    </form>
                @endif
                @if ($selectedCourseId)
                    <button
                        class="btn btn-primary btn-lg rounded-3 exam-builder__create-button text-nowrap"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#createExamModal"
                        @disabled($questionBankItems->isEmpty())
                    >
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Create examination
                    </button>
                @endif
            </div>
        </div>
    </section>

    @if ($isFacultyRequest && $courses->isEmpty())
        <div class="alert alert-info rounded-4 shadow-sm" role="status">No programs are available for your college. Ask an Admin to assign your faculty account to a college.</div>
    @elseif ($isFacultyRequest && !$selectedCourseId)
        <div class="alert alert-info rounded-4 shadow-sm" role="status">Choose a program in your college to start building an exam.</div>
    @elseif (!$isFacultyRequest && $selectedDepartmentId === null)
        <div class="alert alert-info rounded-4 shadow-sm" role="status">Choose a college to view its available programs.</div>
    @elseif (!$isFacultyRequest && !$selectedCourseId && $courses->isEmpty())
        <div class="alert alert-info rounded-4 shadow-sm" role="status">There are no programs available in this college yet.</div>
    @elseif (!$isFacultyRequest && !$selectedCourseId)
        <div class="alert alert-info rounded-4 shadow-sm" role="status">Choose a program to start building an exam from its Faculty question bank.</div>
    @elseif ($questionBankItems->isEmpty())
        <div class="alert alert-info rounded-4 shadow-sm" role="status">
            No enabled questions are currently available in this program. Faculty can enable questions in the Question Bank.
        </div>
    @else
        <section class="exam-builder__bank-summary d-flex flex-wrap align-items-center gap-3 p-3 p-md-4 mb-4" aria-labelledby="program-question-bank-heading">
            <span class="exam-builder__bank-summary-icon" aria-hidden="true"><i class="bi bi-collection"></i></span>
            <div class="flex-grow-1">
                <h2 class="h5 fw-bold mb-1" id="program-question-bank-heading">Question bank ready</h2>
                <p class="text-muted mb-0">All {{ $questionBankItems->count() }} questions in this program are available to choose in the exam setup for {{ $courses->firstWhere('id', $selectedCourseId)?->name }}.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $questionBankItems->count() }} available</span>
        </section>

        <div class="modal fade" id="createExamModal" tabindex="-1" aria-labelledby="create-exam-heading" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable exam-builder__modal-dialog">
                <div class="modal-content exam-builder__modal border-0 shadow-lg rounded-4">
                    <form id="createExamForm" action="{{ route($isFacultyRequest ? 'admin.tests.store-manual' : 'super-admin.tests.store-manual') }}" method="POST">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                        <div class="modal-header border-0 px-4 pt-4">
                            <div>
                                <span class="exam-builder__eyebrow text-primary">Examination setup</span>
                                <h2 class="modal-title h4 fw-bold mb-1" id="create-exam-heading">Build your exam</h2>
                                <p class="small text-muted mb-0">{{ $courses->firstWhere('id', $selectedCourseId)?->name }} · {{ $questionBankItems->count() }} questions available</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4">
                            <section class="rounded-4 bg-body-tertiary p-3 p-md-4 mb-4" aria-labelledby="exam-selection-heading">
                                <div class="mb-3">
                                    <div>
                                        <h3 class="h6 fw-bold mb-1" id="exam-selection-heading">Choose your questions</h3>
                                        <p class="small text-muted mb-0">Filter by topic and difficulty, auto-pick questions, or select them manually.</p>
                                    </div>
                                </div>
                                <div class="exam-builder__pool-summary mb-3" role="group" aria-label="Question Bank pool summary">
                                    <div class="exam-builder__pool-metric">
                                        <span class="exam-builder__pool-label">Total Available</span>
                                        <span class="exam-builder__pool-value tabular-nums">{{ $totalQuestions }} {{ \Illuminate\Support\Str::plural('question', $totalQuestions) }}</span>
                                        <span class="exam-builder__pool-detail tabular-nums">across {{ $topicsCount }} {{ \Illuminate\Support\Str::plural('topic', $topicsCount) }}</span>
                                    </div>
                                    <div class="exam-builder__pool-metric">
                                        <span class="exam-builder__pool-label">Difficulty Breakdown</span>
                                        <div
                                            class="exam-builder__pool-breakdown"
                                            role="group"
                                            aria-label="{{ $easyCount }} Easy · {{ $moderateCount }} Moderate · {{ $hardCount }} Hard"
                                        >
                                            <span class="exam-builder__pool-difficulty exam-builder__pool-difficulty--easy">
                                                <span class="tabular-nums">{{ $easyCount }}</span> Easy
                                            </span>
                                            <span class="exam-builder__pool-difficulty exam-builder__pool-difficulty--moderate">
                                                <span class="tabular-nums">{{ $moderateCount }}</span> Moderate
                                            </span>
                                            <span class="exam-builder__pool-difficulty exam-builder__pool-difficulty--hard">
                                                <span class="tabular-nums">{{ $hardCount }}</span> Hard
                                            </span>
                                        </div>
                                    </div>
                                    <div class="exam-builder__pool-metric exam-builder__pool-metric--selected">
                                        <span class="exam-builder__pool-label">Selected for Exam</span>
                                        <span class="exam-builder__pool-value tabular-nums" id="modal-selected-question-count" role="status" aria-live="polite">0 selected</span>
                                        <span class="exam-builder__pool-detail" id="modal-selected-question-breakdown">No questions selected yet</span>
                                    </div>
                                </div>
                                <div class="row g-2 align-items-end exam-builder__quick-pick mb-3">
                                    <div class="col-12 col-md-4">
                                        <label for="auto-pick-topic" class="form-label small fw-semibold mb-1">Topic</label>
                                        <select id="auto-pick-topic" class="form-select">
                                            <option value="">All Topics</option>
                                            @foreach ($questionBankTopics as $topic)
                                                <option value="{{ $topic['key'] }}">{{ $topic['name'] }} ({{ $topic['count'] }} {{ \Illuminate\Support\Str::plural('item', $topic['count']) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label for="auto-pick-difficulty" class="form-label small fw-semibold mb-1">Difficulty</label>
                                        <select id="auto-pick-difficulty" class="form-select">
                                            <option value="all">All Difficulties</option>
                                            <option value="easy">Easy</option>
                                            <option value="moderate">Moderate</option>
                                            <option value="hard">Hard</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label for="auto-pick-count" class="form-label small fw-semibold mb-1">Number of questions</label>
                                        <input
                                            id="auto-pick-count"
                                            class="form-control"
                                            type="number"
                                            value="{{ min(5, $questionBankItems->count()) }}"
                                            min="1"
                                            max="{{ min(100, $questionBankItems->count()) }}"
                                        >
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <button class="btn btn-outline-primary w-100 exam-builder__auto-pick-button" id="auto-pick-questions" type="button">
                                            <i class="bi bi-shuffle me-1" aria-hidden="true"></i>Auto-Pick
                                        </button>
                                    </div>
                                </div>
                                <div class="exam-builder__selection-feedback d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <p id="auto-pick-status" class="small text-muted mb-0" role="status" aria-live="polite"></p>
                                    <button class="btn btn-link btn-sm text-muted text-nowrap px-1 exam-builder__clear-selection" id="clear-exam-selection" type="button" hidden>
                                        Clear selection
                                    </button>
                                </div>
                                <div class="small text-danger mt-2" id="question-selection-error" role="alert" hidden></div>
                                @error('question_bank_item_ids')
                                    <div class="small text-danger mt-2" role="alert">{{ $message }}</div>
                                @enderror
                                <div class="exam-builder__question-picker mt-3">
                                    @foreach ($questionBankItems as $item)
                                        @php
                                            $examTopicName = trim((string) ($item->topicRecord?->name ?? $item->topic ?? 'General'));
                                            $examTopicKey = mb_strtolower($examTopicName, 'UTF-8');
                                            $examDifficulty = strtolower((string) ($item->difficulty ?? 'moderate'));
                                            $examDifficulty = in_array($examDifficulty, ['easy', 'hard'], true) ? $examDifficulty : 'moderate';
                                            $examDifficultyLabel = match ($examDifficulty) {
                                                'easy' => 'Easy',
                                                'hard' => 'Hard',
                                                default => 'Moderate',
                                            };
                                            $examQualityStatus = $questionQualityMetrics[$item->id]['quality_status']
                                                ?? ($item->needs_revision ? 'flagged' : 'untested');
                                        @endphp
                                        <div
                                            data-exam-question-option
                                            data-topic-key="{{ $examTopicKey }}"
                                            data-difficulty="{{ $examDifficulty }}"
                                            data-quality-status="{{ $examQualityStatus }}"
                                        >
                                            <label class="exam-builder__question-card d-flex gap-3 align-items-start h-100 p-3">
                                                <input
                                                    class="form-check-input mt-1 js-exam-question"
                                                    type="checkbox"
                                                    name="question_bank_item_ids[]"
                                                    value="{{ $item->id }}"
                                                    @checked(in_array($item->id, (array) old('question_bank_item_ids', [])))
                                                >
                                                <span class="exam-builder__question-copy flex-grow-1">
                                                    <span class="badge rounded-pill text-bg-light mb-2">{{ $examTopicName }}</span>
                                                    <span class="badge rounded-pill {{ $examDifficulty === 'hard' ? 'text-bg-danger' : ($examDifficulty === 'easy' ? 'text-bg-success' : 'text-bg-warning') }} mb-2">{{ $examDifficultyLabel }}</span>
                                                    <span class="exam-builder__question-text d-block fw-semibold">{{ $item->question }}</span>
                                                    @if ($item->image_path)
                                                        <img src="{{ asset('storage/'.$item->image_path) }}" alt="Question illustration" class="img-fluid rounded border mt-2" style="max-height: 7rem; object-fit: contain;">
                                                    @endif
                                                    <span class="small text-muted mt-2 d-block">{{ count($item->options ?? []) }} answer choices · {{ $item->author->name ?? 'Former Faculty' }}'s bank</span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                            <section aria-labelledby="exam-details-heading">
                                <h3 class="h6 fw-bold mb-3 exam-builder__details-heading" id="exam-details-heading"><i class="bi bi-calendar2-week" aria-hidden="true"></i>Exam details and schedule</h3>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="exam-title" class="form-label">Examination name</label>
                                        <input id="exam-title" name="title" value="{{ old('title') }}" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="exam-description" class="form-label">Instructions <span class="text-muted">(optional)</span></label>
                                        <textarea id="exam-description" name="description" class="form-control" rows="2" maxlength="5000">{{ old('description') }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="available-from" class="form-label">Start date and time</label>
                                        <input
                                            id="available-from"
                                            type="datetime-local"
                                            name="available_from"
                                            value="{{ old('available_from') }}"
                                            class="form-control"
                                            required
                                        >
                                        @if ($isFacultyRequest)
                                            <div class="form-text text-warning-emphasis mt-2"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Suggestion: schedule at least two days ahead to give the Admin time to review. You can still choose today.</div>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <label for="available-until" class="form-label">Closing date and time <span class="text-muted">(optional)</span></label>
                                        <input id="available-until" type="datetime-local" name="available_until" value="{{ old('available_until') }}" class="form-control">
                                        <div class="form-text">Choose a time after the start time.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="time-limit" class="form-label">Time pressure (minutes)</label>
                                        <input id="time-limit" type="number" name="time_limit" value="{{ old('time_limit', 60) }}" min="1" max="300" class="form-control" required>
                                    </div>
                                </div>
                            </section>
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4 justify-content-between">
                            <p class="small text-muted mb-0 me-auto">
                                <i class="bi {{ $isFacultyRequest ? 'bi-shield-check' : 'bi-calendar-check' }} me-1" aria-hidden="true"></i>
                                {{ $isFacultyRequest ? 'Students can access it after Admin approval.' : 'Students can access it during the scheduled window.' }}
                            </p>
                            <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Continue editing</button>
                            <button class="btn btn-primary rounded-pill px-4 exam-builder__create-button" type="submit">
                                <i class="bi {{ $isFacultyRequest ? 'bi-send' : 'bi-check2-circle' }} me-1" aria-hidden="true"></i>
                                {{ $isFacultyRequest ? 'Submit for approval' : 'Publish timed examination' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</main>

@if ($errors->any() && $questionBankItems->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('createExamModal')).show();
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const departmentForm = document.getElementById('exam-department-form');
        const departmentSelect = document.getElementById('exam-department');
        const courseForm = document.getElementById('exam-course-form');
        const courseSelect = document.getElementById('exam-course');
        if (departmentForm && departmentSelect) {
            departmentSelect.addEventListener('change', () => departmentForm.requestSubmit());
        }
        if (courseForm && courseSelect) {
            courseSelect.addEventListener('change', () => courseForm.requestSubmit());
        }

        const form = document.getElementById('createExamForm');
        if (!form) {
            return;
        }

        const startsAtInput = document.getElementById('available-from');
        const closesAtInput = document.getElementById('available-until');
        const updateClosingDateMinimum = () => {
            closesAtInput.min = startsAtInput.value;
            if (closesAtInput.value && closesAtInput.value <= startsAtInput.value) {
                closesAtInput.value = '';
            }
        };
        startsAtInput.addEventListener('input', updateClosingDateMinimum);
        startsAtInput.addEventListener('change', updateClosingDateMinimum);
        updateClosingDateMinimum();

        const modalSelectedCount = document.getElementById('modal-selected-question-count');
        const modalSelectedBreakdown = document.getElementById('modal-selected-question-breakdown');
        const selectionError = document.getElementById('question-selection-error');
        const questionOptions = Array.from(form.querySelectorAll('[data-exam-question-option]'));
        const questionCheckboxes = questionOptions.map((option) => option.querySelector('.js-exam-question'));
        const topicSelect = document.getElementById('auto-pick-topic');
        const difficultySelect = document.getElementById('auto-pick-difficulty');
        const autoPickCountInput = document.getElementById('auto-pick-count');
        const autoPickButton = document.getElementById('auto-pick-questions');
        const clearSelectionButton = document.getElementById('clear-exam-selection');
        const autoPickStatus = document.getElementById('auto-pick-status');
        const difficultyLabels = { easy: 'Easy', moderate: 'Moderate', hard: 'Hard' };
        const qualityPriority = { healthy: 0, untested: 1, needs_review: 2, flagged: 3 };
        const getSelectedQuestionCount = () => questionCheckboxes.filter((checkbox) => checkbox.checked).length;
        const getMatchingQuestionOptions = () => questionOptions.filter((option) => {
            const topicMatches = !topicSelect.value || option.dataset.topicKey === topicSelect.value;
            const difficultyMatches = difficultySelect.value === 'all'
                || option.dataset.difficulty === difficultySelect.value;

            return topicMatches && difficultyMatches;
        });
        const updateSelectedQuestionCount = () => {
            const selectedQuestions = questionCheckboxes.filter((checkbox) => checkbox.checked);
            const breakdown = { easy: 0, moderate: 0, hard: 0 };
            selectedQuestions.forEach((checkbox) => {
                const difficulty = checkbox.closest('[data-exam-question-option]').dataset.difficulty;
                if (Object.prototype.hasOwnProperty.call(breakdown, difficulty)) {
                    breakdown[difficulty]++;
                }
            });
            const difficultySummary = Object.entries(breakdown)
                .filter(([, count]) => count > 0)
                .map(([difficulty, count]) => `${difficultyLabels[difficulty]}: ${count}`);
            modalSelectedCount.textContent = `${selectedQuestions.length} selected`;
            modalSelectedBreakdown.textContent = difficultySummary.length > 0
                ? difficultySummary.join(' · ')
                : 'No questions selected yet';
            clearSelectionButton.hidden = selectedQuestions.length === 0;
        };
        const updateQuestionFilters = () => {
            const matchingOptions = getMatchingQuestionOptions();
            const maxPickCount = Math.min(100, matchingOptions.length);
            const matchingOptionSet = new Set(matchingOptions);
            questionOptions.forEach((option) => {
                option.hidden = !matchingOptionSet.has(option);
            });

            if (maxPickCount === 0) {
                autoPickCountInput.min = '0';
                autoPickCountInput.max = '0';
                autoPickCountInput.value = '0';
                autoPickCountInput.disabled = true;
                autoPickButton.disabled = true;
                autoPickStatus.textContent = 'No enabled questions match these filters.';

                return;
            }

            autoPickCountInput.min = '1';
            autoPickCountInput.max = String(maxPickCount);
            autoPickCountInput.disabled = false;
            const requestedCount = Number.parseInt(autoPickCountInput.value, 10);
            if (!Number.isInteger(requestedCount) || requestedCount < 1) {
                autoPickCountInput.value = String(Math.min(5, maxPickCount));
            } else if (requestedCount > maxPickCount) {
                autoPickCountInput.value = String(maxPickCount);
            }
            autoPickButton.disabled = false;
            autoPickStatus.textContent = '';
        };
        const shuffle = (items) => {
            const shuffledItems = [...items];
            for (let index = shuffledItems.length - 1; index > 0; index--) {
                const randomIndex = Math.floor(Math.random() * (index + 1));
                [shuffledItems[index], shuffledItems[randomIndex]] = [shuffledItems[randomIndex], shuffledItems[index]];
            }

            return shuffledItems;
        };
        const autoPickQuestions = () => {
            const requestedCount = Number.parseInt(autoPickCountInput.value, 10);
            if (
                !autoPickCountInput.checkValidity()
                || !Number.isInteger(requestedCount)
                || requestedCount < 1
            ) {
                autoPickCountInput.reportValidity();
                return;
            }

            const matchingOptions = getMatchingQuestionOptions();
            const matchingSelectedCount = matchingOptions.filter((option) => option.querySelector('.js-exam-question').checked).length;
            const requestedAdditionalCount = Math.max(0, requestedCount - matchingSelectedCount);
            const unselectedOptions = matchingOptions.filter((option) => !option.querySelector('.js-exam-question').checked);
            const candidatesByQuality = new Map();
            unselectedOptions.forEach((option) => {
                const status = option.dataset.qualityStatus || 'untested';
                if (!candidatesByQuality.has(status)) {
                    candidatesByQuality.set(status, []);
                }
                candidatesByQuality.get(status).push(option);
            });
            const prioritizedCandidates = [...candidatesByQuality.entries()]
                .sort(([left], [right]) => (qualityPriority[left] ?? 1) - (qualityPriority[right] ?? 1))
                .flatMap(([, candidates]) => shuffle(candidates));
            const remainingExamCapacity = Math.max(0, 100 - getSelectedQuestionCount());
            const questionsToPick = Math.min(
                requestedAdditionalCount,
                prioritizedCandidates.length,
                remainingExamCapacity,
            );

            if (questionsToPick === 0) {
                autoPickStatus.textContent = requestedAdditionalCount === 0
                    ? 'The requested number for this filter is already selected.'
                    : 'Exams can include up to 100 questions. Clear or deselect questions before adding more.';

                return;
            }

            prioritizedCandidates.slice(0, questionsToPick).forEach((option) => {
                option.querySelector('.js-exam-question').checked = true;
            });
            selectionError.hidden = true;
            updateSelectedQuestionCount();
            autoPickStatus.textContent = questionsToPick < requestedAdditionalCount
                ? `Added ${questionsToPick} question${questionsToPick === 1 ? '' : 's'}; the exam limit is 100 questions.`
                : `Added ${questionsToPick} question${questionsToPick === 1 ? '' : 's'}. Existing selections were kept.`;
        };

        questionCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', () => {
            if (checkbox.checked && getSelectedQuestionCount() > 100) {
                checkbox.checked = false;
                selectionError.textContent = 'Exams can include up to 100 questions.';
                selectionError.hidden = false;

                return;
            }
            selectionError.hidden = true;
            autoPickStatus.textContent = '';
            updateSelectedQuestionCount();
        }));
        topicSelect.addEventListener('change', updateQuestionFilters);
        difficultySelect.addEventListener('change', updateQuestionFilters);
        autoPickCountInput.addEventListener('input', () => {
            const maxPickCount = Number.parseInt(autoPickCountInput.max, 10) || 0;
            let requestedCount = Number.parseInt(autoPickCountInput.value, 10);
            if (Number.isInteger(requestedCount) && requestedCount > maxPickCount) {
                requestedCount = maxPickCount;
                autoPickCountInput.value = String(maxPickCount);
            }
            autoPickButton.disabled = maxPickCount === 0 || !Number.isInteger(requestedCount) || requestedCount < 1;
            autoPickStatus.textContent = '';
        });
        autoPickButton.addEventListener('click', autoPickQuestions);
        clearSelectionButton.addEventListener('click', () => {
            questionCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            selectionError.hidden = true;
            autoPickStatus.textContent = 'Selection cleared.';
            updateSelectedQuestionCount();
        });
        form.addEventListener('submit', (event) => {
            const selectedCount = getSelectedQuestionCount();
            if (selectedCount === 0) {
                event.preventDefault();
                selectionError.textContent = 'Select at least one question or use Auto-Pick.';
                selectionError.hidden = false;
                questionOptions.find((option) => !option.hidden)?.querySelector('.js-exam-question')?.focus();
                return;
            }

            if (selectedCount > 100) {
                event.preventDefault();
                selectionError.textContent = 'Exams can include up to 100 questions.';
                selectionError.hidden = false;
                return;
            }

            if (closesAtInput.value && closesAtInput.value <= startsAtInput.value) {
                event.preventDefault();
                closesAtInput.setCustomValidity('The closing date and time must be after the start date and time.');
                closesAtInput.reportValidity();
                return;
            }

        });
        closesAtInput.addEventListener('input', () => closesAtInput.setCustomValidity(''));
        updateQuestionFilters();
        updateSelectedQuestionCount();
    });
</script>
@endsection
