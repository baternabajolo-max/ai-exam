@extends('layouts.app')

@push('styles')
<style>
    .results-page {
        --results-ink: #172033;
        --results-muted: #64748b;
        --results-line: #e2e8f0;
        color: var(--results-ink);
    }

    html[data-bs-theme="dark"] .results-page {
        --results-ink: #e2e8f0;
        --results-muted: #94a3b8;
        --results-line: #334155;
    }

    .results-hero {
        background:
            radial-gradient(circle at 88% 20%, rgb(255 255 255 / 14%) 0 4rem, transparent 4.1rem),
            linear-gradient(125deg, #39101b 0%, #70253a 58%, #9b4960 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.25rem;
        box-shadow: 0 1.15rem 2.4rem rgb(57 16 27 / 18%);
        color: #fff;
        padding: clamp(1.35rem, 3vw, 2rem);
    }

    .results-eyebrow {
        color: #f5dce2;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .results-filter-card,
    .results-kpi-card,
    .results-table-card,
    .results-empty {
        background: #fff;
        border: 1px solid var(--results-line);
        border-radius: 1rem;
        box-shadow: 0 .7rem 1.6rem rgb(15 23 42 / 6%);
    }

    .results-filter-card {
        padding: 1.15rem;
    }

    .results-kpi-card {
        height: 100%;
        padding: 1.15rem;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .results-kpi-card:hover {
        box-shadow: 0 1rem 2rem rgb(15 23 42 / 10%);
        transform: translateY(-3px);
    }

    .results-kpi-label {
        color: var(--results-muted);
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .results-kpi-value {
        color: var(--results-ink);
        font-size: clamp(1.45rem, 2.5vw, 2rem);
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.15;
    }

    .results-table-wrap {
        overflow-x: auto;
    }

    .results-table {
        margin: 0;
        min-width: 1050px;
    }

    .results-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid var(--results-line);
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .07em;
        padding: .9rem .8rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .results-table tbody td {
        border-color: #edf1f5;
        padding: .9rem .8rem;
        vertical-align: middle;
    }

    html[data-bs-theme="dark"] .results-filter-card,
    html[data-bs-theme="dark"] .results-kpi-card,
    html[data-bs-theme="dark"] .results-table-card,
    html[data-bs-theme="dark"] .results-empty {
        background: #1e293b;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .results-table {
        --bs-table-bg: #1e293b;
        --bs-table-color: #e2e8f0;
        --bs-table-hover-bg: #29374a;
        --bs-table-hover-color: #f8fafc;
    }

    html[data-bs-theme="dark"] .results-table thead th {
        background: #172033;
        border-color: #334155;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .results-table tbody td {
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .results-table tbody tr[data-result-url]:hover,
    html[data-bs-theme="dark"] .results-table tbody tr[data-result-url]:focus-visible {
        background: #29374a;
    }

    html[data-bs-theme="dark"] .results-rank,
    html[data-bs-theme="dark"] .results-status--not-taken {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }

    .results-table tbody tr[data-result-url] {
        cursor: pointer;
        transition: background-color .16s ease, transform .16s ease;
    }

    .results-table tbody tr[data-result-url]:hover,
    .results-table tbody tr[data-result-url]:focus-visible {
        background: #fff8f1;
        outline: none;
    }

    .results-rank {
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        color: #334155;
        display: inline-flex;
        font-size: .78rem;
        font-weight: 800;
        justify-content: center;
        min-width: 2.8rem;
        padding: .4rem .55rem;
    }

    .results-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 800;
        padding: .4rem .65rem;
        white-space: nowrap;
    }

    .results-status--passed { background: #dcfce7; color: #166534; }
    .results-status--failed { background: #fee2e2; color: #991b1b; }
    .results-status--not-taken { background: #f1f5f9; color: #475569; }

    .results-empty {
        color: var(--results-muted);
        padding: 2.5rem 1.5rem;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .results-hero { border-radius: .9rem; }
        .results-filter-card { padding: .9rem; }
    }
</style>
@endpush

@section('content')
<main class="container-fluid px-3 px-xl-4 results-page">
    <header class="results-hero mb-4">
        <div class="results-eyebrow mb-2">Mock exam insights</div>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1 class="display-6 fw-bold mb-1">Results</h1>
                <p class="mb-0 text-white-50">Track participation, passing outcomes, and individual student performance.</p>
            </div>
            @if ($selectedTest)
                <div class="text-lg-end">
                    <span class="d-block small text-white-50">Selected examination</span>
                    <span class="fw-bold">{{ $selectedTest->title }}</span>
                </div>
            @endif
        </div>
    </header>

    <section class="results-filter-card mb-4" aria-labelledby="results-filter-heading">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width:2.35rem;height:2.35rem" aria-hidden="true">
                <i class="bi bi-funnel"></i>
            </span>
            <div>
                <h2 id="results-filter-heading" class="h6 fw-bold mb-0">Choose an examination</h2>
                <span class="small text-muted">Results are limited to programs you are authorized to review.</span>
            </div>
        </div>
        <form method="GET" action="{{ route($indexRoute) }}" id="results-selection-form" class="row g-3 align-items-end">
            @if ($studentSearch !== '')
                <input type="hidden" name="student_search" value="{{ $studentSearch }}">
            @endif
            @if ($statusFilter !== 'all')
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            @if ($isAdmin)
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label fw-semibold" for="results-college">College</label>
                    <select class="form-select" id="results-college" name="college_id">
                        <option value="">Select a college</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($selectedDepartmentId === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label fw-semibold" for="results-program">Program</label>
                <select
                    class="form-select"
                    id="results-program"
                    name="course_id"
                    @disabled($isAdmin && $selectedDepartmentId === null)
                >
                    <option value="">{{ $isAdmin && $selectedDepartmentId === null ? 'Select a college first' : 'Select a program' }}</option>
                    @foreach ($courses as $course)
                        <option
                            value="{{ $course->id }}"
                            data-college-id="{{ $course->department_id }}"
                            @selected($selectedCourseId === $course->id)
                        >{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-4">
                <label class="form-label fw-semibold" for="results-exam">Examination</label>
                <select
                    class="form-select"
                    id="results-exam"
                    name="test_id"
                    @disabled($selectedCourseId === null)
                >
                    <option value="">{{ $selectedCourseId === null ? 'Choose a program first' : 'Select an examination' }}</option>
                    @foreach ($tests as $test)
                        <option value="{{ $test->id }}" data-course-id="{{ $test->course_id }}" @selected($selectedTest?->id === $test->id)>
                            {{ $test->title }}@if($isAdmin && $test->course) — {{ $test->course->name }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </section>

    @if ($selectedTest)
        <section class="row g-3 mb-4" aria-label="Examination summary">
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="results-kpi-card">
                    <div class="results-kpi-label mb-2">Participation rate</div>
                    <div class="results-kpi-value">{{ $summary['participant_count'] }} / {{ $summary['student_count'] }}</div>
                    <div class="small text-muted mt-2">Students took the exam · {{ $summary['participation_percentage'] }}%</div>
                </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="results-kpi-card">
                    <div class="results-kpi-label mb-2">Class average score</div>
                    <div class="results-kpi-value">{{ $summary['average_percentage'] === null ? '—' : number_format($summary['average_percentage'], 1).'%' }}</div>
                    <div class="small text-muted mt-2">Across all recorded attempts</div>
                </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="results-kpi-card">
                    <div class="results-kpi-label mb-2">Passing rate</div>
                    <div class="results-kpi-value">{{ $summary['passing_count'] }} / {{ $summary['participant_count'] }}</div>
                    <div class="small text-muted mt-2">Students passed · {{ \App\Models\Test::PASSING_SCORE_PERCENTAGE }}% threshold</div>
                </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="results-kpi-card">
                    <div class="results-kpi-label mb-2">Time-out rate</div>
                    <div class="results-kpi-value">{{ $summary['timeout_count'] }}</div>
                    <div class="small text-muted mt-2">
                        {{ $summary['participant_count'] > 0 ? number_format(($summary['timeout_count'] / $summary['participant_count']) * 100, 1).'%' : '0%' }} of takers auto-submitted
                    </div>
                </article>
            </div>
        </section>

        <section class="results-table-card overflow-hidden" aria-labelledby="student-results-heading">
            <div class="border-bottom p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 id="student-results-heading" class="h5 fw-bold mb-1">Student results</h2>
                        <p class="small text-muted mb-0">Ranked by score; students without an attempt appear last.</p>
                    </div>
                    <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $studentResults->count() }} students shown</span>
                </div>
                <form method="GET" action="{{ route($indexRoute) }}" class="row g-2 align-items-end">
                    @if ($isAdmin)
                        <input type="hidden" name="college_id" value="{{ $selectedDepartmentId }}">
                    @endif
                    <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                    <input type="hidden" name="test_id" value="{{ $selectedTest->id }}">
                    <div class="col-12 col-md-7 col-lg-5">
                        <label class="visually-hidden" for="results-student-search">Search by student name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input class="form-control" id="results-student-search" type="search" name="student_search" value="{{ $studentSearch }}" placeholder="Search student name">
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label class="visually-hidden" for="results-status">Filter by result status</label>
                        <select class="form-select" id="results-status" name="status">
                            <option value="all" @selected($statusFilter === 'all')>All students</option>
                            <option value="passed" @selected($statusFilter === 'passed')>Passed</option>
                            <option value="failed" @selected($statusFilter === 'failed')>Failed</option>
                            <option value="not_taken" @selected($statusFilter === 'not_taken')>Not Taken</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button class="btn btn-outline-danger rounded-pill px-4" type="submit">Apply filters</button>
                    </div>
                </form>
            </div>

            @if ($studentResults->isEmpty())
                <div class="results-empty border-0 shadow-none">
                    <i class="bi bi-people fs-2 d-block mb-2" aria-hidden="true"></i>
                    No students match these filters.
                </div>
            @else
                <div class="results-table-wrap">
                    <table class="table table-hover align-middle results-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Rank</th>
                                <th>Student name</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Time taken</th>
                                <th>Submission type</th>
                                <th>Date taken</th>
                                <th class="text-end pe-4">Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($studentResults as $row)
                                @php
                                    $attempt = $row['attempt'];
                                    $rowUrl = $attempt
                                        ? route($studentRoute, array_merge(
                                            ['exam' => $selectedTest->id, 'student' => $row['student']->id],
                                            request()->only(['college_id', 'course_id', 'test_id', 'student_search', 'status']),
                                        ))
                                        : null;
                                    $statusLabel = $attempt ? ($row['passed'] ? 'Passed' : 'Failed') : 'Not Taken';
                                    $statusClass = $attempt ? ($row['passed'] ? 'passed' : 'failed') : 'not-taken';
                                @endphp
                                <tr
                                    @if ($rowUrl)
                                        data-result-url="{{ $rowUrl }}"
                                        tabindex="0"
                                        role="link"
                                        aria-label="View performance for {{ $row['student']->name }}"
                                    @endif
                                >
                                    <td class="ps-4"><span class="results-rank">{{ $row['rank'] === null ? '—' : '#'.$row['rank'] }}</span></td>
                                    <td>
                                        <span class="fw-bold d-block">{{ $row['student']->name }}</span>
                                        <span class="small text-muted">{{ $row['student']->email }}</span>
                                    </td>
                                    <td><span class="results-status results-status--{{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td class="fw-semibold">
                                        @if ($attempt)
                                            {{ $attempt->score }} / {{ $row['total_questions'] }}
                                            <span class="d-block small text-muted">{{ number_format($row['percentage'], 1) }}%</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $attempt ? $row['time_taken_label'].' / '.$timeLimitLabel : '—' }}</td>
                                    <td>
                                        @if ($attempt?->submission_type === 'time_expired')
                                            <span class="badge text-bg-warning">Time Expired</span>
                                        @elseif ($attempt)
                                            <span class="badge text-bg-light border">Manual</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $attempt?->created_at?->format('M j, Y · g:i A') ?? '—' }}</td>
                                    <td class="text-end pe-4">
                                        @if ($rowUrl)
                                            <a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="{{ $rowUrl }}">View Performance</a>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" disabled title="No attempt recorded">No attempt recorded</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @else
        <section class="results-empty">
            <i class="bi bi-clipboard2-data fs-1 d-block mb-3 text-danger" aria-hidden="true"></i>
            <h2 class="h5 fw-bold text-dark">Select a program and examination</h2>
            <p class="mb-0">Choose an authorized program, load its examinations, then select one to review results.</p>
        </section>
    @endif
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const collegeSelect = document.getElementById('results-college');
        const programSelect = document.getElementById('results-program');
        const examSelect = document.getElementById('results-exam');
        const selectionForm = document.getElementById('results-selection-form');
        if (!programSelect || !examSelect || !selectionForm) {
            return;
        }

        const filterPrograms = () => {
            const collegeId = collegeSelect?.value ?? '';
            let selectedProgramExists = false;
            Array.from(programSelect.options).forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }
                const visible = collegeId === '' || option.dataset.collegeId === collegeId;
                option.hidden = !visible;
                if (option.value === programSelect.value && visible) {
                    selectedProgramExists = true;
                }
            });
            if (!selectedProgramExists) {
                programSelect.value = '';
            }
            programSelect.disabled = collegeSelect !== null && collegeId === '';
            programSelect.options[0].textContent = programSelect.disabled ? 'Select a college first' : 'Select a program';
            filterExaminations();
        };

        const filterExaminations = () => {
            const courseId = programSelect.value;
            let selectedExamExists = false;
            let examCount = 0;
            Array.from(examSelect.options).forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }
                const visible = option.dataset.courseId === courseId;
                option.hidden = !visible;
                if (visible) {
                    examCount++;
                }
                if (option.value === examSelect.value && visible) {
                    selectedExamExists = true;
                }
            });
            if (!selectedExamExists) {
                examSelect.value = '';
            }
            examSelect.disabled = courseId === '' || examCount === 0;
            examSelect.options[0].textContent = courseId === ''
                ? 'Choose a program first'
                : (examCount > 0 ? 'Select an examination' : 'No examinations for this program');
        };

        collegeSelect?.addEventListener('change', () => {
            programSelect.value = '';
            filterPrograms();
        });
        programSelect.addEventListener('change', () => {
            examSelect.value = '';
            filterExaminations();
            if (programSelect.value !== '') {
                selectionForm.requestSubmit();
            }
        });
        examSelect.addEventListener('change', () => {
            filterExaminations();
            if (examSelect.value !== '') {
                selectionForm.requestSubmit();
            }
        });
        filterPrograms();

        document.querySelectorAll('tr[data-result-url]').forEach((row) => {
            const navigate = () => window.location.assign(row.dataset.resultUrl);
            row.addEventListener('click', (event) => {
                if (!event.target.closest('a, button')) {
                    navigate();
                }
            });
            row.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    navigate();
                }
            });
        });
    });
</script>
@endpush
