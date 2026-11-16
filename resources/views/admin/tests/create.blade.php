@extends('layouts.app')

@section('content')
@php($isFacultyRequest = auth()->user()->role === 'admin')
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
    .exam-builder__question-card {
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

    .exam-builder__question-card:hover {
        border-color: #a5b4fc;
        box-shadow: 0 1rem 1.75rem rgb(79 70 229 / 12%), inset 0 1px #fff;
        transform: perspective(900px) translateY(-3px) rotateX(1deg);
    }

    .exam-builder__question-card:has(.js-exam-question:checked) {
        background: linear-gradient(145deg, #eef2ff, #fff);
        border-color: #818cf8;
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

    .exam-builder__selection-count {
        min-width: 7rem;
        text-align: center;
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
    html[data-bs-theme="dark"] .exam-builder__question-card {
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

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please review the examination details and try again.</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="exam-builder__program-card mb-4" aria-labelledby="exam-setup-heading">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 p-4">
            <div>
                <span class="badge rounded-pill {{ $isFacultyRequest ? 'text-bg-warning' : 'text-bg-primary' }} mb-2">
                    {{ $isFacultyRequest ? 'Requires Admin approval' : 'Admin-only timed assessment' }}
                </span>
                <h2 class="h4 fw-bold mb-1" id="exam-setup-heading">Start with a program</h2>
                <p class="text-muted mb-0">
                    {{ $isFacultyRequest ? 'Questions will appear as soon as you choose a program. Your exam stays private until approved.' : 'Choose a program to see its questions from all Faculty banks.' }}
                </p>
            </div>
            <form id="exam-course-form" method="GET" action="{{ route($isFacultyRequest ? 'admin.tests.create' : 'super-admin.tests.create') }}" class="d-flex flex-column flex-sm-row gap-2">
                <label class="visually-hidden" for="exam-course">Academic program</label>
                <select id="exam-course" name="course_id" class="form-select form-select-lg" required>
                    <option value="">Choose a program</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->department->name }} — {{ $course->name }}</option>
                    @endforeach
                </select>
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
            </form>
        </div>
    </section>

    @if (!$selectedCourseId)
        <div class="alert alert-info rounded-4 shadow-sm" role="status">Choose a program to see its questions from every Faculty bank.</div>
    @elseif ($questionBankItems->isEmpty())
        <div class="alert alert-info rounded-4 shadow-sm" role="status">
            No Faculty has added questions to this program yet. Ask Faculty to add questions to their individual banks.
        </div>
    @else
        <section class="mb-4" aria-labelledby="program-question-bank-heading">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                <div>
                    <span class="exam-builder__eyebrow text-primary">Question collection</span>
                    <h2 class="h3 fw-bold mb-1" id="program-question-bank-heading">Questions for {{ $courses->firstWhere('id', $selectedCourseId)?->name }}</h2>
                    <p class="text-muted mb-0">All {{ $questionBankItems->count() }} questions in this program are available, including questions from your own bank and other Faculty banks.</p>
                    <p class="small text-muted mb-0 mt-1">Pick questions yourself, or choose random questions in the exam setup.</p>
                </div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $questionBankItems->count() }} available</span>
            </div>
            <div class="row g-3">
                @foreach ($questionBankItems as $item)
                    <div class="col-12 col-xl-6">
                        <label class="exam-builder__question-card d-flex gap-3 align-items-start p-3 h-100">
                            <input
                                class="form-check-input mt-1 js-exam-question"
                                type="checkbox"
                                value="{{ $item->id }}"
                                @checked(in_array($item->id, (array) old('question_bank_item_ids', [])))
                            >
                            <span class="flex-grow-1">
                                <span class="badge rounded-pill text-bg-light mb-2">{{ $item->topic ?: 'General' }}</span>
                                <span class="exam-builder__question-text d-block fw-semibold">{{ $item->question }}</span>
                                <span class="small text-muted mt-2 d-block">{{ count($item->options ?? []) }} answer choices · {{ $item->author->name ?? 'Former Faculty' }}'s bank</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-end mt-3">
                <span class="badge rounded-pill text-bg-primary px-3 py-2" id="selected-question-count" role="status" aria-live="polite">0 selected</span>
            </div>
        </section>

        <div class="modal fade" id="createExamModal" tabindex="-1" aria-labelledby="create-exam-heading" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable exam-builder__modal-dialog">
                <div class="modal-content exam-builder__modal border-0 shadow-lg rounded-4">
                    <form id="createExamForm" action="{{ route($isFacultyRequest ? 'admin.tests.store-manual' : 'super-admin.tests.store-manual') }}" method="POST">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                        <div id="examQuestionIds"></div>
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
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div>
                                        <h3 class="h6 fw-bold mb-1" id="exam-selection-heading">Choose your questions</h3>
                                        <p class="small text-muted mb-0">Set the item count, then select questions below or randomize them.</p>
                                    </div>
                                    <span class="badge rounded-pill text-bg-primary px-3 py-2 exam-builder__selection-count" id="modal-selected-question-count">0 selected</span>
                                </div>
                                <div class="row g-3 align-items-center">
                                    <div class="col-sm-5 col-md-4">
                                        <label for="question-count" class="form-label fw-semibold">Number of exam items</label>
                                        <input
                                            id="question-count"
                                            class="form-control form-control-lg"
                                            type="number"
                                            name="question_count"
                                            value="{{ old('question_count', min(10, $questionBankItems->count())) }}"
                                            min="1"
                                            max="{{ min(100, $questionBankItems->count()) }}"
                                            required
                                        >
                                    </div>
                                    <div class="col-sm-7 col-md-8">
                                        <div class="form-check form-switch border rounded-3 p-3 ps-5 bg-body">
                                            <input class="form-check-input" type="checkbox" role="switch" id="random-question-selection">
                                            <label class="form-check-label fw-semibold" for="random-question-selection">Choose random questions automatically</label>
                                            <div class="small text-muted">Randomly selects exactly the item count above.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="small text-danger mt-2" id="question-selection-error" role="alert" hidden>Close this dialog, select exactly the requested number of question cards, then reopen the exam setup.</div>
                            </section>
                            <section aria-labelledby="exam-details-heading">
                                <h3 class="h6 fw-bold mb-3 exam-builder__details-heading" id="exam-details-heading"><i class="bi bi-calendar2-week" aria-hidden="true"></i>Exam details and schedule</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="exam-title" class="form-label">Examination name</label>
                                        <input id="exam-title" name="title" value="{{ old('title') }}" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="exam-topic" class="form-label">Subject or topic</label>
                                        <input id="exam-topic" name="topic" value="{{ old('topic') }}" class="form-control" maxlength="255" required>
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
                        <div class="modal-footer border-0 px-4 pb-4">
                            <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Keep editing</button>
                            <button class="btn btn-primary rounded-pill px-4 exam-builder__create-button" type="submit">
                                <i class="bi {{ $isFacultyRequest ? 'bi-send' : 'bi-check2-circle' }} me-1" aria-hidden="true"></i>
                                {{ $isFacultyRequest ? 'Post exam' : 'Publish timed examination' }}
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
        const courseForm = document.getElementById('exam-course-form');
        const courseSelect = document.getElementById('exam-course');
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

        const selectedCount = document.getElementById('selected-question-count');
        const modalSelectedCount = document.getElementById('modal-selected-question-count');
        const selectionError = document.getElementById('question-selection-error');
        const questionCheckboxes = Array.from(document.querySelectorAll('.js-exam-question'));
        const questionCountInput = document.getElementById('question-count');
        const randomSelection = document.getElementById('random-question-selection');
        const updateSelectedQuestionCount = () => {
            const count = questionCheckboxes.filter((checkbox) => checkbox.checked).length;
            const requestedCount = Number.parseInt(questionCountInput.value, 10) || 0;
            const label = `${count} of ${requestedCount} selected`;
            selectedCount.textContent = label;
            modalSelectedCount.textContent = label;
            selectionError.hidden = true;
        };
        const selectRandomQuestions = () => {
            const requestedCount = Number.parseInt(questionCountInput.value, 10);
            if (!questionCountInput.checkValidity() || !Number.isInteger(requestedCount) || requestedCount > questionCheckboxes.length) {
                questionCountInput.reportValidity();
                randomSelection.checked = false;
                return;
            }

            const shuffledQuestions = [...questionCheckboxes];
            for (let index = shuffledQuestions.length - 1; index > 0; index--) {
                const randomIndex = Math.floor(Math.random() * (index + 1));
                [shuffledQuestions[index], shuffledQuestions[randomIndex]] = [shuffledQuestions[randomIndex], shuffledQuestions[index]];
            }

            questionCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            shuffledQuestions.slice(0, requestedCount).forEach((checkbox) => {
                checkbox.checked = true;
            });
            updateSelectedQuestionCount();
        };

        questionCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', () => {
            randomSelection.checked = false;
            updateSelectedQuestionCount();
        }));
        questionCountInput.addEventListener('input', () => {
            if (randomSelection.checked) {
                selectRandomQuestions();
                return;
            }
            updateSelectedQuestionCount();
        });
        randomSelection.addEventListener('change', () => {
            if (randomSelection.checked) {
                selectRandomQuestions();
            }
        });
        form.addEventListener('submit', (event) => {
            const requestedCount = Number.parseInt(questionCountInput.value, 10);
            const count = questionCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (count !== requestedCount) {
                event.preventDefault();
                selectionError.hidden = false;
                questionCountInput.focus();
                return;
            }

            if (closesAtInput.value && closesAtInput.value <= startsAtInput.value) {
                event.preventDefault();
                closesAtInput.setCustomValidity('The closing date and time must be after the start date and time.');
                closesAtInput.reportValidity();
                return;
            }

            const selectedQuestionIds = document.getElementById('examQuestionIds');
            selectedQuestionIds.replaceChildren(...questionCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'question_bank_item_ids[]';
                    input.value = checkbox.value;

                    return input;
                }));
        });
        closesAtInput.addEventListener('input', () => closesAtInput.setCustomValidity(''));
        updateSelectedQuestionCount();
    });
</script>
@endsection
