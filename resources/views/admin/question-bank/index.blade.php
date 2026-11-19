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
    .faculty-question-bank__filter-trigger,
    .faculty-question-bank__add-button {
        transition: box-shadow .2s ease, transform .2s ease, background-color .2s ease;
    }

    .faculty-question-bank__question .btn:focus-visible,
    .faculty-question-bank__hero .btn:focus-visible,
    .faculty-question-bank__filter-trigger:focus-visible,
    .faculty-question-bank__add-button:focus-visible {
        box-shadow: 0 0 0 .25rem rgb(99 102 241 / 25%);
        outline: 0;
    }

    .faculty-question-bank__filter {
        flex: 1 1 26rem;
        max-width: 48rem;
    }

    .faculty-question-bank__filter-trigger {
        align-items: center;
        display: inline-flex;
        height: 3rem;
        justify-content: center;
        width: 3rem;
    }

    .faculty-question-bank__section-heading {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
    }

    .faculty-question-bank__topic-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr));
    }

    .faculty-question-bank__topic-card {
        background: linear-gradient(145deg, #fff, #f8faff);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 .65rem 1.4rem rgb(15 23 42 / 6%), inset 0 1px #fff;
        color: inherit;
        min-height: 8.5rem;
        overflow: hidden;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .faculty-question-bank__topic-card:hover,
    .faculty-question-bank__topic-card:focus-within {
        border-color: #818cf8;
        box-shadow: 0 1rem 1.8rem rgb(79 70 229 / 13%), inset 0 1px #fff;
        color: inherit;
        transform: perspective(900px) translateY(-3px) rotateX(1deg);
    }

    .faculty-question-bank__topic-card-link {
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
        justify-content: space-between;
        min-height: 8.5rem;
        padding: 1.15rem;
        text-decoration: none;
    }

    .faculty-question-bank__topic-card-link:focus-visible {
        outline: 3px solid #818cf8;
        outline-offset: -3px;
    }

    .faculty-question-bank__topic-copy {
        min-width: 0;
    }

    .faculty-question-bank__topic-icon {
        align-items: center;
        background: #eef2ff;
        border: 1px solid #dbe3ff;
        border-radius: .8rem;
        color: #4f46e5;
        display: inline-flex;
        flex: 0 0 2.75rem;
        font-size: 1.25rem;
        height: 2.75rem;
        justify-content: center;
    }

    .faculty-question-bank__topic-card--active {
        background: linear-gradient(145deg, #eef2ff, #fff);
        border-color: #6366f1;
        box-shadow: 0 0 0 .2rem rgb(99 102 241 / 13%), 0 .8rem 1.5rem rgb(79 70 229 / 12%);
    }

    .faculty-question-bank__question-grid {
        animation: question-bank-fade-in .22s ease-out both;
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

    .faculty-question-bank__metric-card {
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .85rem;
        min-height: 7.25rem;
        padding: 1rem;
    }

    .faculty-question-bank__metric-info {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 50%;
        color: var(--bs-secondary-color);
        display: inline-flex;
        flex: 0 0 1.75rem;
        height: 1.75rem;
        justify-content: center;
        padding: 0;
        width: 1.75rem;
    }

    .faculty-question-bank__metric-info:hover,
    .faculty-question-bank__metric-info:focus-visible {
        background: var(--bs-secondary-bg);
        color: var(--bs-primary);
        outline: 0;
    }

    .faculty-question-bank__metric-info:focus-visible {
        box-shadow: 0 0 0 .2rem rgb(99 102 241 / 20%);
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

    .question-bank-modal-dialog {
        transform: translateY(-.5rem) scale(.96);
        transition: transform .24s ease-out;
    }

    .modal.show .question-bank-modal-dialog {
        transform: translateY(0) scale(1);
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

    @keyframes question-bank-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .faculty-question-bank__hero-icon { animation: none; }
        .faculty-question-bank__question,
        .faculty-question-bank__topic-card,
        .faculty-question-bank__add-button,
        .question-bank-modal-dialog { transition: none; }
        .faculty-question-bank__question-grid { animation: none; }
    }

    html[data-bs-theme="dark"] .faculty-question-bank__surface,
    html[data-bs-theme="dark"] .faculty-question-bank__question {
        background: linear-gradient(145deg, #1e293b, #172033);
        border-color: #334155;
        box-shadow: 0 .75rem 1.75rem rgb(0 0 0 / 18%), inset 0 1px rgb(255 255 255 / 4%);
    }

    html[data-bs-theme="dark"] .faculty-question-bank__topic-card {
        background: linear-gradient(145deg, #1e293b, #172033);
        border-color: #334155;
        box-shadow: 0 .65rem 1.4rem rgb(0 0 0 / 18%), inset 0 1px rgb(255 255 255 / 4%);
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .faculty-question-bank__topic-card--active {
        background: linear-gradient(145deg, #262f57, #1e293b);
        border-color: #818cf8;
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
                <div class="faculty-question-bank__eyebrow">{{ $readOnly ? 'Academic oversight' : 'Faculty workspace' }}</div>
                <h1 class="display-6 fw-bold mt-1 mb-2">Question Bank</h1>
                <p class="mb-0 text-white-50">
                    {{ $readOnly ? 'Review shared question folders by college and program. Disable questions that should not be used in future exams.' : 'Create and manage your questions in folders shared with Faculty in the same program.' }}
                </p>
            </div>
        </div>
        @if ($showTopicPage && ! $readOnly)
            <a href="{{ route('admin.question-bank.index', ['course_id' => $course->id, 'faculty_id' => $selectedFacultyId]) }}" class="btn btn-light rounded-pill px-4">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to Topics
            </a>
        @elseif ($showTopicPage)
            <a href="{{ route('super-admin.question-bank.index', ['department_id' => $selectedDepartmentId, 'course_id' => $course->id, 'faculty_id' => $selectedFacultyId]) }}" class="btn btn-light rounded-pill px-4">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to Topics
            </a>
        @endif
    </header>

    @if (! $showTopicPage || $readOnly)
        @if (! $readOnly && $courses->isEmpty())
            <div class="alert alert-warning rounded-4 shadow-sm">There are no programs in your assigned college yet. Ask an Admin to add a program first.</div>
        @else
            <section class="faculty-question-bank__surface mb-4" aria-label="Question Bank filters">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 p-md-4">
                    @if ($readOnly)
                        <div>
                            <span class="faculty-question-bank__eyebrow text-primary">Academic oversight</span>
                            <h2 class="h5 fw-bold mb-1">Filter by college, program, and faculty</h2>
                            <p class="small text-muted mb-0">
                                {{ $departments->firstWhere('id', $selectedDepartmentId)?->name ?? 'All colleges' }}
                                · {{ $course?->name ?? 'All programs' }}
                                · {{ $selectedFaculty?->name ?? 'All faculty' }}
                            </p>
                        </div>
                        <button
                            class="btn btn-outline-primary rounded-3 faculty-question-bank__filter-trigger"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#questionBankFilterModal"
                            data-question-bank-filter-trigger
                            aria-label="Filter by college, program, and faculty"
                            aria-controls="questionBankFilterModal"
                            aria-expanded="false"
                            title="Filter by college, program, and faculty"
                        >
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                        </button>
                    @else
                        <div>
                            <span class="faculty-question-bank__eyebrow text-primary">Your collection</span>
                            <h2 class="h5 fw-bold mb-1">Filter by program and faculty</h2>
                            <p class="small text-muted mb-0">Browse shared folders and manage your own questions.</p>
                        </div>
                        <form method="GET" action="{{ route('admin.question-bank.index') }}" class="d-flex flex-wrap align-items-center gap-2 faculty-question-bank__filter">
                            <label class="visually-hidden" for="faculty-question-course">Program filter</label>
                            <select class="form-select form-select-lg" id="faculty-question-course" name="course_id" onchange="this.form.submit()">
                                <option value="">All my programs</option>
                                @foreach($courses as $availableCourse)
                                    <option value="{{ $availableCourse->id }}" @selected($selectedCourseId === $availableCourse->id)>{{ $availableCourse->name }}</option>
                                @endforeach
                            </select>
                            <label class="visually-hidden" for="faculty-question-faculty">Faculty filter</label>
                            <select class="form-select form-select-lg" id="faculty-question-faculty" name="faculty_id"
                                    @disabled($selectedCourseId === null)
                                    onchange="this.form.submit()">
                                <option value="">All faculty</option>
                                @foreach($filterFaculty as $availableFaculty)
                                    <option value="{{ $availableFaculty->id }}" @selected($selectedFacultyId === $availableFaculty->id)>
                                        {{ $availableFaculty->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($course !== null)
                                <button class="btn btn-primary btn-lg rounded-pill px-4 faculty-question-bank__add-button ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#createTopicModal">
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Create Topic
                                </button>
                            @endif
                            <noscript><button class="btn btn-outline-primary" type="submit">Apply filter</button></noscript>
                        </form>
                    @endif
                </div>
            </section>
            @if ($readOnly)
                <div class="modal fade" id="questionBankFilterModal" tabindex="-1" aria-labelledby="question-bank-filter-heading" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered question-bank-modal-dialog">
                        <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
                            <form method="GET" action="{{ route('super-admin.question-bank.index') }}">
                                <div class="modal-header px-4 pt-4">
                                    <div>
                                        <span class="faculty-question-bank__eyebrow text-primary">Refine your results</span>
                                        <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="question-bank-filter-heading">Filter Question Bank</h2>
                                        <p class="small text-muted mb-0">Choose a college and program. Faculty is optional; leave it blank to view all contributors.</p>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body px-4 d-grid gap-3">
                                    <div>
                                        <label for="question-bank-department" class="form-label fw-semibold">College</label>
                                        <select
                                            class="form-select"
                                            id="question-bank-department"
                                            name="department_id"
                                            aria-label="Filter by college"
                                        >
                                            <option value="">Select a college</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}" @selected($selectedDepartmentId === $department->id)>{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="question-bank-course" class="form-label fw-semibold">Program</label>
                                        <select
                                            class="form-select"
                                            id="question-bank-course"
                                            name="course_id"
                                            aria-label="Filter by program"
                                            @disabled($selectedDepartmentId === null)
                                        >
                                            <option value="" @selected($selectedCourseId === null)>{{ $selectedDepartmentId === null ? 'Select a college first' : 'Select a program' }}</option>
                                            @foreach($filterCourses as $availableCourse)
                                                <option
                                                    value="{{ $availableCourse->id }}"
                                                    data-department-id="{{ $availableCourse->department_id }}"
                                                    @selected($selectedCourseId === $availableCourse->id)
                                                    @if($availableCourse->department_id !== $selectedDepartmentId) hidden @endif
                                                >{{ $availableCourse->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="question-bank-faculty" class="form-label fw-semibold">Faculty <span class="text-muted fw-normal">(optional)</span></label>
                                        <select
                                            class="form-select"
                                            id="question-bank-faculty"
                                            name="faculty_id"
                                            aria-label="Filter by faculty"
                                            @disabled($selectedCourseId === null)
                                        >
                                            <option value="">All faculty</option>
                                            @foreach($filterFaculty as $availableFaculty)
                                                <option
                                                    value="{{ $availableFaculty->id }}"
                                                    data-department-id="{{ $selectedDepartmentId }}"
                                                    @selected($selectedFacultyId === $availableFaculty->id)
                                                >{{ $availableFaculty->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer justify-content-between px-4 py-3">
                                    <a href="{{ route('super-admin.question-bank.index') }}" class="btn btn-outline-secondary rounded-pill px-3">Clear filters</a>
                                    <div class="d-flex gap-2 ms-auto">
                                        <button class="btn btn-light rounded-pill px-3" type="button" data-bs-dismiss="modal">Cancel</button>
                                        <button class="btn btn-primary rounded-pill px-4" type="submit">
                                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Apply Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif

    @if ($readOnly && ! $showTopicPage && $selectedDepartmentId !== null && $course === null)
        <div class="alert alert-info rounded-4 shadow-sm" role="status">Choose a program to view its shared topic folders and questions.</div>
    @endif

    @if ($course !== null && ! $showTopicPage)
        <section class="mb-4" aria-labelledby="topics-heading">
            <div class="faculty-question-bank__section-heading mb-3">
                <div>
                    <span class="faculty-question-bank__eyebrow text-primary">{{ $readOnly ? 'Shared collection' : 'Organize your collection' }}</span>
                    <h2 class="h4 fw-bold mb-1" id="topics-heading">Topic folders</h2>
                    <p class="small text-muted mb-0">{{ $course->name }} · Shared with Faculty in this program.</p>
                </div>
            </div>
            @if ($topics->isNotEmpty())
                <div class="faculty-question-bank__topic-grid">
                    @foreach ($topics as $topic)
                        <a
                            class="faculty-question-bank__topic-card faculty-question-bank__topic-card-link"
                            href="{{ $readOnly
                                ? route('super-admin.question-bank.index', ['department_id' => $selectedDepartmentId, 'course_id' => $course->id, 'faculty_id' => $selectedFacultyId, 'topic_id' => $topic->id])
                                : route('admin.question-bank.topics.show', array_filter(['topic' => $topic, 'faculty_id' => $selectedFacultyId])) }}"
                            aria-label="Open {{ $topic->name }} topic folder"
                        >
                            <span class="d-flex align-items-center justify-content-between gap-3">
                                <span class="d-flex align-items-center gap-3 faculty-question-bank__topic-copy">
                                    <span class="faculty-question-bank__topic-icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                                    <span class="fw-bold text-break">{{ $topic->name }}</span>
                                </span>
                                <span class="badge rounded-pill text-bg-primary">{{ $topic->questions_count }}</span>
                            </span>
                            <span class="d-flex align-items-center justify-content-between gap-2">
                                <span class="small text-muted">
                                    {{ $topic->questions_count === 1 ? '1 question' : $topic->questions_count.' questions' }}
                                    @if ($topic->createdBy)
                                        · Created by {{ $topic->createdBy->name }}
                                    @endif
                                </span>
                                <span class="small text-primary fw-semibold text-nowrap">Open folder <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="faculty-question-bank__surface p-4 text-center text-muted">
                    @if ($readOnly)
                        No shared topic folders have been created for {{ $course->name }} yet.
                    @else
                        No topics yet. Create the first topic for {{ $course->name }}.
                    @endif
                </div>
            @endif
        </section>

        @if (! $readOnly)
        <div class="modal fade" id="createTopicModal" tabindex="-1" aria-labelledby="create-topic-heading" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered question-bank-modal-dialog">
                <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
                    <form action="{{ route('admin.question-bank.topics.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <input type="hidden" name="form_context" value="topic">
                        <div class="modal-header px-4 pt-4">
                            <div>
                                <span class="faculty-question-bank__eyebrow text-primary">{{ $course->name }}</span>
                                <h2 class="modal-title h4 fw-bold mt-1 mb-0" id="create-topic-heading">Create Topic</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4 pb-4">
                            <label for="topic-name" class="form-label fw-semibold">Topic Name</label>
                            <input
                                id="topic-name"
                                name="name"
                                value="{{ old('form_context') === 'topic' ? old('name') : '' }}"
                                class="form-control @error('name') is-invalid @enderror"
                                maxlength="255"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="modal-footer justify-content-between px-4 py-3">
                            <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="submit">Save Topic</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endif

    @if ($showTopicPage && $selectedTopic !== null)
        <section class="faculty-question-bank__surface d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 p-md-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-folder2-open faculty-question-bank__topic-icon" aria-hidden="true"></i>
                <div>
                    <span class="faculty-question-bank__eyebrow text-primary">
                        {{ $course->name }} · Shared folder
                        @if ($selectedTopic->createdBy)
                            · Created by {{ $selectedTopic->createdBy->name }}
                        @endif
                    </span>
                    <h2 class="h4 fw-bold mb-0">{{ $selectedTopic->name }}</h2>
                </div>
            </div>
            @if (! $readOnly && $selectedTopic->created_by === auth()->id())
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary rounded-pill px-3" type="button" data-bs-toggle="modal" data-bs-target="#editTopicModal{{ $selectedTopic->id }}">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit folder
                </button>
                @if (! $topicHasOtherFacultyQuestions)
                <form
                    action="{{ route('admin.question-bank.topics.destroy', $selectedTopic) }}"
                    method="POST"
                    data-confirm-title="Delete this topic folder?"
                    data-confirm-message="Your questions in this folder will be deleted. Existing exam copies remain unchanged."
                    data-confirm-button="Delete folder"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger rounded-pill px-3" type="submit">
                        <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete folder
                    </button>
                </form>
                @endif
            </div>
            @endif
        </section>
        @if (! $readOnly && $selectedTopic->created_by === auth()->id())
            @if ($topicHasOtherFacultyQuestions)
                <p class="small text-muted mb-3">This folder contains questions from other Faculty, so it cannot be deleted.</p>
            @endif
            @include('admin.question-bank.partials.topic-edit-modal', ['topic' => $selectedTopic])
        @endif
    @endif

    @if ($selectedTopic !== null && ! $readOnly)
        <div class="modal fade" id="createQuestionModal" tabindex="-1" aria-labelledby="create-question-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable question-bank-modal-dialog">
                <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
                    <form action="{{ route('admin.question-bank.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <input type="hidden" name="topic_id" value="{{ $selectedTopic->id }}">
                        <input type="hidden" name="form_context" value="create">
                        <div class="modal-header px-4 pt-4">
                            <div>
                                <span class="faculty-question-bank__eyebrow text-primary">New bank item</span>
                                <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="create-question-heading">Create a question</h2>
                                <p class="small text-muted mb-0"><strong>{{ $course->name }}</strong> · <span class="badge rounded-pill text-bg-primary">{{ $selectedTopic->name }}</span></p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4">
                            <div class="mb-4">
                                <label for="question" class="form-label fw-semibold">Question Prompt</label>
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
                                    <label for="correct-option" class="form-label fw-semibold">Correct Choice</label>
                                    <select id="correct-option" name="correct_choice" class="form-select" required>
                                        @foreach (['A', 'B', 'C', 'D'] as $choice)
                                            <option value="{{ $choice }}" @selected((old('form_context') === 'create' ? old('correct_choice', 'A') : 'A') === $choice)>Choice {{ $choice }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="question-difficulty" class="form-label fw-semibold">Difficulty</label>
                                    <select id="question-difficulty" name="difficulty" class="form-select" required>
                                        @foreach (['easy' => 'Easy', 'medium' => 'Moderate', 'hard' => 'Hard'] as $value => $label)
                                            <option value="{{ $value }}" @selected((old('form_context') === 'create' ? old('difficulty', 'medium') : 'medium') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="question-image" class="form-label fw-semibold">Question Image <span class="text-muted fw-normal">(optional)</span></label>
                                    <input id="question-image" name="question_image" type="file" accept=".jpg,.jpeg,.png,.webp" class="form-control" data-question-image-preview="question-image-preview">
                                    <img id="question-image-preview" class="img-fluid rounded border mt-2 d-none" alt="Question image preview" style="max-height:12rem;object-fit:contain;">
                                    <div class="form-text">JPG, JPEG, PNG, or WebP up to 5 MB.</div>
                                </div>
                                <div class="col-12">
                                    <label for="question-explanation" class="form-label fw-semibold">Explanation <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea id="question-explanation" name="explanation" class="form-control" rows="2" maxlength="5000">{{ old('form_context') === 'create' ? old('explanation') : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="submit">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showTopicPage && $selectedTopic !== null)
    <section aria-labelledby="bank-items-heading">
        <div class="faculty-question-bank__section-heading mb-3">
            <div>
                <span class="faculty-question-bank__eyebrow text-primary">Question collection</span>
                <h2 class="h3 fw-bold mb-1" id="bank-items-heading">
                    {{ $selectedTopic?->name ?? ('Questions by '.$selectedFaculty?->name) }}
                </h2>
                <p class="small text-muted mb-0">
                    @if ($selectedFaculty !== null)
                        Showing questions added by {{ $selectedFaculty->name }} in {{ $course->name }}.
                    @else
                        {{ $readOnly ? 'Questions are shared across Faculty in this program. Disabled questions cannot be added to future exams.' : 'Questions are shared across Faculty in this program. You can edit or remove only questions you created.' }}
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if ($selectedTopic !== null || $selectedFaculty !== null)
                    <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $items->total() }} total</span>
                @endif
                @if ($selectedTopic !== null)
                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary rounded-pill px-3"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Filter questions by difficulty"
                            title="Filter questions by difficulty"
                        >
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i>
                            {{ $selectedDifficulty === 'medium' ? 'Moderate' : ($selectedDifficulty ? ucfirst($selectedDifficulty) : 'Difficulty') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a
                                    class="dropdown-item {{ $selectedDifficulty === null ? 'active' : '' }}"
                                    href="{{ route(
                                        $readOnly ? 'super-admin.question-bank.index' : 'admin.question-bank.topics.show',
                                        array_filter([
                                            'department_id' => $readOnly ? $selectedDepartmentId : null,
                                            'course_id' => $readOnly ? $course->id : null,
                                            'faculty_id' => $selectedFacultyId,
                                            'topic_id' => $readOnly ? $selectedTopic->id : null,
                                            'topic' => $readOnly ? null : $selectedTopic->id,
                                            'health' => $selectedHealth === 'all' ? null : $selectedHealth,
                                        ])
                                    ) }}"
                                >All difficulties</a>
                            </li>
                            @foreach (['easy' => 'Easy', 'medium' => 'Moderate', 'hard' => 'Hard'] as $value => $label)
                                <li>
                                    <a
                                        class="dropdown-item {{ $selectedDifficulty === $value ? 'active' : '' }}"
                                        href="{{ route(
                                            $readOnly ? 'super-admin.question-bank.index' : 'admin.question-bank.topics.show',
                                            array_filter([
                                                'department_id' => $readOnly ? $selectedDepartmentId : null,
                                                'course_id' => $readOnly ? $course->id : null,
                                                'faculty_id' => $selectedFacultyId,
                                                'topic_id' => $readOnly ? $selectedTopic->id : null,
                                                'topic' => $readOnly ? null : $selectedTopic->id,
                                                'difficulty' => $value,
                                                'health' => $selectedHealth === 'all' ? null : $selectedHealth,
                                            ])
                                        ) }}"
                                    >{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary rounded-pill px-3"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Filter questions by health status"
                            title="Filter questions by health status"
                        >
                            <i class="bi {{ match ($selectedHealth) {
                                'healthy' => 'bi-check-circle',
                                'needs_review' => 'bi-exclamation-triangle',
                                'flagged' => 'bi-flag-fill',
                                'untested' => 'bi-question-circle',
                                default => 'bi-heart-pulse',
                            } }} me-1" aria-hidden="true"></i>
                            {{ $selectedHealth === 'all' ? 'All Health' : match ($selectedHealth) {
                                'healthy' => 'Healthy',
                                'needs_review' => 'Needs Review',
                                'flagged' => 'Flagged',
                                'untested' => 'Untested',
                                default => 'Health',
                            } }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            @foreach ([
                                'all' => 'All Health',
                                'healthy' => 'Healthy',
                                'needs_review' => 'Needs Review',
                                'flagged' => 'Flagged',
                                'untested' => 'Untested',
                            ] as $healthValue => $healthLabel)
                                <li>
                                    <a
                                        class="dropdown-item {{ $selectedHealth === $healthValue ? 'active' : '' }}"
                                        href="{{ route(
                                            $readOnly ? 'super-admin.question-bank.index' : 'admin.question-bank.topics.show',
                                            array_filter([
                                                'department_id' => $readOnly ? $selectedDepartmentId : null,
                                                'course_id' => $readOnly ? $course->id : null,
                                                'faculty_id' => $selectedFacultyId,
                                                'topic_id' => $readOnly ? $selectedTopic->id : null,
                                                'topic' => $readOnly ? null : $selectedTopic->id,
                                                'difficulty' => $selectedDifficulty,
                                                'health' => $healthValue === 'all' ? null : $healthValue,
                                            ])
                                        ) }}"
                                    >{{ $healthLabel }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @if (! $readOnly)
                        <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="button" data-bs-toggle="modal" data-bs-target="#createQuestionModal">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Create Question
                        </button>
                    @endif
                @endif
            </div>
        </div>

        <div class="faculty-question-bank__question-grid">
        @forelse ($items as $item)
            @php
                $canManageQuestion = ! $readOnly && $item->author_id === auth()->id();
                $canFlagQuestion = $canFlagQuestions
                    && (int) $item->author_id !== (int) auth()->id();
                $questionMetrics = $questionQualityMetrics[$item->id] ?? [
                    'total_responses' => 0,
                    'correct_responses' => 0,
                    'pass_rate' => null,
                    'usage_count' => 0,
                    'last_used_at' => null,
                    'choice_breakdown' => [],
                    'quality_status' => $item->needs_revision ? 'flagged' : 'untested',
                    'unflagged_quality_status' => 'untested',
                    'is_easy_miscalibrated' => false,
                ];
                $qualityLabel = match ($questionMetrics['quality_status']) {
                    'healthy' => 'Healthy',
                    'needs_review' => 'Needs Review',
                    'flagged' => 'Flagged',
                    default => 'Untested',
                };
                $qualityBadgeClass = match ($questionMetrics['quality_status']) {
                    'healthy' => 'text-bg-success',
                    'needs_review' => 'text-bg-warning',
                    'flagged' => 'text-bg-danger',
                    default => 'text-bg-secondary',
                };
            @endphp
            <article class="faculty-question-bank__question mb-3" data-question-card="{{ $item->id }}">
                <div class="d-flex align-items-center justify-content-between gap-3 p-3 p-md-4">
                    <div class="d-flex align-items-start gap-3">
                        <span class="faculty-question-bank__question-number" aria-hidden="true"><i class="bi bi-patch-question"></i></span>
                        <div>
                            <span class="badge rounded-pill text-bg-light mb-2">{{ $selectedTopic?->name ?? $item->topicRecord?->name ?? 'Unfiled' }}</span>
                            <div class="small text-muted mb-1">Added by: {{ $item->author?->name ?: 'Faculty' }}</div>
                            <span class="badge rounded-pill {{ $item->difficulty === 'hard' ? 'text-bg-danger' : ($item->difficulty === 'easy' ? 'text-bg-success' : 'text-bg-warning') }} mb-2">{{ $item->difficulty === 'medium' ? 'Moderate' : ucfirst($item->difficulty ?? 'moderate') }}</span>
                            <span
                                id="question-health-status-{{ $item->id }}"
                                class="badge rounded-pill {{ $qualityBadgeClass }} mb-2"
                                data-quality-status="{{ $questionMetrics['quality_status'] }}"
                                data-unflagged-quality-status="{{ $questionMetrics['unflagged_quality_status'] }}"
                            >{{ $qualityLabel }}</span>
                            <span class="badge rounded-pill {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }} mb-2">{{ $item->is_active ? 'Enabled' : 'Disabled' }} for new exams</span>
                            <h3 class="faculty-question-bank__question-text h6 fw-semibold text-break mb-1">{{ $item->question }}</h3>
                            @if ($item->image_path)
                                <img src="{{ asset('storage/'.$item->image_path) }}" alt="Question illustration" class="img-fluid rounded border mt-2" style="max-height: 9rem; object-fit: contain;">
                            @endif
                            @if ($item->explanation)
                                <span class="small text-muted d-block mt-2">{{ $item->explanation }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 flex-shrink-0">
                        @if ($readOnly || $canManageQuestion)
                        <form action="{{ route($readOnly ? 'super-admin.question-bank.status' : 'admin.question-bank.status', $item) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $item->is_active ? '0' : '1' }}">
                            <button class="btn {{ $item->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-3" type="submit">
                                <i class="bi {{ $item->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-sm-1" aria-hidden="true"></i>
                                <span>{{ $item->is_active ? 'Disable' : 'Enable' }}</span>
                            </button>
                        </form>
                        @endif
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
                </div>
            </article>

            <div class="modal fade" id="viewQuestionModal{{ $item->id }}" tabindex="-1" aria-labelledby="viewQuestionModalLabel{{ $item->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
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
                                <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge rounded-pill {{ $item->difficulty === 'hard' ? 'text-bg-danger' : ($item->difficulty === 'easy' ? 'text-bg-success' : 'text-bg-warning') }}">{{ $item->difficulty === 'medium' ? 'Moderate' : ucfirst($item->difficulty ?? 'moderate') }} difficulty</span>
                                    <span class="small text-muted">Added by: {{ $item->author?->name ?: 'Faculty' }}</span>
                                </div>
                                @if ($item->image_path)
                                    <img src="{{ asset('storage/'.$item->image_path) }}" alt="Question illustration" class="img-fluid rounded border mt-3">
                                @endif
                                @if ($item->explanation)
                                    <p class="small text-muted mt-3 mb-0"><strong>Explanation:</strong> {{ $item->explanation }}</p>
                                @endif
                            </div>
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                                <div class="col">
                                    <section class="faculty-question-bank__metric-card h-100">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <h3 class="small fw-semibold mb-0">Quality Status</h3>
                                            <button
                                                class="faculty-question-bank__metric-info"
                                                type="button"
                                                aria-label="Quality Status information"
                                                data-question-metric-tooltip
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="Automatically classifies the question as Healthy, Needs Review (pass rate &lt; 40% or an unused distractor), Flagged (manually marked for editing), or Untested."
                                            ><i class="bi bi-info-circle" aria-hidden="true"></i></button>
                                        </div>
                                        <span
                                            class="badge rounded-pill {{ $qualityBadgeClass }}"
                                            data-quality-status-pill
                                            data-quality-status="{{ $questionMetrics['quality_status'] }}"
                                            data-unflagged-quality-status="{{ $questionMetrics['unflagged_quality_status'] }}"
                                        >{{ $qualityLabel }}</span>
                                    </section>
                                </div>
                                <div class="col">
                                    <section class="faculty-question-bank__metric-card h-100">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <h3 class="small fw-semibold mb-0">Student Pass Rate</h3>
                                            <button
                                                class="faculty-question-bank__metric-info"
                                                type="button"
                                                aria-label="Student Pass Rate information"
                                                data-question-metric-tooltip
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="Combines all student responses across every exam and quiz where this question appeared to show its true pass percentage."
                                            ><i class="bi bi-info-circle" aria-hidden="true"></i></button>
                                        </div>
                                        @if ($questionMetrics['total_responses'] === 0)
                                            <div class="small fw-semibold text-muted">0 responses yet</div>
                                        @else
                                            <div class="small fw-semibold tabular-nums">
                                                {{ number_format((float) $questionMetrics['pass_rate'], 1) }}%
                                                <span class="text-muted fw-normal">({{ $questionMetrics['correct_responses'] }}/{{ $questionMetrics['total_responses'] }})</span>
                                            </div>
                                        @endif
                                    </section>
                                </div>
                                <div class="col">
                                    <section class="faculty-question-bank__metric-card h-100">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <h3 class="small fw-semibold mb-0">Difficulty Match</h3>
                                            <button
                                                class="faculty-question-bank__metric-info"
                                                type="button"
                                                aria-label="Difficulty Match information"
                                                data-question-metric-tooltip
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="Compares your assigned difficulty (Easy, Moderate, Hard) against actual student pass rates to spot items that are harder or easier than intended."
                                            ><i class="bi bi-info-circle" aria-hidden="true"></i></button>
                                        </div>
                                        <div class="small text-muted">
                                            Tagged {{ $item->difficulty === 'medium' ? 'Moderate' : ucfirst($item->difficulty ?? 'Moderate') }}
                                        </div>
                                        @if ($questionMetrics['is_easy_miscalibrated'])
                                            <div class="small text-warning fw-semibold tabular-nums mt-1">Harder than intended ({{ number_format((float) $questionMetrics['pass_rate'], 1) }}% pass rate)</div>
                                        @elseif ($questionMetrics['total_responses'] === 0)
                                            <div class="small text-muted mt-1">Awaiting student responses</div>
                                        @else
                                            <div class="small text-muted mt-1">
                                                Empirical pass rate: {{ number_format((float) $questionMetrics['pass_rate'], 1) }}%
                                            </div>
                                        @endif
                                    </section>
                                </div>
                                <div class="col">
                                    <section class="faculty-question-bank__metric-card h-100">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <h3 class="small fw-semibold mb-0">Exam Reuse &amp; Exposure</h3>
                                            <button
                                                class="faculty-question-bank__metric-info"
                                                type="button"
                                                aria-label="Exam Reuse and Exposure information"
                                                data-question-metric-tooltip
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="Tracks how many exams or quizzes have included this question and when it was last used, helping you balance fresh vs. proven items."
                                            ><i class="bi bi-info-circle" aria-hidden="true"></i></button>
                                        </div>
                                        <div class="small fw-semibold tabular-nums">
                                            Used in {{ $questionMetrics['usage_count'] }} {{ $questionMetrics['usage_count'] === 1 ? 'exam' : 'exams' }}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Last used:
                                            @if ($questionMetrics['last_used_at'] !== null)
                                                {{ $questionMetrics['last_used_at']->format('M j, Y') }}
                                            @elseif ($questionMetrics['usage_count'] > 0)
                                                No attempts yet
                                            @else
                                                Not yet used in an exam
                                            @endif
                                        </div>
                                    </section>
                                </div>
                            </div>
                            <div class="small text-uppercase text-muted fw-bold mb-2">Answer choices</div>
                            <ol class="list-unstyled d-grid gap-2 mb-0">
                                @foreach ($item->options as $option)
                                    @php
                                        $choiceMetric = $questionMetrics['choice_breakdown'][$loop->index] ?? [
                                            'percentage' => 0.0,
                                            'is_correct' => $option === $item->correct_answer,
                                            'is_unused_distractor' => false,
                                        ];
                                    @endphp
                                    <li class="faculty-question-bank__preview-choice {{ $choiceMetric['is_correct'] ? 'faculty-question-bank__preview-choice--correct fw-semibold' : '' }}">
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="faculty-question-bank__preview-letter">{{ chr(65 + $loop->index) }}</span>
                                            <span class="text-break">{{ $option }}</span>
                                        </span>
                                        <span class="d-flex flex-column align-items-end flex-shrink-0">
                                            @if ($choiceMetric['is_correct'])
                                                <span class="badge rounded-pill text-bg-success"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Correct answer</span>
                                            @endif
                                            <span class="small tabular-nums {{ $choiceMetric['is_unused_distractor'] ? 'text-warning fw-semibold' : 'text-muted' }}">
                                                {{ number_format((float) $choiceMetric['percentage'], (float) $choiceMetric['percentage'] === (float) round((float) $choiceMetric['percentage']) ? 0 : 1) }}% picked
                                                @if ($choiceMetric['is_unused_distractor'])
                                                    <span>· Unused distractor</span>
                                                @endif
                                            </span>
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if ($canManageQuestion)
                                    <form action="{{ route('admin.question-bank.destroy', $item) }}" method="POST" data-confirm-title="Remove this question from the bank?" data-confirm-message="Existing examinations will not change." data-confirm-button="Remove question">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                @endif
                                @if ($canFlagQuestion)
                                    <button
                                        class="btn btn-outline-warning"
                                        type="button"
                                        data-question-revision-toggle
                                        data-url="{{ route($readOnly ? 'super-admin.question-bank.revision' : 'admin.question-bank.revision', $item) }}"
                                        data-item-id="{{ $item->id }}"
                                        data-csrf-token="{{ csrf_token() }}"
                                        data-needs-revision="{{ $item->needs_revision ? 'true' : 'false' }}"
                                        aria-label="{{ $item->needs_revision ? 'Unflag question for revision' : 'Flag question for revision' }}"
                                    >{{ $item->needs_revision ? 'Unflag' : 'Flag for Revision' }}</button>
                                    <span id="question-revision-feedback-{{ $item->id }}" class="small" role="status" aria-live="polite"></span>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                                @if ($canManageQuestion)
                                <button
                                    class="btn btn-primary"
                                    type="button"
                                    data-open-edit-modal="#editQuestionModal{{ $item->id }}"
                                >
                                    Edit
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($canManageQuestion)
            <div class="modal fade" id="editQuestionModal{{ $item->id }}" tabindex="-1" aria-labelledby="editQuestionModalLabel{{ $item->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable question-bank-modal-dialog">
                                <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
                                    <form action="{{ route('admin.question-bank.update', $item) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="course_id" value="{{ $item->course_id }}">
                                        <input type="hidden" name="topic_id" value="{{ $item->topic_id }}">
                                        <input type="hidden" name="form_context" value="edit-{{ $item->id }}">
                                        <div class="modal-header">
                                            <div>
                                                <h2 class="modal-title h5 mb-1" id="editQuestionModalLabel{{ $item->id }}">Edit question</h2>
                                                <p class="small text-muted mb-0">{{ $item->course?->name }} · {{ $item->topicRecord?->name ?? $item->topic }}</p>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if ($questionMetrics['usage_count'] > 0)
                                                <div class="alert alert-info py-2 px-3 mb-3 small" role="note">
                                                    Past exam results remain preserved; edits apply to future exams.
                                                </div>
                                            @endif
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
                                                    <label for="edit-correct-option-{{ $item->id }}" class="form-label">Correct Choice</label>
                                                    <select id="edit-correct-option-{{ $item->id }}" name="correct_choice" class="form-select" required>
                                                        @foreach (['A', 'B', 'C', 'D'] as $choice)
                                                            <option value="{{ $choice }}" @selected((old('form_context') === 'edit-'.$item->id ? old('correct_choice') : chr(65 + (int) array_search($item->correct_answer, $item->options, true))) === $choice)>Choice {{ $choice }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit-difficulty-{{ $item->id }}" class="form-label">Difficulty</label>
                                                    <select id="edit-difficulty-{{ $item->id }}" name="difficulty" class="form-select" required>
                                                        @foreach (['easy' => 'Easy', 'medium' => 'Moderate', 'hard' => 'Hard'] as $value => $label)
                                                            <option value="{{ $value }}" @selected((old('form_context') === 'edit-'.$item->id ? old('difficulty', $item->difficulty === 'moderate' ? 'medium' : $item->difficulty) : ($item->difficulty === 'moderate' ? 'medium' : ($item->difficulty ?? 'medium'))) === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label for="edit-question-image-{{ $item->id }}" class="form-label">Question image <span class="text-muted">(optional)</span></label>
                                                    @if ($item->image_path)
                                                        <div class="mb-2">
                                                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="Current question illustration" class="img-fluid rounded border" style="max-height: 9rem; object-fit: contain;">
                                                        </div>
                                                        <div class="form-check mb-2">
                                                            <input id="remove-question-image-{{ $item->id }}" name="remove_question_image" value="1" type="checkbox" class="form-check-input" @checked(old('form_context') === 'edit-'.$item->id && old('remove_question_image'))>
                                                            <label class="form-check-label" for="remove-question-image-{{ $item->id }}">Remove current image</label>
                                                        </div>
                                                    @endif
                                                    <input id="edit-question-image-{{ $item->id }}" name="question_image" type="file" accept=".jpg,.jpeg,.png,.webp" class="form-control" data-question-image-preview="edit-question-image-preview-{{ $item->id }}">
                                                    <img id="edit-question-image-preview-{{ $item->id }}" class="img-fluid rounded border mt-2 d-none" alt="Question image preview" style="max-height:12rem;object-fit:contain;">
                                                    <div class="form-text">JPG, JPEG, PNG, or WebP up to 5 MB.</div>
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
            @endif
        @empty
            <div class="faculty-question-bank__surface p-4 p-md-5 text-center">
                <span class="faculty-question-bank__question-number mb-3"><i class="bi bi-journal-plus" aria-hidden="true"></i></span>
                <h3 class="h5 fw-bold">No questions in this topic yet</h3>
                <p class="text-muted mb-0">
                    @if ($selectedFaculty !== null)
                        No questions by {{ $selectedFaculty->name }} were found in this program.
                    @else
                        {{ $readOnly ? 'No questions are stored in this shared folder yet.' : 'Add the first question to '.$selectedTopic->name.'.' }}
                    @endif
                </p>
            </div>
        @endforelse
        </div>
        <div class="mt-3">{{ $items->links() }}</div>
    </section>
    @endif
</div>
<script>
    document.querySelectorAll('.faculty-question-bank__question-grid .modal').forEach((modal) => {
        document.body.appendChild(modal);
    });
</script>
@if ($errors->any() && ! $readOnly)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formContext = @json(old('form_context', 'create'));
            const modalId = formContext === 'topic'
                ? 'createTopicModal'
                : (formContext.startsWith('topic-edit-')
                    ? `editTopicModal${formContext.slice('topic-edit-'.length)}`
                    : (formContext.startsWith('edit-') ? `editQuestionModal${formContext.slice(5)}` : 'createQuestionModal'));
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterModal = document.getElementById('questionBankFilterModal');
        const filterTrigger = document.querySelector('[data-question-bank-filter-trigger]');
        const departmentFilter = document.getElementById('question-bank-department');
        const programFilter = document.getElementById('question-bank-course');
        const facultyFilter = document.getElementById('question-bank-faculty');

        if (filterModal && filterTrigger && departmentFilter && programFilter && facultyFilter) {
            const programOptions = Array.from(programFilter.options)
                .filter((option) => option.dataset.departmentId);
            const facultyOptions = Array.from(facultyFilter.options)
                .filter((option) => option.dataset.departmentId);
            const programPlaceholder = programFilter.querySelector('option[value=""]');
            const updateProgramOptions = (resetProgram = false) => {
                const departmentId = departmentFilter.value;

                programOptions.forEach((option) => {
                    option.hidden = option.dataset.departmentId !== departmentId;
                });

                if (resetProgram || (
                    programFilter.value !== ''
                    && programFilter.selectedOptions[0]?.dataset.departmentId !== departmentId
                )) {
                    programFilter.value = '';
                }

                programFilter.disabled = departmentId === '';
                programPlaceholder.textContent = departmentId ? 'Select a program' : 'Select a college first';
            };

            const updateFacultyOptions = (resetFaculty = false) => {
                const departmentId = departmentFilter.value;

                facultyOptions.forEach((option) => {
                    option.hidden = option.dataset.departmentId !== departmentId;
                });

                if (resetFaculty || (
                    facultyFilter.value !== ''
                    && facultyFilter.selectedOptions[0]?.dataset.departmentId !== departmentId
                )) {
                    facultyFilter.value = '';
                }

                facultyFilter.disabled = departmentId === '' || programFilter.value === '';
            };

            departmentFilter.addEventListener('change', () => {
                updateProgramOptions(true);
                updateFacultyOptions(true);
            });
            programFilter.addEventListener('change', () => updateFacultyOptions());
            filterModal.addEventListener('shown.bs.modal', () => filterTrigger.setAttribute('aria-expanded', 'true'));
            filterModal.addEventListener('hidden.bs.modal', () => filterTrigger.setAttribute('aria-expanded', 'false'));
            updateProgramOptions();
            updateFacultyOptions();
        }

        document.querySelectorAll('[data-question-image-preview]').forEach((input) => {
            input.addEventListener('change', () => {
                const preview = document.getElementById(input.dataset.questionImagePreview);
                const file = input.files?.[0];
                if (!preview) {
                    return;
                }

                if (!file) {
                    preview.removeAttribute('src');
                    preview.classList.add('d-none');
                    return;
                }

                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
            });
        });

        document.querySelectorAll('[data-question-metric-tooltip]').forEach((button) => {
            bootstrap.Tooltip.getOrCreateInstance(button, {
                container: 'body',
                delay: { show: 150, hide: 50 },
            });
        });

        const qualityLabels = {
            healthy: 'Healthy',
            needs_review: 'Needs Review',
            flagged: 'Flagged',
            untested: 'Untested',
        };
        const qualityBadgeClasses = {
            healthy: 'text-bg-success',
            needs_review: 'text-bg-warning',
            flagged: 'text-bg-danger',
            untested: 'text-bg-secondary',
        };
        const updateQualityPill = (pill, status, unflaggedStatus) => {
            if (!pill || !qualityLabels[status] || !qualityBadgeClasses[status]) {
                throw new Error('The server returned an invalid question health status.');
            }

            pill.classList.remove('text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary');
            pill.classList.add(qualityBadgeClasses[status]);
            pill.dataset.qualityStatus = status;
            pill.dataset.unflaggedQualityStatus = unflaggedStatus;
            pill.textContent = qualityLabels[status];
        };

        document.querySelectorAll('[data-question-revision-toggle]').forEach((button) => {
            button.addEventListener('click', async () => {
                const nextValue = button.dataset.needsRevision !== 'true';
                const feedback = button.closest('.modal-footer')?.querySelector('[role="status"]');
                button.disabled = true;
                if (feedback) {
                    feedback.textContent = '';
                    feedback.classList.remove('text-danger', 'text-success');
                }

                try {
                    const response = await fetch(button.dataset.url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': button.dataset.csrfToken,
                        },
                        body: JSON.stringify({ needs_revision: nextValue }),
                    });
                    const result = await response.json().catch(() => null);
                    if (!response.ok) {
                        throw new Error(result?.message || 'Unable to update the revision flag. Please try again.');
                    }
                    if (
                        !result
                        || typeof result.needs_revision !== 'boolean'
                        || typeof result.quality_status !== 'string'
                        || typeof result.unflagged_quality_status !== 'string'
                    ) {
                        throw new Error('The server returned an incomplete revision update.');
                    }

                    const modal = button.closest('.modal');
                    const statusPills = [
                        document.getElementById(`question-health-status-${button.dataset.itemId}`),
                        ...(modal ? Array.from(modal.querySelectorAll('[data-quality-status-pill]')) : []),
                    ];
                    statusPills.forEach((pill) => updateQualityPill(
                        pill,
                        result.quality_status,
                        result.unflagged_quality_status,
                    ));

                    button.dataset.needsRevision = String(result.needs_revision);
                    button.textContent = result.needs_revision ? 'Unflag' : 'Flag for Revision';
                    button.setAttribute(
                        'aria-label',
                        result.needs_revision ? 'Unflag question for revision' : 'Flag question for revision',
                    );
                    if (feedback) {
                        feedback.textContent = 'Revision flag updated.';
                        feedback.classList.add('text-success');
                    }
                } catch (error) {
                    if (feedback) {
                        feedback.textContent = error instanceof Error
                            ? error.message
                            : 'Unable to update the revision flag. Please try again.';
                        feedback.classList.add('text-danger');
                    }
                } finally {
                    button.disabled = false;
                }
            });
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
