@extends('layouts.app')

@section('content')
<style>
    .admin-bank__hero {
        background:
            radial-gradient(circle at 90% 15%, rgb(255 255 255 / 13%) 0 4.5rem, transparent 4.6rem),
            linear-gradient(125deg, #102a56 0%, #2948a3 58%, #5143a8 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.15rem;
        box-shadow: 0 1rem 2rem rgb(35 54 115 / 16%);
        color: #fff;
        padding: clamp(1.25rem, 3vw, 2rem);
    }

    .admin-bank__eyebrow {
        color: #c7d2fe;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .admin-bank__filter-bar,
    .admin-bank__question {
        background: linear-gradient(145deg, #fff, #f8faff);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 .65rem 1.4rem rgb(15 23 42 / 6%);
    }

    .admin-bank__applied-filter {
        align-items: center;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 999px;
        color: #3730a3;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 700;
        gap: .45rem;
        max-width: 100%;
        padding: .55rem .85rem;
    }

    .admin-bank__applied-filter span {
        overflow-wrap: anywhere;
    }

    .admin-bank__filter-modal {
        border: 0;
        border-radius: 1.1rem;
        overflow: hidden;
    }

    .admin-bank__filter-modal .modal-header {
        background: linear-gradient(145deg, #f8faff, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        padding: 1.35rem 1.5rem;
    }

    .admin-bank__filter-modal .modal-body {
        padding: 1.5rem;
    }

    .admin-bank__filter-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }

    .admin-bank__filter-icon {
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

    .admin-bank__filter-field {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        padding: 1rem;
    }

    .admin-bank__question {
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .admin-bank__question:hover {
        border-color: #a5b4fc;
        box-shadow: 0 1rem 1.6rem rgb(79 70 229 / 10%);
        transform: translateY(-2px);
    }

    .admin-bank__question-text {
        overflow-wrap: anywhere;
    }

    .admin-bank__question-copy {
        min-width: 0;
    }

    .admin-bank__question-icon {
        align-items: center;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: .75rem;
        color: #4338ca;
        display: inline-flex;
        flex: 0 0 2.5rem;
        height: 2.5rem;
        justify-content: center;
    }

    .admin-bank__preview-choice {
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

    .admin-bank__preview-choice--correct {
        background: #f0fdf4;
        border-color: #86efac;
        color: #166534;
    }

    .admin-bank__preview-letter {
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

    @media (max-width: 575.98px) {
        .admin-bank__filter-modal .modal-header,
        .admin-bank__filter-modal .modal-body,
        .admin-bank__filter-modal .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .admin-bank__filter-modal .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .admin-bank__filter-modal .modal-footer .btn {
            width: 100%;
        }
    }

    html[data-bs-theme="dark"] .admin-bank__filter-bar,
    html[data-bs-theme="dark"] .admin-bank__question,
    html[data-bs-theme="dark"] .admin-bank__filter-field {
        background: linear-gradient(145deg, #1e293b, #172033);
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .admin-bank__applied-filter {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .admin-bank__filter-modal .modal-header,
    html[data-bs-theme="dark"] .admin-bank__filter-modal .modal-footer {
        background: #172033;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .admin-bank__filter-icon,
    html[data-bs-theme="dark"] .admin-bank__question-icon {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    html[data-bs-theme="dark"] .admin-bank__preview-choice {
        background: #172033;
        border-color: #334155;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .admin-bank__preview-choice--correct {
        background: #123b2b;
        border-color: #166534;
        color: #bbf7d0;
    }

    html[data-bs-theme="dark"] .admin-bank__preview-letter {
        background: #273449;
        color: #cbd5e1;
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-bank__question {
            transition: none;
        }
    }
</style>

<main class="container admin-bank">
    <header class="admin-bank__hero mb-4">
        <div class="admin-bank__eyebrow mb-2">Academic oversight</div>
        <h1 class="h2 fw-bold mb-2">Question Bank Oversight</h1>
        <p class="text-white-50 mb-0">Each Faculty member has a separate bank. Select a Faculty member to see their questions across all programs, then narrow by program if needed.</p>
    </header>

    <section class="admin-bank__filter-bar d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4" aria-label="Question bank filters">
        <div>
            <div class="small text-uppercase text-muted fw-bold mb-1">Current view</div>
            <div class="admin-bank__applied-filter">
                <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                <span>
            @if($selectedFaculty)
                {{ $selectedFaculty->name }} · {{ $selectedCourseId ? $courses->firstWhere('id', $selectedCourseId)?->name : 'All programs' }}
            @else
                All Faculty banks
            @endif
                </span>
            </div>
        </div>
        <button class="btn btn-primary rounded-3 px-4" type="button" data-bs-toggle="modal" data-bs-target="#filterQuestionBankModal">
            <i class="bi bi-sliders me-1" aria-hidden="true"></i> Filter Faculty Banks
        </button>
    </section>

    <div class="modal fade" id="filterQuestionBankModal" tabindex="-1" aria-labelledby="filter-question-bank-heading" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content admin-bank__filter-modal shadow-lg">
                <form method="GET" action="{{ route('super-admin.question-bank.index') }}">
                    <div class="modal-header">
                        <div class="d-flex align-items-start gap-3">
                            <span class="admin-bank__filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                            <div>
                                <span class="admin-bank__eyebrow text-primary">Refine your results</span>
                                <h2 class="modal-title h4 fw-bold mt-1 mb-1" id="filter-question-bank-heading">Filter Question Bank</h2>
                                <p class="small text-muted mb-0">
                                @if ($selectedFaculty)
                                    {{ $selectedFaculty->name }}'s bank · College: {{ $selectedFaculty->department?->name ?? 'Not assigned' }}. Choose a Faculty member and an optional program; results update only after you apply the filters.
                                @else
                                    Choose a Faculty member to view their questions across all programs. Results update only after you apply the filters.
                                @endif
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div class="admin-bank__filter-field">
                            <div class="row g-3">
                            <div class="{{ $selectedFaculty ? 'col-md-6' : 'col-12' }}">
                                <label for="question-bank-author" class="form-label fw-semibold">Faculty member</label>
                                <select id="question-bank-author" name="author_id" class="form-select form-select-lg">
                                    <option value="">All Faculty banks</option>
                                    @foreach ($faculties as $faculty)
                                        <option value="{{ $faculty->id }}" @selected((string) $selectedAuthorId === (string) $faculty->id)>{{ $faculty->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($selectedFaculty)
                                <div class="col-md-6">
                                    <label for="question-bank-course" class="form-label fw-semibold">Program <span class="text-muted">(optional)</span></label>
                                    <select id="question-bank-course" name="course_id" class="form-select form-select-lg">
                                        <option value="">All programs</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-clear-question-bank-filters>
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Clear filters
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Apply filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <section aria-labelledby="question-list-heading">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <div class="admin-bank__eyebrow text-primary">Bank contents</div>
            <h2 class="h4 fw-bold mb-0" id="question-list-heading">Questions</h2>
        </div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $items->total() }} total</span>
    </div>
    @forelse ($items as $item)
        <article class="admin-bank__question mb-3">
            <div class="d-flex align-items-center justify-content-between gap-3 p-3 p-md-4">
                <div class="d-flex align-items-start gap-3 admin-bank__question-copy">
                    <span class="admin-bank__question-icon" aria-hidden="true"><i class="bi bi-patch-question"></i></span>
                    <div class="admin-bank__question-copy">
                        <div class="small text-muted mb-1">{{ $item->author->name ?? 'Former Faculty' }} · {{ $item->course->name ?? 'Unknown program' }}</div>
                        <h3 class="admin-bank__question-text h6 fw-semibold mb-0">{{ $item->question }}</h3>
                        <span class="badge rounded-pill {{ $item->difficulty === 'hard' ? 'text-bg-danger' : ($item->difficulty === 'easy' ? 'text-bg-success' : 'text-bg-warning') }} mt-2">{{ ucfirst($item->difficulty ?? 'moderate') }} difficulty</span>
                        @if ($item->image_path)
                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="Question illustration" class="img-fluid rounded border mt-2" style="max-height: 9rem; object-fit: contain;">
                        @endif
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-primary rounded-pill flex-shrink-0"
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
                <div class="modal-content admin-bank__filter-modal shadow-lg">
                    <div class="modal-header">
                        <div>
                            <span class="admin-bank__eyebrow text-primary">Faculty question preview</span>
                            <h2 class="modal-title h5 fw-bold mt-1 mb-0" id="viewQuestionModalLabel{{ $item->id }}">Question and answer choices</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="admin-bank__filter-field mb-3">
                            <div class="small text-uppercase text-muted fw-bold mb-2">Prompt</div>
                            <h3 class="h6 fw-semibold text-break mb-0">{{ $item->question }}</h3>
                            <div class="mt-2"><span class="badge rounded-pill {{ $item->difficulty === 'hard' ? 'text-bg-danger' : ($item->difficulty === 'easy' ? 'text-bg-success' : 'text-bg-warning') }}">{{ ucfirst($item->difficulty ?? 'moderate') }} difficulty</span></div>
                            @if ($item->image_path)
                                <img src="{{ asset('storage/'.$item->image_path) }}" alt="Question illustration" class="img-fluid rounded border mt-3">
                            @endif
                        </div>
                        <div class="small text-uppercase text-muted fw-bold mb-2">Answer choices</div>
                        <ol class="list-unstyled d-grid gap-2 mb-0">
                            @foreach ($item->options as $option)
                                <li class="admin-bank__preview-choice {{ $option === $item->correct_answer ? 'admin-bank__preview-choice--correct fw-semibold' : '' }}">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="admin-bank__preview-letter">{{ chr(65 + $loop->index) }}</span>
                                        <span class="text-break">{{ $option }}</span>
                                    </span>
                                    @if ($option === $item->correct_answer)
                                        <span class="badge rounded-pill text-bg-success flex-shrink-0"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Correct answer</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="admin-bank__question p-4 text-center text-muted">No Question Bank items match this filter.</div>
    @endforelse
    <div class="mt-3">{{ $items->links() }}</div>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const programSelect = document.getElementById('question-bank-course');
    const facultySelect = document.getElementById('question-bank-author');
    const clearFiltersButton = document.querySelector('[data-clear-question-bank-filters]');

    facultySelect?.addEventListener('change', () => {
        if (programSelect) {
            programSelect.value = '';
        }
    });

    clearFiltersButton?.addEventListener('click', () => {
        if (facultySelect) {
            facultySelect.value = '';
        }
        if (programSelect) {
            programSelect.value = '';
        }
    });
});
</script>
@endsection
