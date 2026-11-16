@extends('layouts.app')

@section('content')
<style>
    .faculty-question-bank__hero {
        background:
            radial-gradient(circle at 88% 18%, rgb(255 255 255 / 15%) 0 5.5rem, transparent 5.6rem),
            linear-gradient(125deg, #102a56 0%, #2948a3 58%, #6953cf 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.5rem;
        box-shadow: 0 1.25rem 2.5rem rgb(35 54 115 / 20%), inset 0 1px rgb(255 255 255 / 18%);
        color: #fff;
        overflow: hidden;
        padding: clamp(1.5rem, 4vw, 2.75rem);
        position: relative;
    }

    .faculty-question-bank__hero-icon {
        align-items: center;
        animation: question-bank-float 4s ease-in-out infinite;
        background: rgb(255 255 255 / 13%);
        border: 1px solid rgb(255 255 255 / 25%);
        border-radius: 1.15rem;
        box-shadow: 0 1rem 2rem rgb(10 22 58 / 22%), inset 0 1px rgb(255 255 255 / 24%);
        display: inline-flex;
        flex: 0 0 3.75rem;
        font-size: 1.65rem;
        height: 3.75rem;
        justify-content: center;
    }

    .faculty-question-bank__eyebrow {
        color: #c7d2fe;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .faculty-question-bank__surface,
    .faculty-question-bank__question {
        background: linear-gradient(145deg, #fff, #f8faff);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 .75rem 1.75rem rgb(15 23 42 / 7%), inset 0 1px #fff;
    }

    .faculty-question-bank__question {
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .faculty-question-bank__question > div > div,
    .faculty-question-bank__question > div > div > div {
        min-width: 0;
    }

    .faculty-question-bank__question:hover {
        border-color: #a5b4fc;
        box-shadow: 0 1rem 1.75rem rgb(79 70 229 / 12%), inset 0 1px #fff;
        transform: perspective(900px) translateY(-3px) rotateX(1deg);
    }

    .faculty-question-bank__question .btn,
    .faculty-question-bank__hero .btn,
    .faculty-question-bank__add-button {
        transition: box-shadow .2s ease, transform .2s ease, background-color .2s ease;
    }

    .faculty-question-bank__question .btn:focus-visible,
    .faculty-question-bank__hero .btn:focus-visible,
    .faculty-question-bank__add-button:focus-visible {
        box-shadow: 0 0 0 .25rem rgb(99 102 241 / 25%);
        outline: 0;
    }

    .faculty-question-bank__filter {
        max-width: 100%;
        min-width: min(20rem, 100%);
    }

    .faculty-question-bank__section-heading {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
    }

    .faculty-question-bank__preview-question {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .9rem;
        padding: 1rem;
    }

    .faculty-question-bank__preview-choice {
        align-items: center;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        color: #334155;
        display: flex;
        gap: .75rem;
        justify-content: space-between;
        padding: .8rem 1rem;
    }

    .faculty-question-bank__preview-choice--correct {
        background: #f0fdf4;
        border-color: #86efac;
        color: #166534;
    }

    .faculty-question-bank__preview-letter {
        align-items: center;
        background: #f1f5f9;
        border-radius: 50%;
        color: #475569;
        display: inline-flex;
        flex: 0 0 1.8rem;
        font-size: .8rem;
        font-weight: 800;
        height: 1.8rem;
        justify-content: center;
        width: 1.8rem;
    }

    .faculty-question-bank__question-number {
        align-items: center;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: .85rem;
        color: #4338ca;
        display: inline-flex;
        flex: 0 0 2.6rem;
        font-size: .9rem;
        font-weight: 800;
        height: 2.6rem;
        justify-content: center;
    }

    .faculty-question-bank__question-text {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        overflow: hidden;
        overflow-wrap: anywhere;
    }

    .faculty-question-bank__add-button {
        box-shadow: 0 .6rem 1.1rem rgb(79 70 229 / 24%);
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .faculty-question-bank__add-button:hover {
        box-shadow: 0 .9rem 1.5rem rgb(79 70 229 / 32%);
        transform: translateY(-2px);
    }

    .question-bank-modal {
        max-height: min(90dvh, 58rem);
    }

    .question-bank-modal form {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }

    .question-bank-modal .modal-header,
    .question-bank-modal .modal-footer {
        background: var(--bs-modal-bg);
        flex: 0 0 auto;
        position: relative;
        z-index: 1;
    }

    .question-bank-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .question-bank-modal .modal-footer {
        border-top: 1px solid var(--bs-modal-header-border-color);
        box-shadow: 0 -0.5rem 1.25rem rgb(15 23 42 / 5%);
    }

    .question-bank-modal .form-label {
        font-size: .9rem;
    }

    .question-bank-modal .form-control,
    .question-bank-modal .form-select {
        font-size: .95rem;
        min-width: 0;
    }

    .question-bank-modal .form-control:focus,
    .question-bank-modal .form-select:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 .22rem rgb(99 102 241 / 15%);
    }

    .question-bank-modal .modal-footer {
        border-top: 1px solid #e8eef5;
    }

    .faculty-question-bank__preview-modal {
        border-radius: 1rem;
        overflow: hidden;
    }

    @media (max-width: 575.98px) {
        .faculty-question-bank__filter {
            min-width: 100%;
            width: 100%;
        }

        .faculty-question-bank__question > div {
            align-items: flex-start !important;
            flex-wrap: wrap;
        }

        .faculty-question-bank__question > div > div {
            flex: 1 1 calc(100% - 3.5rem);
        }

        .faculty-question-bank__question > div > .btn {
            margin-left: auto;
        }

        .question-bank-modal .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .question-bank-modal .modal-footer .btn {
            width: 100%;
        }
    }

    @keyframes question-bank-float {
        0%, 100% { transform: translateY(0) rotate(-4deg); }
        50% { transform: translateY(-6px) rotate(-1deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        .faculty-question-bank__hero-icon { animation: none; }
        .faculty-question-bank__question,
        .faculty-question-bank__add-button { transition: none; }
    }

    html[data-bs-theme="dark"] .faculty-question-bank__surface,
    html[data-bs-theme="dark"] .faculty-question-bank__question {
        background: linear-gradient(145deg, #1e293b, #172033);
        border-color: #334155;
        box-shadow: 0 .75rem 1.75rem rgb(0 0 0 / 18%), inset 0 1px rgb(255 255 255 / 4%);
    }

    html[data-bs-theme="dark"] .faculty-question-bank__question-number {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .faculty-question-bank__preview-question,
    html[data-bs-theme="dark"] .faculty-question-bank__preview-choice {
        background: #172033;
        border-color: #334155;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .faculty-question-bank__preview-choice--correct {
        background: #123b2b;
        border-color: #166534;
        color: #bbf7d0;
    }

    html[data-bs-theme="dark"] .faculty-question-bank__preview-letter {
        background: #273449;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .question-bank-modal .modal-footer {
        border-top-color: #334155;
    }
</style>

<main class="container py-4 faculty-question-bank">
    <header class="faculty-question-bank__hero d-flex flex-wrap justify-content-between align-items-center gap-4 mb-4">
        <div class="d-flex align-items-center gap-3 gap-md-4">
            <span class="faculty-question-bank__hero-icon" aria-hidden="true"><i class="bi bi-journal-richtext"></i></span>
            <div>
                <div class="faculty-question-bank__eyebrow">Faculty workspace</div>
                <h1 class="display-6 fw-bold mt-1 mb-2">Question Bank</h1>
                <p class="mb-0 text-white-50">Build and organize your private collection of exam-ready questions.</p>
            </div>
        </div>
        @if ($courses->isNotEmpty())
            @if ($course !== null)
                <button class="btn btn-light btn-lg rounded-pill px-4 faculty-question-bank__add-button" type="button" data-bs-toggle="modal" data-bs-target="#createQuestionModal">
                    <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i> Create a question
                </button>
            @else
                <button class="btn btn-light btn-lg rounded-pill px-4 faculty-question-bank__add-button" type="button" data-focus-program>
                    <i class="bi bi-arrow-down-circle me-1" aria-hidden="true"></i> Choose a program
                </button>
            @endif
        @endif
    </header>

    @if (session('success'))
        <div class="alert alert-success rounded-4 shadow-sm" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger rounded-4 shadow-sm" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($courses->isEmpty())
        <div class="alert alert-warning rounded-4 shadow-sm">There are no programs in your assigned college yet. Ask an Admin to add a program first.</div>
    @else
        <section class="faculty-question-bank__surface mb-4" aria-label="Question Bank program filter">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 p-md-4">
                <div>
                    <span class="faculty-question-bank__eyebrow text-primary">Your collection</span>
                    <h2 class="h5 fw-bold mb-1">Choose a program</h2>
                    <p class="small text-muted mb-0">Switch programs to organize and manage your questions.</p>
                </div>
                <form method="GET" action="{{ route('admin.question-bank.index') }}" class="d-flex flex-wrap align-items-center gap-2 faculty-question-bank__filter">
                    <label class="visually-hidden" for="faculty-question-course">Program filter</label>
                    <select class="form-select form-select-lg" id="faculty-question-course" name="course_id" onchange="this.form.submit()">
                        <option value="">All my programs</option>
                        @foreach($courses as $availableCourse)
                            <option value="{{ $availableCourse->id }}" @selected($selectedCourseId === $availableCourse->id)>{{ $availableCourse->name }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-outline-primary" type="submit">Apply filter</button></noscript>
                </form>
            </div>
        </section>
    @endif

    @if ($course !== null)
        <div class="modal fade" id="createQuestionModal" tabindex="-1" aria-labelledby="create-question-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
                    <form action="{{ route('admin.question-bank.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <input type="hidden" name="form_context" value="create">
                        <div class="modal-header px-4 pt-4">
                            <div>
                                <span class="faculty-question-bank__eyebrow text-primary">New bank item</span>
                                <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="create-question-heading">Create a question</h2>
                                <p class="small text-muted mb-0">Adding to <strong>{{ $course->name }}</strong> · Only you can manage this question.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4">
                            <div class="mb-4">
                                <label for="question" class="form-label fw-semibold">Question prompt</label>
                                <textarea id="question" name="question" class="form-control" rows="3" maxlength="5000" required>{{ old('form_context') === 'create' ? old('question') : '' }}</textarea>
                            </div>
                            <div class="row g-3">
                                @for ($index = 0; $index < 4; $index++)
                                    <div class="col-md-6">
                                        <label for="option-{{ $index }}" class="form-label fw-semibold">Choice {{ chr(65 + $index) }}</label>
                                        <input id="option-{{ $index }}" name="options[]" value="{{ old('form_context') === 'create' ? old('options.'.$index) : '' }}" class="form-control" maxlength="255" required>
                                    </div>
                                @endfor
                                <div class="col-md-6">
                                    <label for="correct-option" class="form-label fw-semibold">Correct choice</label>
                                    <select id="correct-option" name="correct_option" class="form-select" required>
                                        @for ($index = 0; $index < 4; $index++)
                                            <option value="{{ $index }}" @selected((string) (old('form_context') === 'create' ? old('correct_option', '0') : '0') === (string) $index)>Choice {{ chr(65 + $index) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="question-topic" class="form-label fw-semibold">Subject or topic</label>
                                    <input id="question-topic" name="topic" value="{{ old('form_context') === 'create' ? old('topic') : '' }}" class="form-control" maxlength="255" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="subtopic-id" class="form-label fw-semibold">Module topic <span class="text-muted fw-normal">(optional)</span></label>
                                    <select id="subtopic-id" name="subtopic_id" class="form-select">
                                        <option value="">Not linked to a module</option>
                                        @foreach ($subtopics as $subtopic)
                                            <option value="{{ $subtopic->id }}" @selected((string) (old('form_context') === 'create' ? old('subtopic_id') : '') === (string) $subtopic->id)>{{ $subtopic->module->title }} — {{ $subtopic->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="question-explanation" class="form-label fw-semibold">Explanation <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea id="question-explanation" name="explanation" class="form-control" rows="2" maxlength="5000">{{ old('form_context') === 'create' ? old('explanation') : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Keep editing</button>
                            <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="submit">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Create question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <section aria-labelledby="bank-items-heading">
        <div class="faculty-question-bank__section-heading mb-3">
            <div>
                <span class="faculty-question-bank__eyebrow text-primary">Question collection</span>
                <h2 class="h3 fw-bold mb-1" id="bank-items-heading">{{ $course?->name ?? 'My questions' }}</h2>
                <p class="small text-muted mb-0">Editing or removing a bank item does not change questions already copied into an exam.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $items->total() }} total</span>
        </div>

        @forelse ($items as $item)
            <article class="faculty-question-bank__question mb-3">
                <div class="d-flex align-items-center justify-content-between gap-3 p-3 p-md-4">
                    <div class="d-flex align-items-start gap-3">
                        <span class="faculty-question-bank__question-number" aria-hidden="true"><i class="bi bi-patch-question"></i></span>
                        <div>
                            <span class="badge rounded-pill text-bg-light mb-2">{{ $item->topic ?: 'General' }}</span>
                            <h3 class="faculty-question-bank__question-text h6 fw-semibold text-break mb-1">{{ $item->question }}</h3>
                            <span class="small text-muted">{{ $item->subtopic?->module?->title ? $item->subtopic->module->title.' · '.$item->subtopic->title : ($item->course?->name ?? 'Program question') }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-primary rounded-pill flex-shrink-0 px-3"
                        data-bs-toggle="modal"
                        data-bs-target="#viewQuestionModal{{ $item->id }}"
                        aria-label="View question and answer choices"
                        title="View question and answer choices"
                    >
                        <i class="bi bi-eye me-sm-1" aria-hidden="true"></i><span class="d-none d-sm-inline">Preview</span>
                    </button>
                </div>
            </article>

            <div class="modal fade" id="viewQuestionModal{{ $item->id }}" tabindex="-1" aria-labelledby="viewQuestionModalLabel{{ $item->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg faculty-question-bank__preview-modal">
                        <div class="modal-header">
                            <div>
                                <span class="faculty-question-bank__eyebrow text-primary">Question preview</span>
                                <h2 class="modal-title h5 fw-bold mt-1 mb-0" id="viewQuestionModalLabel{{ $item->id }}">Question and answer choices</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="faculty-question-bank__preview-question mb-3">
                                <div class="small text-uppercase text-muted fw-bold mb-2">Prompt</div>
                                <h3 class="h6 fw-semibold text-break mb-0">{{ $item->question }}</h3>
                            </div>
                            <div class="small text-uppercase text-muted fw-bold mb-2">Answer choices</div>
                            <ol class="list-unstyled d-grid gap-2 mb-0">
                                @foreach ($item->options as $option)
                                    <li class="faculty-question-bank__preview-choice {{ $option === $item->correct_answer ? 'faculty-question-bank__preview-choice--correct fw-semibold' : '' }}">
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="faculty-question-bank__preview-letter">{{ chr(65 + $loop->index) }}</span>
                                            <span class="text-break">{{ $option }}</span>
                                        </span>
                                        @if ($option === $item->correct_answer)
                                            <span class="badge rounded-pill text-bg-success flex-shrink-0"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Correct answer</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <form action="{{ route('admin.question-bank.destroy', $item) }}" method="POST" data-confirm-title="Remove this question from the bank?" data-confirm-message="Existing examinations will not change." data-confirm-button="Remove question">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger" type="submit">Delete</button>
                            </form>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                                <button
                                    class="btn btn-primary"
                                    type="button"
                                    data-open-edit-modal="#editQuestionModal{{ $item->id }}"
                                >
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editQuestionModal{{ $item->id }}" tabindex="-1" aria-labelledby="editQuestionModalLabel{{ $item->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
                                    <form action="{{ route('admin.question-bank.update', $item) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="course_id" value="{{ $item->course_id }}">
                                        <input type="hidden" name="form_context" value="edit-{{ $item->id }}">
                                        <div class="modal-header">
                                            <div>
                                                <h2 class="modal-title h5 mb-1" id="editQuestionModalLabel{{ $item->id }}">Edit question</h2>
                                                <p class="small text-muted mb-0">{{ $item->course?->name }} · {{ $item->topic }}</p>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="edit-question-text-{{ $item->id }}" class="form-label">Question</label>
                                                <textarea id="edit-question-text-{{ $item->id }}" name="question" class="form-control" rows="3" maxlength="5000" required>{{ old('form_context') === 'edit-'.$item->id ? old('question') : $item->question }}</textarea>
                                            </div>
                                            <div class="row g-3">
                                                @for ($index = 0; $index < 4; $index++)
                                                    <div class="col-md-6">
                                                        <label for="edit-option-{{ $item->id }}-{{ $index }}" class="form-label">Choice {{ chr(65 + $index) }}</label>
                                                        <input id="edit-option-{{ $item->id }}-{{ $index }}" name="options[]" value="{{ old('form_context') === 'edit-'.$item->id ? old('options.'.$index) : ($item->options[$index] ?? '') }}" class="form-control" maxlength="255" required>
                                                    </div>
                                                @endfor
                                                <div class="col-md-6">
                                                    <label for="edit-correct-option-{{ $item->id }}" class="form-label">Correct choice</label>
                                                    <select id="edit-correct-option-{{ $item->id }}" name="correct_option" class="form-select" required>
                                                        @for ($index = 0; $index < 4; $index++)
                                                            <option value="{{ $index }}" @selected((string) (old('form_context') === 'edit-'.$item->id ? old('correct_option') : array_search($item->correct_answer, $item->options, true)) === (string) $index)>Choice {{ chr(65 + $index) }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit-topic-{{ $item->id }}" class="form-label">Subject or topic</label>
                                                    <input id="edit-topic-{{ $item->id }}" name="topic" value="{{ old('form_context') === 'edit-'.$item->id ? old('topic') : $item->topic }}" class="form-control" maxlength="255" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit-subtopic-{{ $item->id }}" class="form-label">Module topic <span class="text-muted">(optional)</span></label>
                                                    <select id="edit-subtopic-{{ $item->id }}" name="subtopic_id" class="form-select">
                                                        <option value="">Not linked to a module</option>
                                                        @foreach ($subtopics as $subtopic)
                                                            <option value="{{ $subtopic->id }}" @selected((string) (old('form_context') === 'edit-'.$item->id ? old('subtopic_id') : $item->subtopic_id) === (string) $subtopic->id)>{{ $subtopic->module->title }} — {{ $subtopic->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label for="edit-explanation-{{ $item->id }}" class="form-label">Explanation <span class="text-muted">(optional)</span></label>
                                                    <textarea id="edit-explanation-{{ $item->id }}" name="explanation" class="form-control" rows="2" maxlength="5000">{{ old('form_context') === 'edit-'.$item->id ? old('explanation') : $item->explanation }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer px-4 py-3">
                                            <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="submit">Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
            </div>
        @empty
            <div class="faculty-question-bank__surface p-4 p-md-5 text-center">
                <span class="faculty-question-bank__question-number mb-3"><i class="bi bi-journal-plus" aria-hidden="true"></i></span>
                <h3 class="h5 fw-bold">Your question bank is ready to grow</h3>
                <p class="text-muted mb-3">Add your first question to {{ $course?->name ?? 'your program' }}. Your questions stay private to you.</p>
                @if ($course !== null)
                    <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="button" data-bs-toggle="modal" data-bs-target="#createQuestionModal">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Create your first question
                    </button>
                @endif
            </div>
        @endforelse
        <div class="mt-3">{{ $items->links() }}</div>
    </section>
</div>
@if ($errors->any() && $course !== null)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formContext = @json(old('form_context', 'create'));
            const modalId = formContext.startsWith('edit-')
                ? `editQuestionModal${formContext.slice(5)}`
                : 'createQuestionModal';
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('[data-focus-program]')?.addEventListener('click', () => {
            document.getElementById('faculty-question-course')?.focus();
        });

        document.querySelectorAll('[data-open-edit-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const viewModalElement = button.closest('.modal');
                const editModalElement = document.querySelector(button.dataset.openEditModal);
                if (!viewModalElement || !editModalElement) {
                    return;
                }

                viewModalElement.addEventListener('hidden.bs.modal', () => {
                    bootstrap.Modal.getOrCreateInstance(editModalElement).show();
                }, { once: true });
                bootstrap.Modal.getOrCreateInstance(viewModalElement).hide();
            });
        });
    });
</script>
@endsection
