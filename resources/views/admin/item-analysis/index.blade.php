@extends('layouts.app')

@section('content')
<style>
    .faculty-analysis__hero {
        background:
            radial-gradient(circle at 90% 15%, rgb(255 255 255 / 13%) 0 4.5rem, transparent 4.6rem),
            linear-gradient(125deg, #102a56 0%, #2948a3 58%, #5143a8 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.15rem;
        box-shadow: 0 1rem 2rem rgb(35 54 115 / 16%);
        color: #fff;
        padding: clamp(1.25rem, 3vw, 2rem);
    }

    .faculty-analysis__eyebrow {
        color: #c7d2fe;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .faculty-analysis__filter-bar,
    .faculty-analysis__table-wrap {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 .65rem 1.4rem rgb(15 23 42 / 6%);
        overflow: hidden;
    }

    .faculty-analysis__applied-filter {
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 999px;
        color: #3730a3;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 700;
        gap: .45rem;
        padding: .55rem .85rem;
    }

    .faculty-analysis__modal {
        border: 0;
        border-radius: 1.1rem;
        max-height: min(90dvh, 42rem);
        overflow: hidden;
    }

    .faculty-analysis__modal .modal-header {
        background: linear-gradient(145deg, #f8faff, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        padding: 1.35rem 1.5rem;
    }

    .faculty-analysis__modal .modal-body {
        overflow-y: auto;
        padding: 1.5rem;
    }

    .faculty-analysis__modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }

    .faculty-analysis__question-dialog {
        --bs-modal-width: 70rem;
    }

    .faculty-analysis__filter-icon {
        align-items: center;
        background: #e0e7ff;
        border: 1px solid #c7d2fe;
        border-radius: .8rem;
        color: #4338ca;
        display: inline-flex;
        flex: 0 0 2.8rem;
        font-size: 1.15rem;
        height: 2.8rem;
        justify-content: center;
    }

    .faculty-analysis__field {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        padding: 1rem;
    }

    .faculty-analysis__select-options.show {
        display: block;
        margin-top: .5rem;
        max-height: 11rem;
        overflow-y: auto;
        overscroll-behavior: contain;
        position: static;
        scrollbar-gutter: stable;
        transform: none !important;
        width: 100%;
    }

    .faculty-analysis__select-options .dropdown-item {
        min-height: 44px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .faculty-analysis__table {
        --bs-table-bg: #fff;
        --bs-table-color: #1e293b;
        min-width: 780px;
    }

    .faculty-analysis__table thead th {
        background: #f8fafc;
        border-bottom-color: #e2e8f0;
        color: #475569;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        padding: .9rem 1rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .faculty-analysis__table tbody td {
        border-color: #eef2f7;
        padding: 1rem;
    }

    .faculty-analysis__table tbody tr.is-difficult > td {
        background: #fffbeb;
    }

    .faculty-analysis__question {
        min-width: 16rem;
        overflow-wrap: anywhere;
    }

    .faculty-analysis__student-table {
        table-layout: fixed;
        width: 100%;
    }

    .faculty-analysis__student-table th:first-child,
    .faculty-analysis__student-table td:first-child {
        width: 30%;
    }

    .faculty-analysis__student-table th:nth-child(2),
    .faculty-analysis__student-table td:nth-child(2) {
        width: 46%;
    }

    .faculty-analysis__student-table th:last-child,
    .faculty-analysis__student-table td:last-child {
        width: 24%;
    }

    .faculty-analysis__student-responses {
        max-height: min(24rem, 45dvh);
        overflow: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .faculty-analysis__student-responses thead th {
        background: #f8fafc;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .faculty-analysis__student-responses td {
        overflow-wrap: anywhere;
    }

    .faculty-analysis__student-responses td:last-child .badge {
        white-space: normal;
    }

    .faculty-analysis__percent {
        color: #64748b;
        font-size: .8rem;
        white-space: nowrap;
    }

    .faculty-analysis__no-responses {
        align-items: flex-start;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: .9rem;
        color: #334155;
        display: flex;
        gap: .9rem;
        padding: 1rem;
    }

    .faculty-analysis__no-responses-icon {
        align-items: center;
        background: #e0e7ff;
        border-radius: .75rem;
        color: #4338ca;
        display: inline-flex;
        flex: 0 0 2.5rem;
        height: 2.5rem;
        justify-content: center;
    }

    .analysis-response-pie {
        border: 1px solid #e2e8f0;
        border-radius: 50%;
        flex: 0 0 9rem;
        height: 9rem;
        width: 9rem;
    }

    .analysis-response-legend {
        list-style: none;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .analysis-response-legend li {
        align-items: start;
        border-bottom: 1px solid #e2e8f0;
        display: grid;
        gap: .5rem .75rem;
        grid-template-columns: minmax(0, 1fr) minmax(0, 9rem);
        padding: .5rem 0;
    }

    .analysis-response-choice {
        align-items: flex-start;
        background: transparent;
        border: 0;
        border-radius: .4rem;
        color: inherit;
        cursor: pointer;
        display: flex;
        gap: .5rem;
        min-width: 0;
        padding: .2rem .35rem;
        text-align: left;
        transition: background-color 140ms ease, color 140ms ease;
        width: 100%;
    }

    .analysis-response-choice:hover,
    .analysis-response-choice[aria-pressed="true"] {
        background: #eef2ff;
        color: #3730a3;
    }

    .analysis-response-choice:focus-visible {
        outline: 2px solid #6366f1;
        outline-offset: 2px;
    }

    .analysis-response-choice:disabled {
        cursor: default;
    }

    .analysis-response-choice__details {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 0;
    }

    .analysis-response-choice__label {
        overflow-wrap: anywhere;
    }

    .analysis-response-count {
        max-width: 9rem;
        text-align: right;
        white-space: normal;
    }

    .faculty-analysis__student-response-header {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        justify-content: space-between;
    }

    .faculty-analysis__student-response-heading {
        flex: 1 1 12rem;
        min-width: 0;
    }

    .faculty-analysis__response-filter-cards {
        display: grid;
        flex: 1 1 17rem;
        gap: .4rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-left: auto;
        max-width: 24rem;
    }

    .faculty-analysis__response-filter-card {
        align-items: center;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: .65rem;
        color: #334155;
        cursor: pointer;
        gap: .15rem;
        justify-content: center;
        min-height: 3.35rem;
        min-width: 0;
        padding: .35rem .2rem;
        transition: background-color 140ms ease, border-color 140ms ease, box-shadow 140ms ease;
    }

    .faculty-analysis__response-filter-card:hover,
    .faculty-analysis__response-filter-card:focus-visible {
        box-shadow: 0 0 0 .15rem rgb(99 102 241 / 14%);
    }

    .faculty-analysis__response-filter-card:focus-visible {
        outline: 2px solid #6366f1;
        outline-offset: 2px;
    }

    .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="all"] {
        background: #eef2ff;
        border-color: #a5b4fc;
        color: #3730a3;
    }

    .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="correct"] {
        background: #ecfdf5;
        border-color: #86efac;
        color: #166534;
    }

    .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="incorrect"] {
        background: #fff1f2;
        border-color: #fda4af;
        color: #9f1239;
    }

    .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="unanswered"] {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #334155;
    }

    .faculty-analysis__response-filter-count {
        font-size: 1rem;
        font-weight: 800;
        line-height: 1;
    }

    .faculty-analysis__response-filter-label {
        font-size: .62rem;
        font-weight: 700;
        line-height: 1.15;
        overflow-wrap: anywhere;
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .faculty-analysis__response-filter-cards {
            flex-basis: 100%;
            max-width: none;
        }
    }

    .analysis-response-swatch {
        border-radius: 50%;
        flex: 0 0 .75rem;
        height: .75rem;
        width: .75rem;
    }

    .faculty-analysis__review {
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 999px;
        color: #92400e;
        cursor: pointer;
        font-size: .75rem;
        font-weight: 700;
        padding: .45rem .7rem;
        white-space: nowrap;
        transition: background-color 140ms ease, box-shadow 140ms ease;
    }

    .faculty-analysis__review:hover,
    .faculty-analysis__review:focus-visible {
        background: #fde68a;
        box-shadow: 0 0 0 .2rem rgb(245 158 11 / 20%);
    }

    .faculty-analysis__insight {
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 999px;
        color: #3730a3;
        cursor: pointer;
        font-size: .75rem;
        font-weight: 700;
        padding: .45rem .7rem;
        white-space: nowrap;
        transition: background-color 140ms ease, box-shadow 140ms ease;
    }

    .faculty-analysis__insight:hover,
    .faculty-analysis__insight:focus-visible {
        background: #e0e7ff;
        box-shadow: 0 0 0 .2rem rgb(99 102 241 / 18%);
    }

    .faculty-analysis__empty {
        padding: 2.75rem 1.25rem !important;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .faculty-analysis__table {
            min-width: 0;
        }

        .faculty-analysis__table thead {
            display: none;
        }

        .faculty-analysis__table tbody tr:not(:has(> td[colspan])) {
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: .1rem;
            padding: .5rem;
        }

        .faculty-analysis__table tbody tr:last-child {
            border-bottom: 0;
        }

        .faculty-analysis__table tbody td {
            align-items: start;
            border: 0;
            display: grid;
            gap: .75rem;
            grid-template-columns: 8.5rem minmax(0, 1fr);
            padding: .55rem .65rem;
            text-align: right;
        }

        .faculty-analysis__table tbody td::before {
            color: #64748b;
            content: attr(data-label);
            font-size: .7rem;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
        }

        .faculty-analysis__question {
            min-width: 0;
        }

        .faculty-analysis__modal .modal-header,
        .faculty-analysis__modal .modal-body,
        .faculty-analysis__modal .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .faculty-analysis__modal .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .faculty-analysis__modal .modal-footer .btn {
            width: 100%;
        }
    }

    html[data-bs-theme="dark"] .faculty-analysis__filter-bar,
    html[data-bs-theme="dark"] .faculty-analysis__table-wrap,
    html[data-bs-theme="dark"] .faculty-analysis__field {
        background: #1e293b;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .faculty-analysis__applied-filter,
    html[data-bs-theme="dark"] .faculty-analysis__filter-icon {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .faculty-analysis__exam-options {
        background: #1e293b;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .faculty-analysis__exam-options .dropdown-item {
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .faculty-analysis__exam-options .dropdown-item:hover,
    html[data-bs-theme="dark"] .faculty-analysis__exam-options .dropdown-item:focus {
        background: #273449;
    }

    html[data-bs-theme="dark"] .faculty-analysis__modal .modal-header,
    html[data-bs-theme="dark"] .faculty-analysis__modal .modal-footer {
        background: #172033;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .faculty-analysis__table {
        --bs-table-bg: #1e293b;
        --bs-table-color: #e2e8f0;
        --bs-table-hover-bg: #273449;
        --bs-table-hover-color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .faculty-analysis__table thead th {
        background: #172033;
        border-color: #334155;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .faculty-analysis__student-responses thead th {
        background: #172033;
    }

    html[data-bs-theme="dark"] .faculty-analysis__table tbody td,
    html[data-bs-theme="dark"] .faculty-analysis__table tbody tr {
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .faculty-analysis__table tbody tr.is-difficult > td {
        background: #422006;
    }

    html[data-bs-theme="dark"] .faculty-analysis__no-responses {
        background: #172033;
        border-color: #475569;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .faculty-analysis__no-responses-icon {
        background: #262f57;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .analysis-response-choice:hover,
    html[data-bs-theme="dark"] .analysis-response-choice[aria-pressed="true"] {
        background: #262f57;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .faculty-analysis__response-filter-card {
        background: #1e293b;
        border-color: #475569;
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="all"] {
        background: #262f57;
        border-color: #6366f1;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="correct"] {
        background: #064e3b;
        border-color: #16a34a;
        color: #bbf7d0;
    }

    html[data-bs-theme="dark"] .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="incorrect"] {
        background: #4c0519;
        border-color: #e11d48;
        color: #fecdd3;
    }

    html[data-bs-theme="dark"] .faculty-analysis__response-filter-card[aria-pressed="true"][data-faculty-result-filter="unanswered"] {
        background: #334155;
        border-color: #94a3b8;
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .faculty-analysis__review {
        background: #422006;
        border-color: #854d0e;
        color: #fde68a;
    }

    html[data-bs-theme="dark"] .faculty-analysis__insight {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }
</style>

<main class="container faculty-analysis">
    <header class="faculty-analysis__hero mb-4">
        <div class="faculty-analysis__eyebrow mb-2">Assessment insights</div>
        <h1 class="h2 fw-bold mb-2">Item Analysis</h1>
        <p class="text-white-50 mb-0">Find learning gaps, review question performance, and plan focused follow-up.</p>
    </header>

    <section class="faculty-analysis__filter-bar d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4" aria-label="Item analysis filter">
        <div>
            <div class="small text-uppercase text-muted fw-bold mb-1">Current analysis</div>
        @if ($selectedTest)
            <span class="faculty-analysis__applied-filter"><i class="bi bi-mortarboard" aria-hidden="true"></i>{{ $selectedTest->course->name ?? 'Program' }} · {{ $selectedTest->title }}</span>
        @else
            <span class="faculty-analysis__applied-filter"><i class="bi bi-journal" aria-hidden="true"></i>Choose a program and examination</span>
        @endif
        </div>
        <button class="btn btn-primary rounded-3 px-4" type="button" data-bs-toggle="modal" data-bs-target="#selectFacultyExamModal">
            <i class="bi bi-sliders me-1" aria-hidden="true"></i> Choose Program and Examination
        </button>
    </section>

    <div class="modal fade" id="selectFacultyExamModal" tabindex="-1" aria-labelledby="select-faculty-exam-heading" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable faculty-analysis__question-dialog">
            <div class="modal-content faculty-analysis__modal shadow-lg">
                <form method="GET" action="{{ route('admin.item-analysis.index') }}">
                    <div class="modal-header">
                        <div class="d-flex align-items-start gap-3">
                            <span class="faculty-analysis__filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                            <div>
                                <span class="faculty-analysis__eyebrow text-primary">Explore performance</span>
                                <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="select-faculty-exam-heading">Choose a program and examination</h2>
                                <p class="small text-muted mb-0">Choose a program first, then select an examination to load its analysis and matching topic filters.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="faculty-analysis__field mb-3">
                            <label for="faculty-program-picker" class="form-label fw-semibold"><i class="bi bi-mortarboard me-1 text-primary" aria-hidden="true"></i>Program</label>
                            <input id="faculty-program" name="course_id" type="hidden" value="{{ $selectedCourseId }}">
                            <div class="dropdown">
                                <button
                                    id="faculty-program-picker"
                                    class="form-select text-start"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-display="static"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    aria-controls="faculty-program-options"
                                    @disabled($courses->isEmpty())
                                >
                                    <span data-faculty-program-label>{{ $courses->firstWhere('id', $selectedCourseId)?->name ?? 'Choose a program' }}</span>
                                </button>
                                <ul id="faculty-program-options" class="dropdown-menu faculty-analysis__select-options" role="listbox" aria-labelledby="faculty-program-picker">
                                    @foreach ($courses as $course)
                                        <li>
                                            <button
                                                @class([
                                                    'dropdown-item d-flex align-items-center justify-content-between gap-2',
                                                    'active' => (int) $selectedCourseId === $course->id,
                                                ])
                                                type="button"
                                                role="option"
                                                aria-selected="{{ (int) $selectedCourseId === $course->id ? 'true' : 'false' }}"
                                                data-faculty-program-option
                                                data-value="{{ $course->id }}"
                                                data-label="{{ $course->name }}"
                                            >
                                                <span class="text-truncate">{{ $course->name }}</span>
                                                <i
                                                    @class([
                                                        'bi bi-check2',
                                                        'd-none' => (int) $selectedCourseId !== $course->id,
                                                    ])
                                                    data-faculty-option-check
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="faculty-analysis__field">
                            <label for="faculty-exam-picker" class="form-label fw-semibold"><i class="bi bi-journal-check me-1 text-primary" aria-hidden="true"></i>Examination</label>
                            <input id="faculty-exam" name="test_id" type="hidden" value="{{ $selectedTest?->id }}">
                            <div class="dropdown faculty-analysis__exam-picker">
                                <button
                                    id="faculty-exam-picker"
                                    class="form-select text-start"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-display="static"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    aria-controls="faculty-exam-options"
                                    @disabled($selectedCourseId === null || !$tests->contains('course_id', $selectedCourseId))
                                >
                                    <span data-faculty-exam-label>{{ $selectedTest ? $selectedTest->title.' · '.$selectedTest->scheduleStatus().' · '.$selectedTest->results_count.' result(s)' : ($selectedCourseId === null ? 'Choose a program first' : 'Choose an examination') }}</span>
                                </button>
                                <ul id="faculty-exam-options" class="dropdown-menu faculty-analysis__select-options" role="listbox" aria-labelledby="faculty-exam-picker">
                                    <li class="d-none" data-faculty-exam-empty>
                                        <span class="dropdown-item-text small text-muted">Choose a program first</span>
                                    </li>
                                    @foreach ($tests as $test)
                                        <li @class(['d-none' => (int) $selectedCourseId !== $test->course_id])>
                                            <button
                                                @class([
                                                    'dropdown-item d-flex align-items-center justify-content-between gap-2',
                                                    'active' => $selectedTest?->id === $test->id,
                                                ])
                                                type="button"
                                                role="option"
                                                aria-selected="{{ $selectedTest?->id === $test->id ? 'true' : 'false' }}"
                                                data-faculty-exam-option
                                                data-value="{{ $test->id }}"
                                                data-course-id="{{ $test->course_id }}"
                                                data-label="{{ $test->title }} · {{ $test->scheduleStatus() }} · {{ $test->results_count }} result(s)"
                                                title="{{ $test->title }} · {{ $test->scheduleStatus() }} · {{ $test->results_count }} result(s)"
                                            >
                                                <span class="text-truncate">{{ $test->title }} · {{ $test->scheduleStatus() }} · {{ $test->results_count }} result(s)</span>
                                                <i
                                                    @class([
                                                        'bi bi-check2',
                                                        'd-none' => $selectedTest?->id !== $test->id,
                                                    ])
                                                    data-faculty-option-check
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @if ($courses->isEmpty())
                            <div class="alert alert-info mt-3 mb-0" role="status"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No programs are available for your college.</div>
                        @elseif ($tests->isEmpty())
                            <div class="alert alert-info mt-3 mb-0" role="status"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No scheduled examinations are available for programs in your college.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($selectedTest)
        <section class="row g-3 mb-3" aria-label="Examination summary">
            <div class="col-sm-6 col-xl-3">
                <article class="faculty-analysis__filter-bar h-100 p-3 p-md-4">
                    <div class="small text-uppercase text-muted fw-bold mb-1">Participation</div>
                    <div class="h3 fw-bold mb-1 font-monospace" id="analysis-participation">
                        {{ $summary['participants'] }}@if ($summary['eligible_students'] > 0) / {{ $summary['eligible_students'] }}@endif
                    </div>
                    <p class="small text-muted mb-0">
                        @if ($summary['completion_percentage'] !== null)
                            {{ $summary['completion_percentage'] }}% of approved students submitted
                        @else
                            Student enrollment is unavailable for this exam.
                        @endif
                    </p>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="faculty-analysis__filter-bar h-100 p-3 p-md-4">
                    <div class="small text-uppercase text-muted fw-bold mb-1">Average score</div>
                    <div class="h3 fw-bold mb-1 font-monospace" id="analysis-average-score">
                        {{ $summary['average_score_percentage'] !== null ? $summary['average_score_percentage'].'%' : '—' }}
                    </div>
                    <p class="small text-muted mb-0">Mean percentage on each student's latest attempt</p>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="faculty-analysis__filter-bar h-100 p-3 p-md-4">
                    <div class="small text-uppercase text-muted fw-bold mb-1">Flagged for review</div>
                    <div class="h3 fw-bold mb-1 font-monospace">{{ $summary['questions_to_review'] }}</div>
                    <p class="small text-muted mb-0">Questions with at least 50% incorrect responses</p>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="faculty-analysis__filter-bar h-100 p-3 p-md-4">
                    <div class="small text-uppercase text-muted fw-bold mb-1">Questions</div>
                    <div class="h3 fw-bold mb-1 font-monospace">{{ $summary['question_count'] }}</div>
                    <p class="small text-muted mb-0">Items included in this examination</p>
                </article>
            </div>
        </section>

        <section class="faculty-analysis__filter-bar d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-md-4 mb-3">
            <span class="badge rounded-pill text-bg-light border mb-2">Status: {{ $selectedTest->scheduleStatus() }} · {{ $selectedTest->results_count }} result(s)</span>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.question-bank.index', ['course_id' => $selectedTest->course_id]) }}">
                    <i class="bi bi-journal-text me-1" aria-hidden="true"></i>Open Question Bank
                </a>
                <a class="btn btn-primary" href="{{ route('admin.item-analysis.export', ['test_id' => $selectedTest->id]) }}">
                    <i class="bi bi-download me-1" aria-hidden="true"></i>Export CSV
                </a>
            </div>
            <div class="w-100">
                <h2 class="h4 fw-bold mb-1">{{ $selectedTest->title }}</h2>
                <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Analysis uses each student's latest submitted attempt. Only answers recorded for an item are included in its response totals.</p>
            </div>
        </section>
        <div class="faculty-analysis__table-wrap">
            @php
                $activeFilterCount = count(array_filter([
                    $filters['topic'] !== '',
                    $filters['difficulty'] !== 'all',
                    $filters['review'] !== 'all',
                    $filters['sort'] !== 'default',
                ]));
            @endphp
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 px-md-4 py-3 border-bottom">
                <div>
                    <h3 class="h5 fw-bold mb-1">Question performance</h3>
                    <p class="small text-muted mb-0">A high miss rate is a review signal, not proof that a question is flawed.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ count($analysis) }} of {{ $summary['question_count'] }} questions</span>
                    <button
                        class="btn btn-outline-primary"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#analysisFiltersModal"
                        aria-label="Open question filters{{ $activeFilterCount > 0 ? ', '.$activeFilterCount.' active' : '' }}"
                    >
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        @if ($activeFilterCount > 0)
                            <span class="badge rounded-pill text-bg-primary ms-1">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                </div>
            </div>
            <div class="modal fade" id="analysisFiltersModal" tabindex="-1" aria-labelledby="analysis-filters-heading" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content faculty-analysis__modal shadow-lg">
                        <form method="GET" action="{{ route('admin.item-analysis.index') }}">
                            <input type="hidden" name="test_id" value="{{ $selectedTest->id }}">
                            <div class="modal-header">
                                <div>
                                    <span class="faculty-analysis__eyebrow text-primary">Refine your results</span>
                                    <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="analysis-filters-heading">Filter questions</h2>
                                    <p class="small text-muted mb-0">Choose how to narrow and order this examination's question analysis.</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="analysis-topic">Topic</label>
                                    <select class="form-select" id="analysis-topic" name="topic">
                                        <option value="">All topics</option>
                                        @foreach ($topics as $topic)
                                            <option value="{{ $topic }}" @selected($filters['topic'] === $topic)>{{ $topic }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="analysis-difficulty">Difficulty</label>
                                    <select class="form-select" id="analysis-difficulty" name="difficulty">
                                        <option value="all" @selected($filters['difficulty'] === 'all')>All difficulties</option>
                                        <option value="easy" @selected($filters['difficulty'] === 'easy')>Easy</option>
                                        <option value="moderate" @selected($filters['difficulty'] === 'moderate')>Moderate</option>
                                        <option value="hard" @selected($filters['difficulty'] === 'hard')>Hard</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="analysis-review">Review status</label>
                                    <select class="form-select" id="analysis-review" name="review">
                                        <option value="all" @selected($filters['review'] === 'all')>All questions</option>
                                        <option value="needs-review" @selected($filters['review'] === 'needs-review')>Flagged only</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold" for="analysis-sort">Sort by</label>
                                    <select class="form-select" id="analysis-sort" name="sort">
                                        <option value="default" @selected($filters['sort'] === 'default')>Question order</option>
                                        <option value="most-correct" @selected($filters['sort'] === 'most-correct')>Most correct</option>
                                        <option value="most-incorrect" @selected($filters['sort'] === 'most-incorrect')>Most incorrect</option>
                                        <option value="fewest-responses" @selected($filters['sort'] === 'fewest-responses')>Fewest responses</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <a class="btn btn-outline-secondary" href="{{ route('admin.item-analysis.index', ['test_id' => $selectedTest->id]) }}">Clear filters</a>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light" type="reset" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Apply Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 faculty-analysis__table">
                    <thead class="table-light">
                        <tr><th scope="col">Question</th><th scope="col">Topic</th><th scope="col">Responses recorded</th><th scope="col">Correct</th><th scope="col">Incorrect</th><th scope="col">Review</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($analysis as $row)
                            <tr class="{{ $row['is_difficult'] ? 'is-difficult' : '' }}">
                                <td data-label="Question" class="faculty-analysis__question fw-semibold">{{ $row['question'] }}</td>
                                <td data-label="Topic">{{ $row['topic'] ?: '—' }}</td>
                                <td data-label="Responses recorded"><span class="fw-semibold">{{ $row['total'] }}</span></td>
                                <td data-label="Correct"><span class="text-success fw-bold">{{ $row['correct'] }}</span><span class="faculty-analysis__percent"> ({{ $row['correct_percentage'] }}%)</span></td>
                                <td data-label="Incorrect"><span class="text-danger fw-bold">{{ $row['incorrect'] }}</span><span class="faculty-analysis__percent"> ({{ $row['incorrect_percentage'] }}%)</span></td>
                                <td data-label="Review">
                                    <button
                                        @class([
                                            'faculty-analysis__review' => $row['is_difficult'],
                                            'faculty-analysis__insight' => ! $row['is_difficult'],
                                        ])
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#facultyQuestionReview{{ $loop->index }}"
                                        aria-label="Review question: {{ $row['question'] }}"
                                    >
                                        <i class="bi {{ $row['is_difficult'] ? 'bi-exclamation-circle-fill' : 'bi-graph-up' }} me-1" aria-hidden="true"></i>
                                        Review question
                                    </button>
                                    <div class="modal fade" id="facultyQuestionReview{{ $loop->index }}" tabindex="-1" aria-labelledby="facultyQuestionReviewTitle{{ $loop->index }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                <div class="modal-content faculty-analysis__modal shadow-lg">
                                                    <div class="modal-header">
                                                        <div>
                                                            <span class="faculty-analysis__eyebrow text-primary">Question review</span>
                                                            <h2 class="modal-title h5 fw-bold mt-1 mb-0" id="facultyQuestionReviewTitle{{ $loop->index }}">Question {{ $loop->iteration }}</h2>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h3 class="h6 fw-semibold text-dark mb-3">{{ $row['question'] }}</h3>
                                                        @if ($row['total'] > 0)
                                                            <div class="alert alert-warning">
                                                                <strong class="d-block mb-1">Latest student attempts</strong>
                                                                Among {{ $row['total'] }} recorded responses: <strong>{{ $row['correct'] }} correct</strong> and <strong>{{ $row['incorrect'] }} incorrect</strong> ({{ $row['incorrect_percentage'] }}% incorrect).
                                                                The highlighted choice is the answer key, not an individual student's response.
                                                                Interpret patterns in context, especially when only a few students responded.
                                                            </div>
                                                        @else
                                                            <section class="faculty-analysis__no-responses mb-3" role="status">
                                                                <span class="faculty-analysis__no-responses-icon" aria-hidden="true">
                                                                    <i class="bi bi-hourglass-split"></i>
                                                                </span>
                                                                <div>
                                                                    <h4 class="h6 fw-bold mb-1">No student responses yet</h4>
                                                                    <p class="small mb-0">Answer counts and individual student records will appear here once students answer this question.</p>
                                                                </div>
                                                            </section>
                                                        @endif
                                                        @if ($row['most_selected_distractor'])
                                                            <section class="border rounded-3 bg-light p-3 mb-3">
                                                                <h4 class="small fw-bold mb-1">Teaching insight</h4>
                                                                <p class="small mb-0">
                                                                    Most selected incorrect answer:
                                                                    <strong>{{ $row['most_selected_distractor']['label'] }}</strong>
                                                                    ({{ $row['most_selected_distractor']['count'] }} of {{ $row['total'] }} responses).
                                                                    @if ($row['topic'])
                                                                        Consider reviewing <strong>{{ $row['topic'] }}</strong> with the class; this pattern may point to a misconception.
                                                                    @else
                                                                        Consider reviewing this concept with the class; this pattern may point to a misconception.
                                                                    @endif
                                                                </p>
                                                            </section>
                                                        @elseif ($row['is_difficult'])
                                                            <section class="border rounded-3 bg-light p-3 mb-3">
                                                                <h4 class="small fw-bold mb-1">Teaching follow-up</h4>
                                                                <p class="small mb-0">Consider revisiting this question's concept with the class. The high miss rate is a prompt to investigate, not a judgment that the item is defective.</p>
                                                            </section>
                                                        @endif
                                                        @if ($row['total'] > 0)
                                                            @php
                                                                $responseColors = ['#4f46e5', '#059669', '#d97706', '#e11d48', '#0891b2', '#64748b'];
                                                                $responseSegments = [];
                                                                $responseStart = 0;
                                                                foreach ($row['response_breakdown'] as $responseIndex => $response) {
                                                                    if ($response['count'] === 0) {
                                                                        continue;
                                                                    }
                                                                    $responseEnd = $responseStart + ($response['count'] / $row['total']) * 100;
                                                                    $responseSegments[] = $responseColors[$responseIndex % count($responseColors)].' '.$responseStart.'% '.$responseEnd.'%';
                                                                    $responseStart = $responseEnd;
                                                                }
                                                            @endphp
                                                            <section class="border rounded-3 p-3 mb-3" aria-label="Student answer distribution">
                                                                <h4 class="small fw-bold mb-3">Student answer distribution</h4>
                                                                <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                                                                    <div
                                                                        class="analysis-response-pie"
                                                                        role="img"
                                                                        aria-label="Pie chart showing answer selections for Question {{ $loop->iteration }}"
                                                                        style="background: conic-gradient({{ implode(', ', $responseSegments) }});"
                                                                    ></div>
                                                                    <ul class="analysis-response-legend">
                                                                        @foreach ($row['response_breakdown'] as $responseIndex => $response)
                                                                            <li>
                                                                                <button
                                                                                    class="analysis-response-choice"
                                                                                    type="button"
                                                                                    data-faculty-answer-filter="{{ $response['label'] }}"
                                                                                    aria-label="Filter students who chose {{ $response['label'] }}"
                                                                                    aria-pressed="false"
                                                                                    @disabled($row['student_responses'] === [])
                                                                                >
                                                                                    <span class="analysis-response-swatch" style="background-color: {{ $responseColors[$responseIndex % count($responseColors)] }};" aria-hidden="true"></span>
                                                                                    <span class="analysis-response-choice__details">
                                                                                        <span class="analysis-response-choice__label">{{ $response['label'] }}</span>
                                                                                        @if ($response['is_correct'])
                                                                                            <span class="badge text-bg-success align-self-start">Answer key</span>
                                                                                        @endif
                                                                                    </span>
                                                                                </button>
                                                                                <span class="analysis-response-count small text-muted">{{ $response['count'] }} {{ $response['count'] === 1 ? 'response' : 'responses' }} · {{ $response['percentage'] }}%</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </section>
                                                        @endif
                                                        @if ($row['correct_answer'])
                                                            <p class="small mb-2"><strong>Answer key:</strong> {{ $row['correct_answer'] }}</p>
                                                        @endif
                                                        @if ($row['explanation'])
                                                            <div class="alert alert-info mb-0">
                                                                <strong class="d-block mb-1">Explanation</strong>
                                                                {{ $row['explanation'] }}
                                                            </div>
                                                        @elseif ($row['options'] === [] && ! $row['correct_answer'])
                                                            <p class="small text-muted mb-0">Question choices and answer details are unavailable for this item.</p>
                                                        @endif
                                                        @if ($row['student_responses'] !== [])
                                                            @php
                                                                $studentResponseCount = count($row['student_responses']);
                                                                $unansweredStudentResponseCount = count(array_filter(
                                                                    $row['student_responses'],
                                                                    fn (array $studentResponse): bool => $studentResponse['answer'] === 'Unanswered',
                                                                ));
                                                                $responseFilterCounts = [
                                                                    'all' => $studentResponseCount,
                                                                    'correct' => $row['correct'],
                                                                    'incorrect' => $studentResponseCount - $row['correct'] - $unansweredStudentResponseCount,
                                                                    'unanswered' => $unansweredStudentResponseCount,
                                                                ];
                                                            @endphp
                                                            <section class="mt-4" data-faculty-question-review>
                                                                <div class="faculty-analysis__student-response-header mb-3">
                                                                    <div class="faculty-analysis__student-response-heading">
                                                                        <h4 class="h6 fw-bold mb-1">Individual student responses</h4>
                                                                        <span class="small text-muted">{{ $studentResponseCount }} {{ $studentResponseCount === 1 ? 'student' : 'students' }} · Latest submitted attempt</span>
                                                                    </div>
                                                                    <div class="faculty-analysis__response-filter-cards" role="group" aria-label="Filter student responses by result">
                                                                        <button class="faculty-analysis__response-filter-card" type="button" data-faculty-result-filter="all" aria-pressed="true">
                                                                            <span class="faculty-analysis__response-filter-count" data-faculty-result-count>{{ $responseFilterCounts['all'] }}</span>
                                                                            <span class="faculty-analysis__response-filter-label">All</span>
                                                                        </button>
                                                                        <button class="faculty-analysis__response-filter-card" type="button" data-faculty-result-filter="correct" aria-pressed="false">
                                                                            <span class="faculty-analysis__response-filter-count" data-faculty-result-count>{{ $responseFilterCounts['correct'] }}</span>
                                                                            <span class="faculty-analysis__response-filter-label">Correct</span>
                                                                        </button>
                                                                        <button class="faculty-analysis__response-filter-card" type="button" data-faculty-result-filter="incorrect" aria-pressed="false">
                                                                            <span class="faculty-analysis__response-filter-count" data-faculty-result-count>{{ $responseFilterCounts['incorrect'] }}</span>
                                                                            <span class="faculty-analysis__response-filter-label">Incorrect</span>
                                                                        </button>
                                                                        <button class="faculty-analysis__response-filter-card" type="button" data-faculty-result-filter="unanswered" aria-pressed="false">
                                                                            <span class="faculty-analysis__response-filter-count" data-faculty-result-count>{{ $responseFilterCounts['unanswered'] }}</span>
                                                                            <span class="faculty-analysis__response-filter-label">Unanswered</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="table-responsive border rounded-3 faculty-analysis__student-responses" role="region" aria-label="Individual student responses" tabindex="0">
                                                                    <table class="table table-sm table-hover align-middle mb-0 faculty-analysis__student-table">
                                                                        <thead>
                                                                            <tr><th scope="col">Student</th><th scope="col">Selected answer</th><th scope="col">Result</th></tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($row['student_responses'] as $studentResponse)
                                                                                <tr
                                                                                    data-student-answer="{{ $studentResponse['answer'] }}"
                                                                                    data-student-result="{{ $studentResponse['is_correct'] ? 'correct' : ($studentResponse['answer'] === 'Unanswered' ? 'unanswered' : 'incorrect') }}"
                                                                                >
                                                                                    <td>{{ $studentResponse['student_name'] }}</td>
                                                                                    <td>{{ $studentResponse['answer'] }}</td>
                                                                                    <td>
                                                                                        @if ($studentResponse['is_correct'])
                                                                                            <span class="badge text-bg-success">Correct</span>
                                                                                        @else
                                                                                            <span class="badge text-bg-danger">{{ $studentResponse['answer'] === 'Unanswered' ? 'Unanswered' : 'Incorrect' }}</span>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                            <tr data-faculty-response-empty hidden>
                                                                                <td colspan="3" class="small text-muted text-center py-3" role="status">No students match these filters.</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <p class="visually-hidden" data-faculty-response-filter-status aria-live="polite">Showing all student responses.</p>
                                                            </section>
                                                        @endif
                                                        <div class="mt-3">
                                                            @if ($selectedTest->course_id && $row['topic_id'])
                                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.question-bank.index', ['course_id' => $selectedTest->course_id, 'topic_id' => $row['topic_id']]) }}">
                                                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Review related Question Bank topic
                                                                </a>
                                                            @else
                                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.question-bank.index', ['course_id' => $selectedTest->course_id]) }}">
                                                                    <i class="bi bi-journal-text me-1" aria-hidden="true"></i>Open Question Bank
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="faculty-analysis__empty text-muted">
                                    <i class="bi bi-bar-chart-line fs-3 d-block mb-2" aria-hidden="true"></i>
                                    @if ($summary['question_count'] === 0)
                                        No question-level responses are recorded for this examination yet.
                                    @else
                                        No questions match these filters.
                                        <a href="{{ route('admin.item-analysis.index', ['test_id' => $selectedTest->id]) }}">Clear filters</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-faculty-question-review]').forEach((responsePanel) => {
            const answerButtons = Array.from(responsePanel.closest('.modal')?.querySelectorAll('[data-faculty-answer-filter]') ?? []);
            const resultButtons = Array.from(responsePanel.querySelectorAll('[data-faculty-result-filter]'));
            const studentRows = Array.from(responsePanel.querySelectorAll('[data-student-result]'));
            const emptyMessage = responsePanel.querySelector('[data-faculty-response-empty]');
            const filterStatus = responsePanel.querySelector('[data-faculty-response-filter-status]');

            if (studentRows.length === 0 || resultButtons.length === 0) {
                return;
            }

            let selectedAnswer = null;
            let selectedResult = 'all';

            const updateResponses = () => {
                const normalizedAnswer = selectedAnswer?.trim().toLowerCase() ?? null;
                const answerMatchingRows = studentRows.filter((studentRow) => {
                    const rowAnswer = studentRow.dataset.studentAnswer?.trim().toLowerCase() ?? '';

                    return normalizedAnswer === null || rowAnswer === normalizedAnswer;
                });
                let visibleCount = 0;

                studentRows.forEach((studentRow) => {
                    const rowAnswer = studentRow.dataset.studentAnswer?.trim().toLowerCase() ?? '';
                    const matchesAnswer = normalizedAnswer === null || rowAnswer === normalizedAnswer;
                    const matchesResult = selectedResult === 'all' || studentRow.dataset.studentResult === selectedResult;
                    const isVisible = matchesAnswer && matchesResult;

                    studentRow.hidden = !isVisible;
                    visibleCount += isVisible ? 1 : 0;
                });

                if (emptyMessage) {
                    emptyMessage.hidden = visibleCount > 0;
                }

                answerButtons.forEach((button) => {
                    const isSelected = selectedAnswer === button.dataset.facultyAnswerFilter;

                    button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                });

                resultButtons.forEach((button) => {
                    const resultFilter = button.dataset.facultyResultFilter ?? 'all';
                    const isSelected = selectedResult === resultFilter;
                    const resultCount = resultFilter === 'all'
                        ? answerMatchingRows.length
                        : answerMatchingRows.filter((studentRow) => studentRow.dataset.studentResult === resultFilter).length;
                    const countElement = button.querySelector('[data-faculty-result-count]');

                    button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                    if (countElement) {
                        countElement.textContent = String(resultCount);
                    }
                });

                const resultLabel = selectedResult === 'all' ? '' : `${selectedResult} `;
                const answerLabel = selectedAnswer === null ? '' : ` for "${selectedAnswer}"`;

                if (filterStatus) {
                    filterStatus.textContent = `Showing ${visibleCount} ${resultLabel}response${visibleCount === 1 ? '' : 's'}${answerLabel}.`;
                }
            };

            answerButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    selectedAnswer = selectedAnswer === button.dataset.facultyAnswerFilter
                        ? null
                        : button.dataset.facultyAnswerFilter;
                    updateResponses();
                });
            });

            resultButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    selectedResult = button.dataset.facultyResultFilter ?? 'all';
                    updateResponses();
                });
            });
        });

        const programInput = document.getElementById('faculty-program');
        const programLabel = document.querySelector('[data-faculty-program-label]');
        const examPicker = document.getElementById('faculty-exam-picker');
        const examInput = document.getElementById('faculty-exam');
        const examLabel = document.querySelector('[data-faculty-exam-label]');
        const examForm = examPicker?.closest('form');
        const examOptions = Array.from(document.querySelectorAll('[data-faculty-exam-option]'));
        const emptyExamOption = document.querySelector('[data-faculty-exam-empty]');

        if (!programInput || !programLabel || !examPicker || !examInput || !examLabel || !examForm) {
            return;
        }

        const setSelectedOption = (options, selectedOption) => {
            options.forEach((option) => {
                const selected = option === selectedOption;
                option.classList.toggle('active', selected);
                option.setAttribute('aria-selected', selected ? 'true' : 'false');
                option.querySelector('[data-faculty-option-check]')?.classList.toggle('d-none', !selected);
            });
        };

        const updateExamOptions = () => {
            const selectedProgram = programInput.value;
            let selectedExamOption = null;
            let hasProgramExams = false;

            examOptions.forEach((option) => {
                const isVisible = selectedProgram !== '' && option.dataset.courseId === selectedProgram;
                option.closest('li')?.classList.toggle('d-none', !isVisible);
                hasProgramExams = hasProgramExams || isVisible;

                if (isVisible && option.dataset.value === examInput.value) {
                    selectedExamOption = option;
                }
            });

            if (selectedExamOption === null) {
                examInput.value = '';
                examLabel.textContent = selectedProgram === ''
                    ? 'Choose a program first'
                    : (hasProgramExams ? 'Choose an examination' : 'No examinations for this program');
            } else {
                examLabel.textContent = selectedExamOption.dataset.label;
            }

            examPicker.disabled = selectedProgram === '' || !hasProgramExams;
            if (emptyExamOption) {
                emptyExamOption.classList.toggle('d-none', hasProgramExams);
                emptyExamOption.querySelector('.dropdown-item-text').textContent = selectedProgram === ''
                    ? 'Choose a program first'
                    : 'No examinations are available for this program';
            }

            setSelectedOption(examOptions, selectedExamOption);
        };

        const programOptions = Array.from(document.querySelectorAll('[data-faculty-program-option]'));
        programOptions.forEach((option) => {
            option.addEventListener('click', () => {
                programInput.value = option.dataset.value;
                programLabel.textContent = option.dataset.label;
                setSelectedOption(programOptions, option);
                updateExamOptions();
            });
        });

        examOptions.forEach((option) => {
            option.addEventListener('click', () => {
                if (examInput.value === option.dataset.value) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('selectFacultyExamModal')).hide();

                    return;
                }

                examInput.value = option.dataset.value;
                programInput.value = option.dataset.courseId;
                examLabel.textContent = option.dataset.label;
                updateExamOptions();
                examForm.requestSubmit();
            });
        });

        updateExamOptions();
    });
</script>
@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('selectFacultyExamModal')).show();
        });
    </script>
@endif
@endsection
