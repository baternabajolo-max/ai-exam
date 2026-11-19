@extends('layouts.app')

@section('content')
<style>
    .student-exams__header {
        align-items: center;
        background:
            radial-gradient(circle at 92% 10%, color-mix(in srgb, var(--student-theme, #2563eb) 9%, transparent) 0 5rem, transparent 5.1rem),
            linear-gradient(125deg, #fff 0%, color-mix(in srgb, var(--student-theme, #2563eb) 7%, #fff) 100%);
        border: 1px solid color-mix(in srgb, var(--student-theme, #2563eb) 18%, #dbe5f1);
        border-radius: 1.25rem;
        box-shadow: 0 .9rem 2rem rgb(15 23 42 / 7%);
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        overflow: hidden;
        padding: clamp(1rem, 2vw, 1.5rem);
        position: relative;
    }

    .student-exams__header::after {
        border: 1px solid color-mix(in srgb, var(--student-theme, #2563eb) 12%, transparent);
        border-radius: 50%;
        content: "";
        height: 14rem;
        pointer-events: none;
        position: absolute;
        right: -6rem;
        top: -8rem;
        width: 14rem;
    }

    .student-exams__header-main,
    .student-exams__header-aside {
        position: relative;
        z-index: 1;
    }

    .student-exams__header-main {
        max-width: 46rem;
    }

    .student-exams__header-aside {
        align-items: flex-end;
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .student-exams__summary {
        align-items: center;
        background: rgb(255 255 255 / 78%);
        border: 1px solid rgb(255 255 255 / 80%);
        border-radius: .9rem;
        box-shadow: 0 .35rem .9rem rgb(15 23 42 / 5%);
        display: flex;
        gap: .75rem;
        min-height: 3.5rem;
        padding: .65rem .85rem;
    }

    .student-exams__summary-icon {
        align-items: center;
        background: color-mix(in srgb, var(--student-theme, #2563eb) 11%, white);
        border: 1px solid color-mix(in srgb, var(--student-theme, #2563eb) 20%, white);
        border-radius: .7rem;
        color: var(--student-theme, #2563eb);
        display: inline-flex;
        flex: 0 0 2.25rem;
        height: 2.25rem;
        justify-content: center;
        width: 2.25rem;
    }

    .student-exams__eyebrow {
        color: var(--student-theme, #2563eb);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .student-exams__card {
        background: linear-gradient(145deg, #fff, #fbfdff);
        border: 1px solid #dbe5f1;
        border-radius: 1rem;
        box-shadow: 0 .45rem 1.1rem rgb(15 23 42 / 5%);
        height: 100%;
        overflow: hidden;
        position: relative;
        transition: border-color .22s ease, box-shadow .22s ease, transform .22s ease;
    }

    .student-exams__card::before {
        background: var(--student-theme, #2563eb);
        content: "";
        inset: 0 auto 0 0;
        opacity: .9;
        position: absolute;
        width: .3rem;
    }

    .student-exams__card:hover {
        border-color: color-mix(in srgb, var(--student-theme, #2563eb) 35%, #dbe5f1);
        box-shadow: 0 1rem 2rem rgb(15 23 42 / 11%);
        transform: translateY(-4px);
    }

    .student-exams__card-body {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: clamp(1rem, 1.6vw, 1.25rem);
        padding-left: clamp(1.25rem, 2vw, 1.5rem);
    }

    .student-exams__status {
        border: 1px solid transparent;
        font-size: .73rem;
        font-weight: 700;
        letter-spacing: .01em;
        padding: .45rem .7rem;
    }

    .student-exams__attempt {
        align-items: center;
        display: inline-flex;
        gap: .3rem;
        white-space: nowrap;
    }

    .student-exams__status--active {
        background: #dcfce7;
        border-color: #bbf7d0;
        color: #166534;
    }

    .student-exams__status--scheduled {
        background: #fef3c7;
        border-color: #fde68a;
        color: #92400e;
    }

    .student-exams__status--closed {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #475569;
    }

    .student-exams__status--completed {
        background: #dbeafe;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .student-exams__status--retake {
        background: #ede9fe;
        border-color: #ddd6fe;
        color: #5b21b6;
    }

    .student-exams__title {
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .student-exams__description {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        min-height: 1.4em;
        overflow: hidden;
    }

    .student-exams__details {
        background: linear-gradient(145deg, #f8fafc, #f3f7fc);
        border: 1px solid #e2eaf3;
        border-radius: .9rem;
        display: grid;
        gap: .4rem;
        margin: 0 0 .9rem;
        padding: .7rem .85rem;
    }

    .student-exams__detail {
        align-items: baseline;
        display: flex;
        flex-wrap: wrap;
        gap: .25rem .4rem;
    }

    .student-exams__detail dt {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        min-width: 5.5rem;
    }

    .student-exams__detail dd {
        color: #1e293b;
        font-size: .85rem;
        font-weight: 600;
        margin: 0;
    }

    .student-exams__action {
        --bs-btn-border-radius: .7rem;
        align-items: center;
        display: inline-flex;
        gap: .4rem;
        justify-content: center;
        min-height: 2.5rem;
    }

    .student-exams__empty {
        background: linear-gradient(145deg, #fff, #fbfdff);
        border: 1px dashed #cbd5e1;
        border-radius: 1rem;
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .student-exams__empty-icon {
        align-items: center;
        background: color-mix(in srgb, var(--student-theme, #2563eb) 10%, white);
        border-radius: 1rem;
        color: var(--student-theme, #2563eb);
        display: inline-flex;
        font-size: 1.5rem;
        height: 3.5rem;
        justify-content: center;
        margin-bottom: 1rem;
        width: 3.5rem;
    }

    .student-exams__notice {
        align-items: flex-start;
        background: color-mix(in srgb, var(--student-theme, #2563eb) 8%, white);
        border: 1px solid color-mix(in srgb, var(--student-theme, #2563eb) 18%, white);
        border-radius: .85rem;
        color: #334155;
        display: flex;
        gap: .5rem;
        max-width: 24rem;
        padding: .9rem 1rem;
    }

    .student-exams__tabs-shell {
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--student-theme, #2563eb) 14%, #dbe5f1);
        border-radius: 999px;
        box-shadow: 0 .35rem .9rem rgb(15 23 42 / 7%);
        display: inline-flex;
        margin-top: 1.1rem;
        max-width: 100%;
        padding: .35rem;
    }

    .student-exams__tabs {
        flex-wrap: nowrap;
        gap: .25rem;
    }

    .student-exams__tabs .student-exams__tab {
        align-items: center;
        border-radius: 999px;
        color: #475569;
        display: inline-flex;
        font-weight: 700;
        gap: .55rem;
        justify-content: center;
        min-height: 2.85rem;
        padding: .55rem 1rem;
        transition: background-color .18s ease, box-shadow .18s ease, color .18s ease;
        white-space: nowrap;
    }

    .student-exams__tabs .student-exams__tab:hover:not(.active) {
        background: color-mix(in srgb, var(--student-theme, #2563eb) 8%, white);
        color: var(--student-theme, #2563eb);
    }

    .student-exams__tabs .student-exams__tab.active {
        background: var(--student-theme, #2563eb);
        box-shadow: 0 .35rem .8rem color-mix(in srgb, var(--student-theme, #2563eb) 28%, transparent);
        color: #fff;
    }

    .student-exams__tab-count {
        align-items: center;
        background: #e2e8f0;
        border-radius: 999px;
        color: #334155;
        display: inline-flex;
        font-size: .75rem;
        font-variant-numeric: tabular-nums;
        height: 1.45rem;
        justify-content: center;
        min-width: 1.45rem;
        padding: 0 .35rem;
    }

    .student-exams__tab.active .student-exams__tab-count {
        background: rgb(255 255 255 / 22%);
        color: #fff;
    }

    .student-exams__program {
        align-items: center;
        color: var(--student-theme, #2563eb);
        display: inline-flex;
        font-size: .82rem;
        font-weight: 700;
        gap: .4rem;
        margin-bottom: .65rem;
    }

    .student-exams__score {
        font-size: 1rem;
        font-variant-numeric: tabular-nums;
        font-weight: 800;
    }

    html[data-bs-theme="dark"] .student-exams__header {
        background: linear-gradient(125deg, #1e293b, #172033);
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .student-exams__summary {
        background: #172033;
        border-color: #334155;
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .student-exams__summary-icon {
        background: color-mix(in srgb, var(--student-theme, #2563eb) 22%, #1e293b);
        border-color: color-mix(in srgb, var(--student-theme, #2563eb) 38%, #1e293b);
    }

    html[data-bs-theme="dark"] .student-exams__tabs-shell {
        background: #172033;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .student-exams__tabs .student-exams__tab {
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .student-exams__tabs .student-exams__tab:hover:not(.active) {
        background: #1e293b;
        color: #bfdbfe;
    }

    html[data-bs-theme="dark"] .student-exams__tabs .student-exams__tab.active {
        color: #fff;
    }

    html[data-bs-theme="dark"] .student-exams__tab-count {
        background: #334155;
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .student-exams__program {
        color: #93c5fd;
    }

    html[data-bs-theme="dark"] .student-exams__card,
    html[data-bs-theme="dark"] .student-exams__empty {
        background: linear-gradient(145deg, #1e293b, #172033);
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .student-exams__details {
        background: #172033;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .student-exams__detail dt {
        color: #94a3b8;
    }

    html[data-bs-theme="dark"] .student-exams__detail dd,
    html[data-bs-theme="dark"] .student-exams__notice {
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .student-exams__status--active {
        background: #123b2b;
        border-color: #166534;
        color: #bbf7d0;
    }

    html[data-bs-theme="dark"] .student-exams__status--scheduled {
        background: #422006;
        border-color: #854d0e;
        color: #fde68a;
    }

    html[data-bs-theme="dark"] .student-exams__status--closed {
        background: #273449;
        border-color: #475569;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .student-exams__status--completed {
        background: #172554;
        border-color: #1d4ed8;
        color: #bfdbfe;
    }

    html[data-bs-theme="dark"] .student-exams__status--retake {
        background: #2e1065;
        border-color: #5b21b6;
        color: #ddd6fe;
    }

    html[data-bs-theme="dark"] .student-exams__notice {
        background: color-mix(in srgb, var(--student-theme, #2563eb) 18%, #1e293b);
        border-color: color-mix(in srgb, var(--student-theme, #2563eb) 38%, #1e293b);
    }

    @media (max-width: 767.98px) {
        .student-exams__header {
            position: static;
        }

        .student-exams__header-aside {
            align-items: stretch;
            width: 100%;
        }

        .student-exams__notice {
            max-width: none;
        }
    }

    @media (min-width: 768px) {
        .student-exams__header {
            position: sticky;
            top: 4.25rem;
            z-index: 1010;
        }
    }

    @media (max-width: 575.98px) {
        .student-exams__tabs-shell {
            display: flex;
            width: 100%;
        }

        .student-exams__tabs {
            width: 100%;
        }

        .student-exams__tabs .student-exams__tab {
            flex: 1 1 50%;
            gap: .35rem;
            padding-inline: .5rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .student-exams__card {
            transition: none;
        }

        .student-exams__card:hover {
            transform: none;
        }
    }
</style>

<main class="container student-exams">
    <header class="student-exams__header">
        <div class="student-exams__header-main">
            <div class="student-exams__eyebrow mb-2">Your assessments</div>
            <h1 class="h2 fw-bold mb-1">Examinations</h1>
            <p class="text-muted mb-0">Review upcoming deadlines, start an available exam, or revisit results from exams you have completed.</p>
            <div class="student-exams__tabs-shell">
                <ul class="nav nav-pills student-exams__tabs" id="studentExamTabs" role="tablist" aria-label="Examinations by completion status">
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link student-exams__tab active"
                            id="available-exams-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#available-exams-panel"
                            type="button"
                            role="tab"
                            aria-controls="available-exams-panel"
                            aria-selected="true"
                        >
                            <i class="bi bi-play-circle" aria-hidden="true"></i>
                            <span>Available Exams</span>
                            <span class="student-exams__tab-count">{{ $availableExams->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link student-exams__tab"
                            id="taken-exams-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#taken-exams-panel"
                            type="button"
                            role="tab"
                            aria-controls="taken-exams-panel"
                            aria-selected="false"
                        >
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <span>Taken Exams</span>
                            <span class="student-exams__tab-count">{{ $takenExams->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <aside class="student-exams__header-aside" aria-label="Examination schedule summary">
            <div class="student-exams__summary">
                <span class="student-exams__summary-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                <div>
                    <span class="small text-muted d-block">Your progress</span>
                    <strong>{{ $availableExams->count() }} available · {{ $takenExams->count() }} taken</strong>
                </div>
            </div>
            <div class="student-exams__notice small">
                <i class="bi bi-info-circle-fill mt-1" aria-hidden="true"></i>
                <span>Check each exam's due date and time limit before you begin.</span>
            </div>
        </aside>
    </header>

    <div class="tab-content" id="studentExamTabsContent">
        <section class="tab-pane fade show active" id="available-exams-panel" role="tabpanel" aria-labelledby="available-exams-tab" tabindex="0">
            <div class="row g-3 g-lg-4">
                @forelse ($availableExams as $exam)
                    @php($status = $exam->scheduleStatus())
                    <div class="col-12 col-sm-6 col-xl-4">
                        <article class="student-exams__card">
                            <div class="student-exams__card-body">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                    <span class="badge rounded-pill student-exams__status student-exams__status--{{ strtolower($status) }}">
                                        <i class="bi {{ $status === 'Active' ? 'bi-unlock-fill' : ($status === 'Closed' ? 'bi-lock-fill' : 'bi-clock-fill') }} me-1" aria-hidden="true"></i>
                                        {{ $status }}
                                    </span>
                                </div>
                                <h2 class="student-exams__title h4 fw-bold mb-1">{{ $exam->title }}</h2>
                                <p class="student-exams__program"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i>{{ $exam->course?->name ?? 'Program not specified' }}</p>
                                <p class="student-exams__description text-muted mb-3">{{ $exam->description ?: $exam->topic }}</p>
                                <dl class="student-exams__details small">
                                    <div class="student-exams__detail"><dt><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Due date</dt><dd>{{ $exam->available_until?->format('M j, Y g:i A') ?? 'No due date' }}</dd></div>
                                    <div class="student-exams__detail"><dt><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Opens</dt><dd>{{ $exam->available_from?->format('M j, Y g:i A') ?? 'Immediately' }}</dd></div>
                                    <div class="student-exams__detail"><dt><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Time limit</dt><dd>{{ $exam->time_limit ?? $exam->duration_minutes }} minutes</dd></div>
                                </dl>
                                @if ($status === 'Active')
                                    <button
                                        class="btn btn-primary student-exams__action mt-auto align-self-start"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#takeExamConfirmation"
                                        data-exam-start-url="{{ route('student.exams.start', $exam) }}"
                                        data-exam-title="{{ $exam->title }}"
                                    ><i class="bi bi-arrow-right-circle" aria-hidden="true"></i>Take exam</button>
                                @else
                                    <button class="btn btn-outline-secondary student-exams__action mt-auto align-self-start" type="button" disabled>
                                        <i class="bi {{ $status === 'Scheduled' ? 'bi-clock' : 'bi-lock' }}" aria-hidden="true"></i>{{ $status === 'Scheduled' ? 'Not available yet' : 'Examination closed' }}
                                    </button>
                                @endif
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="student-exams__empty">
                            <span class="student-exams__empty-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                            <h2 class="h4 fw-bold">You’re all caught up</h2>
                            <p class="text-muted mb-0">
                                @if ($takenExams->isNotEmpty())
                                    You have completed all currently listed examinations. Your results are available in Taken Exams.
                                @else
                                    There are no scheduled examinations for your program right now.
                                @endif
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="tab-pane fade" id="taken-exams-panel" role="tabpanel" aria-labelledby="taken-exams-tab" tabindex="0">
            <div class="row g-3 g-lg-4">
                @forelse ($takenExams as $exam)
                    @php($completedResult = $exam->results->first())
                    @php($retakeGrant = $exam->retakeGrants->first())
                    @php($status = $exam->scheduleStatus())
                    <div class="col-12 col-sm-6 col-xl-4">
                        <article class="student-exams__card">
                            <div class="student-exams__card-body">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                    <span class="badge rounded-pill student-exams__status student-exams__status--{{ $retakeGrant ? 'retake' : 'completed' }}">
                                        <i class="bi {{ $retakeGrant ? 'bi-arrow-repeat' : 'bi-check-circle-fill' }} me-1" aria-hidden="true"></i>
                                        {{ $retakeGrant ? 'Retake available' : 'Completed' }}
                                    </span>
                                    <span class="student-exams__attempt small text-muted"><i class="bi bi-check2" aria-hidden="true"></i>Attempt submitted</span>
                                </div>
                                <h2 class="student-exams__title h4 fw-bold mb-1">{{ $exam->title }}</h2>
                                <p class="student-exams__program"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i>{{ $exam->course?->name ?? 'Program not specified' }}</p>
                                <p class="student-exams__description text-muted mb-3">{{ $exam->description ?: $exam->topic }}</p>
                                <dl class="student-exams__details small">
                                    <div class="student-exams__detail"><dt><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Date taken</dt><dd>{{ $completedResult->created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}</dd></div>
                                    <div class="student-exams__detail"><dt><i class="bi bi-award me-1" aria-hidden="true"></i>Score</dt><dd><span class="student-exams__score">{{ $completedResult->score }} / {{ $completedResult->total_questions }}</span> correct</dd></div>
                                </dl>
                                <a class="btn btn-outline-primary student-exams__action mt-auto align-self-start" href="{{ route('student.results.show', $completedResult) }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>View results</a>
                                @if ($retakeGrant && $status === 'Active')
                                    <button
                                        class="btn btn-primary student-exams__action mt-2 align-self-start"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#takeExamConfirmation"
                                        data-exam-start-url="{{ route('student.exams.start', $exam) }}"
                                        data-exam-title="{{ $exam->title }}"
                                        data-exam-retake="true"
                                    ><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Take retake</button>
                                @endif
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="student-exams__empty">
                            <span class="student-exams__empty-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                            <h2 class="h4 fw-bold">No completed exams yet</h2>
                            <p class="text-muted mb-0">After you submit an examination, its date and score will appear here.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>

<div class="modal fade" id="takeExamConfirmation" tabindex="-1" aria-labelledby="takeExamConfirmationTitle" aria-describedby="takeExamConfirmationMessage" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom-0 px-4 pt-4 pb-0">
                <div>
                    <span class="student-exams__eyebrow">Ready when you are</span>
                    <h2 class="modal-title h4 fw-bold mt-1" id="takeExamConfirmationTitle">Before you begin</h2>
                </div>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="student-exams__notice mb-3"><i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i><span id="takeExamName" class="fw-semibold"></span></div>
                <p class="text-secondary mb-0" id="takeExamConfirmationMessage">
                    <span id="takeExamNotice">You can take this examination only once. Proceed to begin; your attempt will not be submitted until you finish, time expires, or you close or leave the examination.</span>
                </p>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="startExamForm" method="POST" action="#" target="_blank">
                    @csrf
                    <button id="proceedToExam" class="btn btn-primary rounded-pill px-4" type="submit">Proceed</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const confirmationModal = document.getElementById('takeExamConfirmation');
    const examName = document.getElementById('takeExamName');
    const examNotice = document.getElementById('takeExamNotice');
    const startExamForm = document.getElementById('startExamForm');

    startExamForm.addEventListener('submit', function () {
        bootstrap.Modal.getOrCreateInstance(confirmationModal).hide();
    });

    confirmationModal.addEventListener('show.bs.modal', function (event) {
        const takeExamButton = event.relatedTarget;
        if (!(takeExamButton instanceof HTMLElement)) {
            return;
        }

        examName.textContent = takeExamButton.dataset.examTitle || 'Examination';
        startExamForm.action = takeExamButton.dataset.examStartUrl || '#';
        examNotice.textContent = takeExamButton.dataset.examRetake === 'true'
            ? 'This is your one additional attempt. It will be submitted when you finish, time expires, or you close or leave the examination.'
            : 'You can take this examination only once. Proceed to begin; your attempt will not be submitted until you finish, time expires, or you close or leave the examination.';
    });

    if ('BroadcastChannel' in window) {
        const examUpdates = new BroadcastChannel('lemu-exam-updates');
        examUpdates.addEventListener('message', function (event) {
            if (event.data?.type === 'exam-submitted') {
                window.location.reload();
            }
        });
    }
});
</script>
@endsection
