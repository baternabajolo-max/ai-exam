@extends('layouts.app')

@section('content')
<style>
    .admin-analysis__hero {
        background:
            radial-gradient(circle at 90% 15%, rgb(255 255 255 / 13%) 0 4.5rem, transparent 4.6rem),
            linear-gradient(125deg, #102a56 0%, #2948a3 58%, #5143a8 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.15rem;
        box-shadow: 0 1rem 2rem rgb(35 54 115 / 16%);
        color: #fff;
        padding: clamp(1.25rem, 3vw, 2rem);
    }

    .admin-analysis__eyebrow {
        color: #c7d2fe;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .admin-analysis__filter-bar,
    .admin-analysis__table-wrap {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 .65rem 1.4rem rgb(15 23 42 / 6%);
        overflow: hidden;
    }

    .admin-analysis__applied-filter {
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

    .admin-analysis__modal {
        border: 0;
        border-radius: 1.1rem;
        max-height: min(90dvh, 48rem);
        overflow: hidden;
    }

    .admin-analysis__modal .modal-header {
        background: linear-gradient(145deg, #f8faff, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        padding: 1.35rem 1.5rem;
    }

    .admin-analysis__modal .modal-body {
        overflow-y: auto;
        padding: 1.5rem;
    }

    .admin-analysis__modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }

    .admin-analysis__filter-icon {
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

    .admin-analysis__field {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        height: 100%;
        padding: 1rem;
    }

    .admin-analysis__table {
        --bs-table-bg: #fff;
        --bs-table-color: #1e293b;
        min-width: 780px;
    }

    .admin-analysis__table thead th {
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

    .admin-analysis__table tbody td {
        border-color: #eef2f7;
        padding: 1rem;
    }

    .admin-analysis__table tbody tr.is-difficult > td {
        background: #fffbeb;
    }

    .admin-analysis__question {
        min-width: 16rem;
        overflow-wrap: anywhere;
    }

    .admin-analysis__percent {
        color: #64748b;
        font-size: .8rem;
        white-space: nowrap;
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
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        gap: .55rem;
        justify-content: space-between;
        padding: .5rem 0;
    }

    .analysis-response-swatch {
        border-radius: 50%;
        flex: 0 0 .75rem;
        height: .75rem;
        width: .75rem;
    }

    .admin-analysis__review {
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

    .admin-analysis__review:hover,
    .admin-analysis__review:focus-visible {
        background: #fde68a;
        box-shadow: 0 0 0 .2rem rgb(245 158 11 / 20%);
    }

    .admin-analysis__insight {
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

    .admin-analysis__insight:hover,
    .admin-analysis__insight:focus-visible {
        background: #e0e7ff;
        box-shadow: 0 0 0 .2rem rgb(99 102 241 / 18%);
    }

    .admin-analysis__empty {
        padding: 2.75rem 1.25rem !important;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .admin-analysis__table {
            min-width: 0;
        }

        .admin-analysis__table thead {
            display: none;
        }

        .admin-analysis__table tbody tr:not(:has(> td[colspan])) {
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: .1rem;
            padding: .5rem;
        }

        .admin-analysis__table tbody tr:last-child {
            border-bottom: 0;
        }

        .admin-analysis__table tbody td {
            align-items: start;
            border: 0;
            display: grid;
            gap: .75rem;
            grid-template-columns: 8.5rem minmax(0, 1fr);
            padding: .55rem .65rem;
            text-align: right;
        }

        .admin-analysis__table tbody td::before {
            color: #64748b;
            content: attr(data-label);
            font-size: .7rem;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
        }

        .admin-analysis__question {
            min-width: 0;
        }

        .admin-analysis__modal .modal-header,
        .admin-analysis__modal .modal-body,
        .admin-analysis__modal .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .admin-analysis__modal .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .admin-analysis__modal .modal-footer .btn {
            width: 100%;
        }
    }

    html[data-bs-theme="dark"] .admin-analysis__filter-bar,
    html[data-bs-theme="dark"] .admin-analysis__table-wrap,
    html[data-bs-theme="dark"] .admin-analysis__field {
        background: #1e293b;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .admin-analysis__applied-filter,
    html[data-bs-theme="dark"] .admin-analysis__filter-icon {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .admin-analysis__modal .modal-header,
    html[data-bs-theme="dark"] .admin-analysis__modal .modal-footer {
        background: #172033;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .admin-analysis__table {
        --bs-table-bg: #1e293b;
        --bs-table-color: #e2e8f0;
        --bs-table-hover-bg: #273449;
        --bs-table-hover-color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .admin-analysis__table thead th {
        background: #172033;
        border-color: #334155;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .admin-analysis__table tbody td,
    html[data-bs-theme="dark"] .admin-analysis__table tbody tr {
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .admin-analysis__table tbody tr.is-difficult > td {
        background: #422006;
    }

    html[data-bs-theme="dark"] .admin-analysis__review {
        background: #422006;
        border-color: #854d0e;
        color: #fde68a;
    }

    html[data-bs-theme="dark"] .admin-analysis__insight {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }
</style>

<main class="container admin-analysis">
    <header class="admin-analysis__hero mb-4">
        <div class="admin-analysis__eyebrow mb-2">Assessment insights</div>
        <h1 class="h2 fw-bold mb-2">Cross-program Item Analysis</h1>
        <p class="text-white-50 mb-0">Inspect question performance from scheduled examinations across LEMU programs.</p>
    </header>

    <section class="admin-analysis__filter-bar d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4" aria-label="Item analysis filters">
        <div>
            <div class="small text-uppercase text-muted fw-bold mb-1">Current analysis</div>
            @if ($selectedTest)
                <span class="admin-analysis__applied-filter"><i class="bi bi-clipboard-data" aria-hidden="true"></i>{{ $selectedTest->course->name ?? 'Program' }} · {{ $selectedTest->title }}</span>
            @elseif ($selectedCourseId)
                <span class="admin-analysis__applied-filter"><i class="bi bi-mortarboard" aria-hidden="true"></i>{{ $courses->firstWhere('id', $selectedCourseId)?->name ?? 'Selected program' }}</span>
            @else
                <span class="admin-analysis__applied-filter"><i class="bi bi-globe2" aria-hidden="true"></i>All programs</span>
            @endif
        </div>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#selectAdminAnalysisModal">
            <i class="bi bi-sliders me-1" aria-hidden="true"></i> Choose Program and Examination
        </button>
    </section>

    <div class="modal fade" id="selectAdminAnalysisModal" tabindex="-1" aria-labelledby="select-admin-analysis-heading" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content admin-analysis__modal shadow-lg">
                <form method="GET" action="{{ route('super-admin.item-analysis.index') }}">
                    <div class="modal-header">
                        <div class="d-flex align-items-start gap-3">
                            <span class="admin-analysis__filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                            <div>
                                <span class="admin-analysis__eyebrow text-primary">Explore performance</span>
                                <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="select-admin-analysis-heading">Filter Item Analysis</h2>
                                <p class="small text-muted mb-0">Choose a program first, then select an examination to inspect question performance.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="admin-analysis__field">
                                <label for="analysis-course" class="form-label fw-semibold"><i class="bi bi-mortarboard me-1 text-primary" aria-hidden="true"></i>Program</label>
                                <select id="analysis-course" name="course_id" class="form-select">
                                    <option value="">All programs</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->department->name ?? 'College' }} — {{ $course->name }}</option>
                                    @endforeach
                                </select>
                                <p class="form-text mb-0 mt-2">Select the academic program for the examination.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="admin-analysis__field">
                                <label for="analysis-exam" class="form-label fw-semibold"><i class="bi bi-journal-check me-1 text-primary" aria-hidden="true"></i>Examination</label>
                                <select id="analysis-exam" name="test_id" class="form-select" required @disabled(!$selectedCourseId || !$tests->contains('course_id', (int) $selectedCourseId))>
                                    <option value="">Choose a program first</option>
                                    @foreach ($tests as $test)
                                        <option value="{{ $test->id }}" data-course-id="{{ $test->course_id }}" @selected($selectedTest?->id === $test->id)>{{ $test->title }} ({{ $test->results_count }} result(s))</option>
                                    @endforeach
                                </select>
                                <p class="form-text mb-0 mt-2">Available examinations follow the selected program.</p>
                                </div>
                            </div>
                        </div>
                        @if ($tests->isEmpty())
                            <p class="small text-muted mt-3 mb-0">No scheduled examinations are available for this filter.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button id="analysis-submit" type="submit" class="btn btn-primary px-4" @disabled(!$selectedCourseId || !$tests->contains('course_id', (int) $selectedCourseId) || !$selectedTest)><i class="bi bi-graph-up-arrow me-1" aria-hidden="true"></i>Analyze</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($selectedTest)
        <section class="admin-analysis__filter-bar p-3 p-md-4 mb-3">
            <span class="badge rounded-pill text-bg-light border mb-2">{{ $selectedTest->course->department->name ?? 'College' }} · {{ $selectedTest->course->name ?? 'Program' }}</span>
            <h2 class="h4 fw-bold mb-1">{{ $selectedTest->title }}</h2>
            <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Results use each student's latest submitted attempt. Historical responses without question identifiers are matched by question text.</p>
        </section>
        <div class="admin-analysis__table-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 px-md-4 py-3 border-bottom">
                <div>
                    <h3 class="h5 fw-bold mb-1">Question performance</h3>
                    <p class="small text-muted mb-0">Open each question for review; questions with a high incorrect rate are flagged.</p>
                </div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ count($analysis) }} questions</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-analysis__table">
                    <thead class="table-light"><tr><th scope="col">Question</th><th scope="col">Topic</th><th scope="col">Students answered</th><th scope="col">Correct</th><th scope="col">Incorrect</th><th scope="col">Review</th></tr></thead>
                    <tbody>
                        @forelse ($analysis as $row)
                            <tr class="{{ $row['is_difficult'] ? 'is-difficult' : '' }}">
                                <td data-label="Question" class="admin-analysis__question fw-semibold">{{ $row['question'] }}</td>
                                <td data-label="Topic">{{ $row['topic'] ?: '—' }}</td>
                                <td data-label="Students answered"><span class="fw-semibold">{{ $row['total'] }}</span></td>
                                <td data-label="Correct"><span class="text-success fw-bold">{{ $row['correct'] }}</span><span class="admin-analysis__percent"> ({{ $row['correct_percentage'] }}%)</span></td>
                                <td data-label="Incorrect"><span class="text-danger fw-bold">{{ $row['incorrect'] }}</span><span class="admin-analysis__percent"> ({{ $row['incorrect_percentage'] }}%)</span></td>
                                <td data-label="Review">
                                    <button
                                        @class([
                                            'admin-analysis__review' => $row['is_difficult'],
                                            'admin-analysis__insight' => ! $row['is_difficult'],
                                        ])
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#adminQuestionReview{{ $loop->index }}"
                                        aria-label="Review question: {{ $row['question'] }}"
                                    >
                                        <i class="bi {{ $row['is_difficult'] ? 'bi-exclamation-circle-fill' : 'bi-graph-up' }} me-1" aria-hidden="true"></i>
                                        Review question
                                    </button>
                                    <div class="modal fade" id="adminQuestionReview{{ $loop->index }}" tabindex="-1" aria-labelledby="adminQuestionReviewTitle{{ $loop->index }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                <div class="modal-content admin-analysis__modal shadow-lg">
                                                    <div class="modal-header">
                                                        <div>
                                                            <span class="admin-analysis__eyebrow text-primary">Question review</span>
                                                            <h2 class="modal-title h5 fw-bold mt-1 mb-0" id="adminQuestionReviewTitle{{ $loop->index }}">Question {{ $loop->iteration }}</h2>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h3 class="h6 fw-semibold text-dark mb-3">{{ $row['question'] }}</h3>
                                                        <div class="alert alert-warning">
                                                            <strong class="d-block mb-1">Latest student attempts</strong>
                                                            Across {{ $row['total'] }} attempts: <strong>{{ $row['correct'] }} correct</strong> and <strong>{{ $row['incorrect'] }} incorrect</strong> ({{ $row['incorrect_percentage'] }}% incorrect).
                                                            The highlighted choice is the answer key, not an individual student's response.
                                                        </div>
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
                                                                                <span class="d-flex align-items-center gap-2 min-w-0">
                                                                                    <span class="analysis-response-swatch" style="background-color: {{ $responseColors[$responseIndex % count($responseColors)] }};" aria-hidden="true"></span>
                                                                                    <span class="text-break">{{ $response['label'] }}</span>
                                                                                    @if ($response['is_correct'])
                                                                                        <span class="badge text-bg-success">Answer key</span>
                                                                                    @endif
                                                                                </span>
                                                                                <span class="small text-muted text-nowrap">{{ $response['count'] }} {{ $response['count'] === 1 ? 'response' : 'responses' }} · {{ $response['percentage'] }}%</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </section>
                                                        @else
                                                            <p class="small text-muted">No student response distribution is available yet.</p>
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
                            <tr><td colspan="6" class="admin-analysis__empty text-muted"><i class="bi bi-bar-chart-line fs-3 d-block mb-2" aria-hidden="true"></i>No question-level responses are recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</main>
@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('selectAdminAnalysisModal')).show();
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const programSelect = document.getElementById('analysis-course');
        const examSelect = document.getElementById('analysis-exam');
        const analyzeButton = document.getElementById('analysis-submit');
        const filterExamsByProgram = () => {
            const selectedProgram = programSelect.value;
            const hasProgramExams = Array.from(examSelect.options).some((option) => (
                option.value !== '' && option.dataset.courseId === selectedProgram
            ));
            let selectedExamIsVisible = false;

            Array.from(examSelect.options).forEach((option) => {
                const isVisible = option.value === '' || option.dataset.courseId === selectedProgram;
                option.hidden = !isVisible;
                if (option.value === examSelect.value && isVisible) {
                    selectedExamIsVisible = true;
                }
            });

            if (!selectedExamIsVisible) {
                examSelect.value = '';
            }

            examSelect.disabled = selectedProgram === '' || !hasProgramExams;
            analyzeButton.disabled = examSelect.disabled || examSelect.value === '';
            examSelect.options[0].textContent = selectedProgram === ''
                ? 'Choose a program first'
                : (hasProgramExams ? 'Choose an examination' : 'No examinations for this program');
        };

        programSelect.addEventListener('change', filterExamsByProgram);
        examSelect.addEventListener('change', filterExamsByProgram);
        filterExamsByProgram();
    });
</script>
@endsection
