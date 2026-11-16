@extends('layouts.app')

@section('content')
<style>
    .student-exams__header {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: 1.5rem;
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
        box-shadow: 0 .75rem 1.5rem rgb(15 23 42 / 7%);
        height: 100%;
        overflow: hidden;
        position: relative;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .student-exams__card::before {
        background: var(--student-theme, #2563eb);
        content: "";
        inset: 0 auto 0 0;
        opacity: .8;
        position: absolute;
        width: .25rem;
    }

    .student-exams__card:hover {
        border-color: color-mix(in srgb, var(--student-theme, #2563eb) 35%, #dbe5f1);
        box-shadow: 0 1rem 2rem rgb(15 23 42 / 10%);
        transform: translateY(-3px);
    }

    .student-exams__card-body {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 1.25rem;
        padding-left: 1.5rem;
    }

    .student-exams__status {
        border: 1px solid transparent;
        font-size: .73rem;
        font-weight: 700;
        letter-spacing: .01em;
        padding: .45rem .7rem;
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

    .student-exams__title {
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .student-exams__description {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        min-height: 2.8em;
        overflow: hidden;
    }

    .student-exams__details {
        background: #f8fafc;
        border: 1px solid #e8eef5;
        border-radius: .8rem;
        display: grid;
        gap: .65rem;
        margin: 0 0 1.1rem;
        padding: .9rem 1rem;
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
        min-width: 5rem;
    }

    .student-exams__detail dd {
        color: #1e293b;
        font-size: .85rem;
        margin: 0;
    }

    .student-exams__action {
        --bs-btn-border-radius: .7rem;
        align-items: center;
        display: inline-flex;
        gap: .4rem;
        justify-content: center;
        min-height: 2.75rem;
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
        background: color-mix(in srgb, var(--student-theme, #2563eb) 8%, white);
        border: 1px solid color-mix(in srgb, var(--student-theme, #2563eb) 18%, white);
        border-radius: .85rem;
        color: #334155;
        padding: .9rem 1rem;
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

    html[data-bs-theme="dark"] .student-exams__notice {
        background: color-mix(in srgb, var(--student-theme, #2563eb) 18%, #1e293b);
        border-color: color-mix(in srgb, var(--student-theme, #2563eb) 38%, #1e293b);
    }

    @media (prefers-reduced-motion: reduce) {
        .student-exams__card {
            transition: none;
        }
    }
</style>

<main class="container student-exams">
    <header class="student-exams__header">
        <div>
            <div class="student-exams__eyebrow mb-2">Your assessments</div>
            <h1 class="h2 fw-bold mb-1">Examinations</h1>
            <p class="text-muted mb-0">Scheduled examinations for your program. Each examination can be taken once.</p>
        </div>
        <div class="student-exams__notice small">
            <i class="bi bi-info-circle-fill me-1" aria-hidden="true"></i>
            Review the schedule and time limit before you begin.
        </div>
    </header>

    <div class="row g-3 g-lg-4">
        @forelse ($exams as $exam)
            @php($status = $exam->scheduleStatus())
            @php($completedResult = $exam->results->first())
            <div class="col-12 col-lg-6">
                <article class="student-exams__card">
                    <div class="student-exams__card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <span class="badge rounded-pill student-exams__status student-exams__status--{{ $completedResult ? 'completed' : strtolower($status) }}">
                                <i class="bi {{ $completedResult ? 'bi-check-circle-fill' : ($status === 'Active' ? 'bi-unlock-fill' : ($status === 'Closed' ? 'bi-lock-fill' : 'bi-clock-fill')) }} me-1" aria-hidden="true"></i>
                                {{ $completedResult ? 'Completed' : $status }}
                            </span>
                            @if ($completedResult)<span class="small text-muted"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Attempt submitted</span>@endif
                        </div>
                        <h2 class="student-exams__title h4 fw-bold mb-1">{{ $exam->title }}</h2>
                        <p class="student-exams__description text-muted mb-3">{{ $exam->description ?: $exam->topic }}</p>
                        <dl class="student-exams__details small">
                            <div class="student-exams__detail"><dt><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Opens</dt><dd>{{ $exam->available_from?->format('M j, Y g:i A') ?? 'Immediately' }}</dd></div>
                            @if ($exam->available_until)<div class="student-exams__detail"><dt><i class="bi bi-calendar-x me-1" aria-hidden="true"></i>Closes</dt><dd>{{ $exam->available_until->format('M j, Y g:i A') }}</dd></div>@endif
                            <div class="student-exams__detail"><dt><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Time limit</dt><dd>{{ $exam->time_limit ?? $exam->duration_minutes }} minutes</dd></div>
                        </dl>
                        @if ($completedResult)
                            <a class="btn btn-outline-primary student-exams__action mt-auto align-self-start" href="{{ route('student.results.show', $completedResult) }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>View results</a>
                        @elseif ($status === 'Active')
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
                    <span class="student-exams__empty-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                    <h2 class="h4 fw-bold">You’re all caught up</h2>
                    <p class="text-muted mb-0">There are no scheduled examinations for your program right now.</p>
                </div>
            </div>
        @endforelse
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
                    You can take this examination only once. Proceed to begin; your attempt will not be submitted until you finish, time expires, or you close or leave the examination.
                </p>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="startExamForm" method="POST" action="#">
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
    const startExamForm = document.getElementById('startExamForm');

    confirmationModal.addEventListener('show.bs.modal', function (event) {
        const takeExamButton = event.relatedTarget;
        if (!(takeExamButton instanceof HTMLElement)) {
            return;
        }

        examName.textContent = takeExamButton.dataset.examTitle || 'Examination';
        startExamForm.action = takeExamButton.dataset.examStartUrl || '#';
    });
});
</script>
@endsection
