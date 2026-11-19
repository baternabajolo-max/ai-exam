@extends('layouts.app')

@section('hide-navbar')
@endsection

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.22/dist/katex.min.css">
<!-- Link to External CSS -->
<link rel="stylesheet" href="{{ asset('css/student/learn/review.css') }}">

<style>
    /* ==========================================================
       STUDENT READING SANCTUARY & THEME TOKENS
    ========================================================== */
    :root {
        --reader-bg: #f8fafc;
        --reader-card-bg: #ffffff;
        --reader-text: #0f172a;
        --reader-lead: #475569;
        --reader-muted: #64748b;
        --reader-border: #e2e8f0;
        --reader-nav-bg: rgba(255, 255, 255, 0.88);
        --reader-font-size: 1.125rem;
        --reader-line-height: 1.85;
    }

    /* Sepia Warm Mode */
    body.theme-sepia {
        --reader-bg: #fbf7ee;
        --reader-card-bg: #f5eedb;
        --reader-text: #382717;
        --reader-lead: #5c4731;
        --reader-muted: #7c6853;
        --reader-border: #e6dac0;
        --reader-nav-bg: rgba(245, 238, 219, 0.92);
    }

    /* High-Contrast Focused Dark Mode */
    body.theme-night {
        --reader-bg: #090d16;
        --reader-card-bg: #111827;
        --reader-text: #f8fafc;
        --reader-lead: #cbd5e1;
        --reader-muted: #94a3b8;
        --reader-border: #1f2937;
        --reader-nav-bg: rgba(17, 24, 39, 0.92);
    }

    body.theme-night h1,
    body.theme-night h2,
    body.theme-night h3,
    body.theme-night h4,
    body.theme-night h5,
    body.theme-night h6,
    body.theme-night strong {
        color: #ffffff !important;
    }

    body.theme-night .text-secondary,
    body.theme-night .text-muted {
        color: #94a3b8 !important;
    }

    body.theme-night .text-slate-900,
    body.theme-night .text-dark {
        color: #f8fafc !important;
    }

    body.theme-night .reader-sheet {
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
    }

    body.theme-night .bg-light {
        background-color: #1f2937 !important;
        color: #f8fafc !important;
    }

    body.theme-night .border {
        border-color: #1f2937 !important;
    }

    .review-workspace {
        background-color: var(--reader-bg);
        min-height: 100vh;
        transition: background-color 0.25s ease, color 0.25s ease;
    }

    /* Reading Progress Bar */
    .reading-progress-track {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: transparent;
        z-index: 1050;
    }

    .reading-progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #2563eb, #38bdf8);
        transition: width 0.1s ease-out;
    }

    /* Sticky Navigation Header */
    .reader-navbar {
        position: sticky;
        top: 0;
        z-index: 1040;
        background: var(--reader-nav-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--reader-border);
        transition: background-color 0.25s ease, border-color 0.25s ease;
    }

    /* Reader Document Container */
    .reader-sheet {
        background-color: var(--reader-card-bg);
        border: 1px solid var(--reader-border);
        border-radius: 1.25rem;
        padding: 3.5rem 3.5rem 5rem;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
        transition: background-color 0.25s ease, border-color 0.25s ease;
    }

    .pdf-text-content {
        font-family: 'Charter', 'Bitstream Charter', 'Sitka Text', 'Cambria', serif;
        font-size: var(--reader-font-size);
        line-height: var(--reader-line-height);
        color: var(--reader-text);
        white-space: pre-line;
        word-break: break-word;
        letter-spacing: 0.01em;
    }

    .pdf-text-content::selection {
        background: #bae6fd;
        color: #0369a1;
    }

    /* Controls */
    .reader-control-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid var(--reader-border);
        background: var(--reader-card-bg);
        color: var(--reader-lead);
        font-size: 0.85rem;
        transition: all 0.15s ease;
    }

    .reader-control-btn:hover,
    .reader-control-btn.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .quiz-companion-card {
        background-color: var(--reader-card-bg);
        border: 1px solid var(--reader-border);
        border-radius: 1.25rem;
        padding: 1.5rem;
        position: sticky;
        top: 5rem;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }

    /* ==========================================================
       HIGHLIGHT TOOLTIP (STRICTLY INVISIBLE UNTIL SELECTION)
    ========================================================== */
    #highlight-tooltip {
        position: absolute !important;
        display: none !important;
        z-index: 99999 !important;
        background: #0f172a;
        padding: 0.35rem 0.5rem;
        border-radius: 9999px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.2);
        pointer-events: auto;
    }

    #highlight-tooltip.is-active {
        display: inline-flex !important;
        align-items: center;
        gap: 0.4rem;
        animation: tooltipPop 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes tooltipPop {
        from { opacity: 0; transform: translateY(6px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ==========================================================
       3D LIVE PREVIEW FLASHCARD ARCHITECTURE
    ========================================================== */
    .fc-scene {
        height: 250px;
        width: 100%;
        perspective: 1200px;
        position: relative;
    }

    .fc-card {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 1.25rem;
        transform-style: preserve-3d;
        -webkit-transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
        cursor: pointer;
        user-select: none;
        outline: none;
    }

    .fc-scene:hover .fc-card,
    .fc-card.is-flipped {
        transform: rotateY(180deg);
        box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.12);
    }

    .fc-face {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        padding: 1.5rem 1.25rem 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        border-radius: 1.25rem;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .fc-front { transform: rotateY(0deg); -webkit-transform: rotateY(0deg); }
    .fc-back { transform: rotateY(180deg); -webkit-transform: rotateY(180deg); }

    .fc-content-body {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        overflow-y: auto;
        padding: 0.5rem 0;
        scrollbar-width: thin;
    }

    /* Color Themes */
    .fc-theme-blue .fc-face   { background: radial-gradient(circle at 10% 20%, #eff6ff 0%, #dbeafe 100%); border-color: #bfdbfe; }
    .fc-theme-blue .fc-badge  { background: #dbeafe; color: #1e40af; }

    .fc-theme-green .fc-face  { background: radial-gradient(circle at 10% 20%, #f0fdf4 0%, #dcfce7 100%); border-color: #bbf7d0; }
    .fc-theme-green .fc-badge { background: #dcfce7; color: #166534; }

    .fc-theme-yellow .fc-face { background: radial-gradient(circle at 10% 20%, #fefce8 0%, #fef08a 100%); border-color: #fde047; }
    .fc-theme-yellow .fc-badge{ background: #fef08a; color: #854d0e; }

    .fc-theme-red .fc-face    { background: radial-gradient(circle at 10% 20%, #fef2f2 0%, #fee2e2 100%); border-color: #fecaca; }
    .fc-theme-red .fc-badge   { background: #fee2e2; color: #991b1b; }

    .fc-theme-pink .fc-face   { background: radial-gradient(circle at 10% 20%, #fdf2f8 0%, #fce7f3 100%); border-color: #fbcfe8; }
    .fc-theme-pink .fc-badge  { background: #fce7f3; color: #9d174d; }

    /* Swatches */
    .color-swatch-radio { display: none; }
    .color-swatch-label {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid transparent;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }
    .color-swatch-label:hover { transform: scale(1.1); }
    .color-swatch-radio:checked + .color-swatch-label { border-color: #0f172a; transform: scale(1.1); }
    .color-swatch-radio:checked + .color-swatch-label::after {
        content: '\F272';
        font-family: 'bootstrap-icons';
        font-size: 0.85rem;
        color: #0f172a;
        font-weight: bold;
    }

    /* Action Tab Pills */
    .mode-tab-btn {
        border-radius: 9999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        transition: all 0.15s ease;
    }
    .mode-tab-btn.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }

    #studyTimerPausedModal .modal-dialog,
    #studyTopicCapModal .modal-dialog {
        transform: scale(.96);
        transition: transform .22s ease-out;
    }

    #studyTimerPausedModal.show .modal-dialog,
    #studyTopicCapModal.show .modal-dialog {
        transform: scale(1);
    }

    @media (prefers-reduced-motion: reduce) {
        #studyTimerPausedModal .modal-dialog,
        #studyTopicCapModal .modal-dialog {
            transition: none;
        }
    }
</style>

<div class="review-workspace">
    
    {{-- Top Reading Progress Indicator --}}
    <div class="reading-progress-track">
        <div class="reading-progress-fill" id="readingProgressFill"></div>
    </div>

    {{-- Clean Glassmorphism Study Header --}}
    <header class="reader-navbar py-2 px-3">
        <div class="container d-flex justify-content-between align-items-center">
            
            <a href="#" onclick="window.close()" class="text-decoration-none d-flex align-items-center gap-2 fw-semibold text-secondary transition-hover small">
                <i class="bi bi-arrow-left-circle-fill fs-5"></i>
                <span class="d-none d-sm-inline">Exit Review Session</span>
                <span class="d-sm-none">Exit</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small fw-medium d-none d-md-inline">Module:</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-bold small">
                    <i class="bi bi-book-half me-1"></i>{{ $subtopic->module->title ?? 'Module' }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-1">
                <button type="button" class="reader-control-btn" id="btn-font-dec" title="Decrease font size">A-</button>
                <button type="button" class="reader-control-btn" id="btn-font-inc" title="Increase font size">A+</button>
                
                <div class="vr mx-1 my-1 opacity-25"></div>

                <button type="button" class="reader-control-btn active" id="theme-default-btn" title="Default Theme">
                    <i class="bi bi-brightness-high"></i>
                </button>
                <button type="button" class="reader-control-btn" id="theme-sepia-btn" title="Warm Sepia">
                    <i class="bi bi-cup-hot"></i>
                </button>
                <button type="button" class="reader-control-btn" id="theme-night-btn" title="Night Mode">
                    <i class="bi bi-moon-stars"></i>
                </button>
            </div>

        </div>
    </header>

    {{-- Main Document Layout --}}
    <main class="container py-4 py-md-5">
        <div class="row g-4 justify-content-center">
            
            <div class="col-lg-8 col-xl-8">
                <article class="reader-sheet" id="reader-content">
                    
                    <header class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                        <span class="text-uppercase tracking-wider text-primary fw-bold" style="font-size: 0.72rem;">Module Topic Material</span>
                        <h1 class="fw-bold mb-2 mt-1 text-slate-900" style="font-size: clamp(1.85rem, 3.5vw, 2.35rem); letter-spacing: -0.02em;">
                            {{ $subtopic->title }}
                        </h1>
                        <p class="text-secondary small mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history"></i>
                            <span>Comprehensive study material. Highlight any phrasing to summon instant AI clarifications or save directly to a flashcard.</span>
                        </p>
                    </header>

                    @if($errors->has('quiz'))
                        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 border-0 shadow-sm p-3 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <div class="small fw-semibold">{{ $errors->first('quiz') }}</div>
                        </div>
                    @endif

                    @if(! $isPdf || $subtopic->text_extraction_status === 'completed')
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3 mb-4 border" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2) !important;">
                            <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success flex-shrink-0">
                                <i class="bi bi-magic fs-5"></i>
                            </div>
                            <div class="small text-slate-700">
                                <strong class="d-block text-slate-900 mb-0.5">Interactive Context Assistant Active</strong>
                                Highlight unfamiliar words, concepts, or formulas directly inside the text to explain them or add them to the front or back of any flashcard.
                            </div>
                        </div>
                    @endif

                    @if($isPdf)
                        @if($subtopic->text_extraction_status === 'failed')
                            <div class="alert alert-warning rounded-3" role="status">
                                {{ $pdfText }}
                            </div>
                        @elseif($subtopic->text_extraction_status === 'completed')
                            <div class="pdf-text-content" id="documentBody">
                                {{ $pdfText }}
                            </div>
                        @endif
                    @else
                        <div class="pdf-text-content" id="documentBody">
                            {{ $pdfText }}
                        </div>
                    @endif

                    @if(! $isPdf && $reviewerUrl)
                        <a class="btn btn-outline-primary btn-sm mt-3" href="{{ $reviewerUrl }}" target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Open reviewer file
                        </a>
                    @endif

                </article>
            </div>

            <aside class="col-lg-4 col-xl-4">
                <div class="quiz-companion-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-patch-question-fill fs-5"></i>
                        </div>
                        <div>
                            <h2 class="h6 fw-bold mb-0 text-slate-900">Knowledge Checkpoints</h2>
                            <small class="text-secondary">Verify mastery of this topic</small>
                        </div>
                    </div>

                    <p class="text-secondary small mb-4">
                        Test your conceptual grasp immediately with a practice quiz based on this module topic.
                    </p>

                    <p id="study-tracking-status" class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 mb-3 tabular-nums" role="status" aria-live="polite">
                        Studying: {{ floor($studyTimeSeconds / 60) }}m / 30m
                        · {{ $hasTopicQuizAttempt ? 'Quiz verified' : 'Take topic quiz to verify' }}
                    </p>
                    <div id="study-time-tracker"
                         data-subtopic-id="{{ $subtopic->id }}"
                         data-start-url="{{ route('student.activity.sessions.store') }}"
                         data-heartbeat-url="{{ route('student.study-heartbeat') }}"
                         data-stop-url-template="{{ route('student.activity.sessions.stop', ['studySession' => 'SESSION_ID']) }}"
                         data-csrf-token="{{ csrf_token() }}"
                         data-used-seconds="{{ $studyTimeSeconds }}"
                         data-verified="{{ $hasTopicQuizAttempt ? 'true' : 'false' }}"
                         hidden></div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('student.learn.quiz', ['subtopic' => $subtopic, 'question_type' => 'multiple_choice']) }}"
                           id="btn-take-quiz"
                           data-question-type="multiple-choice"
                           data-quiz-topic="{{ $subtopic->title }}"
                           class="btn btn-success py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2 text-white shadow-sm rounded-pill text-decoration-none">
                            <i class="bi bi-joystick" id="quiz-btn-icon"></i>
                            <span id="quiz-btn-text">Multiple-Choice Quiz</span>
                        </a>

                        <a href="{{ route('student.learn.quiz', ['subtopic' => $subtopic, 'question_type' => 'identification']) }}"
                           id="btn-take-identification-quiz"
                           data-question-type="identification"
                           data-quiz-topic="{{ $subtopic->title }}"
                           class="btn btn-primary py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2 text-white shadow-sm rounded-pill text-decoration-none">
                            <i class="bi bi-pencil-square"></i>
                            <span>Identification Quiz</span>
                        </a>
                    </div>

                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 text-center">
                        <small class="text-muted d-block mb-1">Pass requirement: <strong>85% Score</strong></small>
                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            <i class="bi bi-shield-lock me-1"></i>Remediation Cleared
                        </span>
                    </div>
                </div>
            </aside>

        </div>
    </main>

</div>

<div class="modal fade" id="studyTimerPausedModal" tabindex="-1" aria-labelledby="studyTimerPausedTitle" aria-describedby="studyTimerPausedMessage" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 fw-bold" id="studyTimerPausedTitle">Study Timer Paused</h2>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary mb-0" id="studyTimerPausedMessage">No reading progress or page scrolling was detected. Repeated clicks in the same spot do not keep the timer active.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-primary rounded-pill px-4" id="resumeStudyTimer" type="button">Resume Studying</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="studyTopicCapModal" tabindex="-1" aria-labelledby="studyTopicCapTitle" aria-describedby="studyTopicCapMessage" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 fw-bold" id="studyTopicCapTitle">30-Minute Topic Study Limit Reached</h2>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary mb-0" id="studyTopicCapMessage">You have reached the maximum creditable study time for this topic. Complete at least 1 quiz attempt on this topic to verify your study hours for the leaderboard.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-between">
                <button class="btn btn-outline-secondary rounded-pill px-3" id="continueUntimedStudy" type="button">Continue Reading (Untimed)</button>
                <a href="{{ route('student.learn.quiz', ['subtopic' => $subtopic, 'question_type' => 'multiple_choice']) }}"
                   id="studyTopicCapQuiz"
                   data-question-type="multiple-choice"
                   data-quiz-topic="{{ $subtopic->title }}"
                   class="btn btn-primary rounded-pill px-4">
                    Take Topic Quiz
                </a>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: QUIZ PREPARATION SPINNER --}}
<div class="modal fade" id="quizGeneratingModal" tabindex="-1" aria-labelledby="quizGeneratingModalLabel" aria-describedby="quizGeneratingModalMessage" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body text-center p-5 bg-white">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                <h2 class="h5 fw-bold mb-2 text-slate-900" id="quizGeneratingModalLabel">Preparing your quiz</h2>
                <p class="text-secondary small mb-0" id="quizGeneratingModalMessage" role="status" aria-live="polite">
                    Pausing your study timer before the AI creates questions from this topic's reviewer material.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- FLOATING HIGHLIGHT TOOLTIP (ONLY VISIBLE ON TEXT SELECTION) --}}
<div id="highlight-tooltip">
    <button type="button" id="explain-selection" class="btn btn-sm btn-dark text-white rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
        <i class="bi bi-stars text-warning"></i>
        <span>Explain</span>
    </button>
    <button type="button" id="flashcard-selection" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
        <i class="bi bi-card-text"></i>
        <span>Save to Card</span>
    </button>
</div>

{{-- MODAL: CREATE / EDIT FLASHCARD WITH LIVE 3D PREVIEW --}}
<div class="modal fade" id="flashcardModal" tabindex="-1" aria-labelledby="flashcard-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="flashcard-form" method="POST" action="{{ route('student.flashcards.store') }}">
                @csrf
                <div id="flashcard-method"></div>
                <input type="hidden" name="subtopic_id" value="{{ $subtopic->id }}">

                <div class="modal-header border-bottom-0 px-4 pt-4 pb-2 bg-white">
                    <div>
                        <h2 class="h4 fw-bold text-slate-900 mb-0" id="flashcard-modal-title">Create Flashcard</h2>
                        <small class="text-secondary">Capture a key term or replace the definition of an existing card in real time.</small>
                    </div>
                    <button class="btn-close shadow-none" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 py-3 bg-white">
                    
                    {{-- CARD TARGETING BAR --}}
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-slate-700 small mb-1 d-block" for="existing-flashcard-select">
                                    <i class="bi bi-collection me-1 text-primary"></i>Choose Flashcard Target:
                                </label>
                                <select id="existing-flashcard-select" class="form-select form-select-sm rounded-pill border bg-white shadow-sm">
                                    <option value="" selected>+ Create Brand New Card</option>
                                    <?php if (isset($flashcards) && (is_array($flashcards) || $flashcards instanceof \Countable) && count($flashcards) > 0): ?>
                                        <?php foreach ($flashcards as$fc): ?>
                                            <option value="{{ $fc->id }}" 
                                                    data-front="{{ e($fc->front) }}" 
                                                    data-back="{{ e($fc->back) }}" 
                                                    data-description="{{ e($fc->description ?? '') }}" 
                                                    data-color="{{ $fc->color ?? 'blue' }}"
                                                    data-action="{{ route('student.flashcards.update', $fc) }}">
                                                Update: {{ \Illuminate\Support\Str::limit($fc->front, 40) }} ({{$fc->back ? 'Has Back' : 'Blank Back' }})
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-5 text-md-end pt-2 pt-md-0">
                                <span class="d-block text-secondary small fw-bold mb-1">Place Highlighted Text into:</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" id="btn-insert-front">
                                        <i class="bi bi-box-arrow-in-left me-1"></i>Front (Term)
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="btn-insert-back">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Back (Definition)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 align-items-center">
                        
                        {{-- LEFT COLUMN: INPUT CONTROLS --}}
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold small text-slate-700 mb-0" for="card-front">
                                            Front: Term or Question <span class="text-danger">*</span>
                                        </label>
                                        <button type="button" class="btn btn-link p-0 text-decoration-none small" style="font-size: 0.75rem;" id="clear-front-btn">Clear</button>
                                    </div>
                                    <textarea id="card-front" class="form-control rounded-3 border bg-white focus-ring p-3" name="front" rows="3" placeholder="e.g. What is Polymorphism?" required></textarea>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold small text-slate-700 mb-0" for="card-back">
                                            Back: Definition or Explanation
                                        </label>
                                        <button type="button" class="btn btn-link p-0 text-decoration-none small" style="font-size: 0.75rem;" id="clear-back-btn">Clear</button>
                                    </div>
                                    <textarea id="card-back" class="form-control rounded-3 border bg-white focus-ring p-3" name="back" rows="3" placeholder="e.g. The ability of different classes to respond to the same message in different ways..."></textarea>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label fw-bold small text-slate-700" for="card-desc">
                                        Additional Context / Mnemonics (Optional)
                                    </label>
                                    <input type="text" id="card-desc" class="form-control rounded-3 border bg-white p-2.5" name="description" placeholder="e.g. Remember OOP pillars: A-P-I-E">
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label fw-bold small text-slate-700 d-block">Deck Color Theme</label>
                                    <div class="d-flex align-items-center gap-2 pt-1">
                                        <label>
                                            <input type="radio" name="color" value="blue" class="color-swatch-radio" checked>
                                            <span class="color-swatch-label shadow-sm" style="background: #bfdbfe;" title="Blue"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="green" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #bbf7d0;" title="Green"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="yellow" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #fef08a;" title="Yellow"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="red" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #fecaca;" title="Red"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="pink" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #fbcfe8;" title="Pink"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN: EXACT REPLICA 3D PREVIEW DECK --}}
                        <div class="col-lg-5">
                            <div class="p-3 bg-light rounded-4 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.68rem; letter-spacing: 0.05em;">LIVE PREVIEW</span>
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-primary p-0 small fw-semibold" id="toggle-preview-flip">
                                        <i class="bi bi-arrow-repeat me-1"></i> Flip Card
                                    </button>
                                </div>

                                <div class="fc-scene">
                                    <div class="fc-card fc-theme-blue" id="preview-card" tabindex="0" role="button">
                                        
                                        {{-- FRONT --}}
                                        <div class="fc-face fc-front">
                                            <div class="w-100 d-flex justify-content-start align-items-center">
                                                <span class="badge fc-badge rounded-pill px-2.5 py-1 text-uppercase tracking-wider fw-bold" style="font-size: 0.65rem;">
                                                    FRONT · QUESTION
                                                </span>
                                            </div>

                                            <div class="fc-content-body">
                                                <h2 class="h5 fw-bold mb-0 text-slate-900 lh-sm" id="preview-front-text">Your question will appear here...</h2>
                                            </div>

                                            <div class="w-100 pt-2 border-top border-black-subtle border-opacity-10 d-flex align-items-center justify-content-between text-secondary" style="font-size: 0.72rem;">
                                                <span class="d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-hand-index-thumb text-primary"></i>
                                                    <span>Click or hover to flip</span>
                                                </span>
                                                <span id="preview-has-notes" class="d-none"><i class="bi bi-card-text"></i></span>
                                            </div>
                                        </div>

                                        {{-- BACK --}}
                                        <div class="fc-face fc-back">
                                            <div class="w-100 d-flex justify-content-start align-items-center">
                                                <span class="badge fc-badge rounded-pill px-2.5 py-1 text-uppercase tracking-wider fw-bold" style="font-size: 0.65rem;">
                                                    BACK · ANSWER
                                                </span>
                                            </div>

                                            <div class="fc-content-body flex-column">
                                                <p class="mb-0 fs-6 fw-semibold text-slate-900 lh-sm" id="preview-back-text">
                                                    Your answer or definition will appear here...
                                                </p>
                                                <p class="mt-2 text-secondary mb-0 small fst-italic d-none" id="preview-desc-text"></p>
                                            </div>

                                            <div class="w-100 pt-2 border-top border-black-subtle border-opacity-10 d-flex align-items-center justify-content-center text-secondary" style="font-size: 0.72rem;">
                                                <span>Reverse side</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-2 bg-white d-flex justify-content-start gap-2">
                    <button class="btn btn-outline-secondary rounded-pill px-4 fw-medium" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" type="submit" id="save-flashcard-btn">Save Flashcard</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: AI EXPLANATION ASSISTANT --}}
<div class="modal fade" id="aiExplainModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-dark text-white border-bottom-0 px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-1.5 rounded-circle bg-primary bg-opacity-25 text-primary">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                    <div>
                        <h2 class="modal-title h6 fw-bold mb-0 text-white">LEMU Concept Assistant</h2>
                        <small class="text-white-50">Contextual pedagogical explanation</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <div class="p-3 rounded-3 bg-light border mb-3">
                    <span class="text-uppercase text-secondary small fw-bold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">
                        Selected Text:
                    </span>
                    <div id="highlighted-text-display" class="fst-italic text-slate-800 fw-medium"></div>
                </div>

                <div>
                    <span class="text-uppercase text-primary small fw-bold d-block mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">
                        Explanation & Simplification:
                    </span>
                    <div id="ai-response-content" class="text-slate-800 lh-base" style="font-size: 1rem;">
                        <div class="d-flex align-items-center gap-2 text-secondary py-3">
                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            <span class="small fw-medium">The AI is simplifying this text and explaining it in context...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top px-4 py-2 bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium" data-bs-dismiss="modal">Done</button>
            </div>

        </div>
    </div>
</div>

<script>
    const explainApiUrl = "{{ route('student.learn.explain') }}";
</script>

<script src="https://cdn.jsdelivr.net/npm/katex@0.16.22/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.22/dist/contrib/auto-render.min.js"></script>
<script src="{{ asset('js/student/learn/review.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        /*
         * 1. Mouse Text Highlight & Popup Controller
         */
        const tooltip = document.getElementById('highlight-tooltip');
        const documentBody = document.getElementById('documentBody');
        let currentSelectedText = '';

        if (documentBody && typeof renderMathInElement === 'function') {
            const renderReviewerMath = function () {
                renderMathInElement(documentBody, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true },
                        { left: '$', right: '$', display: false },
                        { left: '\\[', right: '\\]', display: true },
                        { left: '\\(', right: '\\)', display: false },
                    ],
                    throwOnError: false,
                });
            };

            renderReviewerMath();

            const mathObserver = new MutationObserver(function (mutations) {
                const hasUnrenderedContent = mutations.some(function (mutation) {
                    if (mutation.type === 'characterData') {
                        return !mutation.target.parentElement?.closest('.katex');
                    }

                    return Array.from(mutation.addedNodes).some(function (node) {
                        const element = node.nodeType === Node.ELEMENT_NODE
                            ? node
                            : node.parentElement;

                        return element && !element.closest('.katex');
                    });
                });

                if (hasUnrenderedContent) {
                    window.requestAnimationFrame(renderReviewerMath);
                }
            });

            mathObserver.observe(documentBody, {
                childList: true,
                characterData: true,
                subtree: true,
            });
        }

        function handleSelection() {
            const selection = window.getSelection();
            const text = selection ? selection.toString().trim() : '';

            if (text.length > 0 && documentBody && documentBody.contains(selection.anchorNode)) {
                currentSelectedText = text;
                const range = selection.getRangeAt(0);
                const rect = range.getBoundingClientRect();

                tooltip.style.top = `${window.scrollY + rect.top - 46}px`;
                tooltip.style.left = `${window.scrollX + rect.left + (rect.width / 2) - 80}px`;
                tooltip.classList.add('is-active');
            } else {
                tooltip.classList.remove('is-active');
            }
        }

        document.addEventListener('mouseup', handleSelection);
        document.addEventListener('keyup', handleSelection);

        document.getElementById('explain-selection')?.addEventListener('click', async function (event) {
            event.preventDefault();

            if (!currentSelectedText) {
                return;
            }

            const selectedText = currentSelectedText;
            const selection = window.getSelection();
            const range = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
            const selectionNode = range?.commonAncestorContainer;
            const selectedElement = selectionNode?.nodeType === Node.ELEMENT_NODE
                ? selectionNode
                : selectionNode?.parentElement;
            const contextElement = selectedElement?.closest('p, li, h1, h2, h3, h4, blockquote, pre');
            let context = contextElement?.textContent?.trim() || '';

            if (!context && range?.startContainer.nodeType === Node.TEXT_NODE) {
                const sourceText = range.startContainer.textContent || '';
                const start = Math.max(0, range.startOffset - 600);
                const end = Math.min(sourceText.length, range.endOffset + 600);
                context = sourceText.slice(start, end).trim();
            } else if (context.length > 1500) {
                const matchIndex = context.toLocaleLowerCase().indexOf(selectedText.toLocaleLowerCase());
                const start = matchIndex >= 0 ? Math.max(0, matchIndex - 500) : 0;
                context = context.slice(start, start + 1500);
            }

            tooltip.classList.remove('is-active');
            document.getElementById('highlighted-text-display').textContent = `"${selectedText}"`;

            const responseContent = document.getElementById('ai-response-content');
            responseContent.classList.remove('text-danger');
            responseContent.innerHTML = '<div class="d-flex align-items-center gap-2 text-secondary py-3"><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span class="small fw-medium">The AI is explaining the selected text...</span></div>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('aiExplainModal')).show();

            try {
                const response = await fetch(explainApiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ text: selectedText, context }),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'The explanation could not be generated. Please try again.');
                }

                responseContent.textContent = data.explanation;
            } catch (error) {
                responseContent.textContent = error instanceof Error
                    ? error.message
                    : 'A network error occurred. Please try again.';
                responseContent.classList.add('text-danger');
            }
        });

        document.addEventListener('mousedown', function (e) {
            if (tooltip && !tooltip.contains(e.target) && documentBody && !documentBody.contains(e.target)) {
                tooltip.classList.remove('is-active');
            }
        });

        /*
         * 2. Form, Inputs & Existing Card Selector Logic
         */
        const form = document.getElementById('flashcard-form');
        const methodBox = document.getElementById('flashcard-method');
        const cardFront = document.getElementById('card-front');
        const cardBack = document.getElementById('card-back');
        const cardDesc = document.getElementById('card-desc');
        const existingSelect = document.getElementById('existing-flashcard-select');
        const modalTitle = document.getElementById('flashcard-modal-title');
        const saveBtn = document.getElementById('save-flashcard-btn');
        const flashcardModalEl = document.getElementById('flashcardModal');

        const defaultStoreUrl = "{{ route('student.flashcards.store') }}";

        // Open Modal from Highlight Selection
        document.getElementById('flashcard-selection')?.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            tooltip.classList.remove('is-active');

            // Default: Place highlighted text into Front unless user chooses otherwise
            if (currentSelectedText) {
                if (existingSelect && existingSelect.value) {
                    // If an existing card is currently targeted, place text on the back (definition)
                    cardBack.value = currentSelectedText;
                } else {
                    cardFront.value = currentSelectedText;
                }
            }

            updateLivePreview();
            bootstrap.Modal.getOrCreateInstance(flashcardModalEl).show();
        });

        // Insert Buttons inside modal
        document.getElementById('btn-insert-front')?.addEventListener('click', function () {
            if (currentSelectedText) {
                cardFront.value = currentSelectedText;
                updateLivePreview();
            }
        });

        document.getElementById('btn-insert-back')?.addEventListener('click', function () {
            if (currentSelectedText) {
                cardBack.value = currentSelectedText;
                updateLivePreview();
            }
        });

        document.getElementById('clear-front-btn')?.addEventListener('click', function () {
            cardFront.value = '';
            updateLivePreview();
        });

        document.getElementById('clear-back-btn')?.addEventListener('click', function () {
            cardBack.value = '';
            updateLivePreview();
        });

        // Switch between Creating New vs Updating Existing Card
        existingSelect?.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];

            if (this.value) {
                // Switching to UPDATE mode
                form.action = selectedOption.dataset.action;
                methodBox.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                modalTitle.textContent = 'Update Flashcard';
                saveBtn.textContent = 'Save Changes';

                cardFront.value = selectedOption.dataset.front || '';
                cardBack.value = selectedOption.dataset.back || (currentSelectedText || '');
                cardDesc.value = selectedOption.dataset.description || '';

                const color = selectedOption.dataset.color || 'blue';
                const colorRadio = document.querySelector(`input[name="color"][value="${color}"]`);
                if (colorRadio) colorRadio.checked = true;
            } else {
                // Switching back to CREATE NEW mode
                form.action = defaultStoreUrl;
                methodBox.innerHTML = '';
                modalTitle.textContent = 'Create Flashcard';
                saveBtn.textContent = 'Save Flashcard';

                cardFront.value = currentSelectedText || '';
                cardBack.value = '';
                cardDesc.value = '';

                const defaultRadio = document.querySelector('input[name="color"][value="blue"]');
                if (defaultRadio) defaultRadio.checked = true;
            }

            updateLivePreview();
        });

        /*
         * 3. Live 3D Preview Synchronizer
         */
        const previewCard = document.getElementById('preview-card');
        const previewFront = document.getElementById('preview-front-text');
        const previewBack = document.getElementById('preview-back-text');
        const previewDesc = document.getElementById('preview-desc-text');
        const previewHasNotes = document.getElementById('preview-has-notes');
        const toggleFlipBtn = document.getElementById('toggle-preview-flip');
        const colorRadios = document.querySelectorAll('input[name="color"]');

        function updateLivePreview() {
            if (!previewCard) return;

            const frontVal = cardFront?.value.trim() || '';
            previewFront.textContent = frontVal || 'Your question will appear here...';

            const backVal = cardBack?.value.trim() || '';
            previewBack.textContent = backVal || 'Your answer or definition will appear here...';

            const descVal = cardDesc?.value.trim() || '';
            if (descVal) {
                previewDesc.textContent = descVal;
                previewDesc.classList.remove('d-none');
                previewHasNotes.classList.remove('d-none');
            } else {
                previewDesc.textContent = '';
                previewDesc.classList.add('d-none');
                previewHasNotes.classList.add('d-none');
            }

            const checkedColor = document.querySelector('input[name="color"]:checked')?.value || 'blue';
            const isFlipped = previewCard.classList.contains('is-flipped');
            previewCard.className = `fc-card fc-theme-${checkedColor}` + (isFlipped ? ' is-flipped' : '');
        }

        if (cardFront) cardFront.addEventListener('input', updateLivePreview);
        if (cardBack) cardBack.addEventListener('input', updateLivePreview);
        if (cardDesc) cardDesc.addEventListener('input', updateLivePreview);
        colorRadios.forEach(radio => radio.addEventListener('change', updateLivePreview));

        if (previewCard) {
            previewCard.addEventListener('click', function () {
                this.classList.toggle('is-flipped');
            });
        }

        if (toggleFlipBtn && previewCard) {
            toggleFlipBtn.addEventListener('click', function (e) {
                e.preventDefault();
                previewCard.classList.toggle('is-flipped');
            });
        }

        /*
         * 4. Font Sizing & Theme Controls
         */
        let currentScale = 1.125;
        const root = document.documentElement;
        document.getElementById('btn-font-inc')?.addEventListener('click', function () {
            if (currentScale < 1.45) {
                currentScale += 0.075;
                root.style.setProperty('--reader-font-size', currentScale + 'rem');
            }
        });
        document.getElementById('btn-font-dec')?.addEventListener('click', function () {
            if (currentScale > 0.95) {
                currentScale -= 0.075;
                root.style.setProperty('--reader-font-size', currentScale + 'rem');
            }
        });

        const themeDefault = document.getElementById('theme-default-btn');
        const themeSepia = document.getElementById('theme-sepia-btn');
        const themeNight = document.getElementById('theme-night-btn');
        const themeBtns = [themeDefault, themeSepia, themeNight];

        function setActiveTheme(activeBtn, themeClass) {
            document.body.classList.remove('theme-sepia', 'theme-night');
            themeBtns.forEach(btn => btn?.classList.remove('active'));
            if (themeClass) document.body.classList.add(themeClass);
            activeBtn?.classList.add('active');
        }

        themeDefault?.addEventListener('click', () => setActiveTheme(themeDefault, ''));
        themeSepia?.addEventListener('click', () => setActiveTheme(themeSepia, 'theme-sepia'));
        themeNight?.addEventListener('click', () => setActiveTheme(themeNight, 'theme-night'));

        // Scroll Bar
        window.addEventListener('scroll', function () {
            const docElem = document.documentElement;
            const docBody = document.body;
            const scrollTop = docElem.scrollTop || docBody.scrollTop;
            const scrollHeight = (docElem.scrollHeight || docBody.scrollHeight) - docElem.clientHeight;
            const progress = (scrollHeight > 0) ? (scrollTop / scrollHeight) * 100 : 0;
            const fill = document.getElementById('readingProgressFill');
            if (fill) fill.style.width = Math.min(100, Math.max(0, progress)) + '%';
        }, { passive: true });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tracker = document.getElementById('study-time-tracker');
        const status = document.getElementById('study-tracking-status');
        if (!tracker || !status) {
            return;
        }

        const startUrl = tracker.dataset.startUrl;
        const heartbeatUrl = tracker.dataset.heartbeatUrl;
        const stopUrlTemplate = tracker.dataset.stopUrlTemplate;
        const csrfToken = tracker.dataset.csrfToken;
        const capModal = document.getElementById('studyTopicCapModal');
        const pausedModal = document.getElementById('studyTimerPausedModal');
        const isVerified = tracker.dataset.verified === 'true';
        let topicSeconds = Number(tracker.dataset.usedSeconds) || 0;
        let sessionId = null;
        let heartbeatTimer = null;
        let progressTimer = null;
        let requestPending = false;
        let activeRequest = null;
        let isLeavingForQuiz = false;
        let isLeavingPage = false;
        let isPausedForInactivity = false;
        let isUntimed = false;
        let lastProgressAt = performance.now();
        let lastWindowScrollY = window.scrollY;
        let lastReaderScrollTop = document.getElementById('documentBody')?.scrollTop || 0;

        function sessionUrl(template, id) {
            return template.replace('SESSION_ID', encodeURIComponent(id));
        }

        function displayDuration(seconds) {
            topicSeconds = Math.min(Number(seconds) || 0, 1800);
            const minutes = Math.floor(topicSeconds / 60);
            status.textContent = `Studying: ${minutes}m / 30m · ${isVerified ? 'Quiz verified' : 'Take topic quiz to verify'}`;
        }

        async function postJson(url, body, keepalive) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: Boolean(keepalive),
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            });
            const data = await response.json();
            if (!response.ok) {
                const requestError = new Error(data.message || 'Study-time tracking could not be updated.');
                requestError.status = response.status;
                requestError.data = data;
                throw requestError;
            }

            return data;
        }

        function showTopicCapModal() {
            sessionId = null;
            window.clearInterval(heartbeatTimer);
            heartbeatTimer = null;
            displayDuration(1800);
            bootstrap.Modal.getOrCreateInstance(capModal).show();
        }

        function progressFromScroll(event, readerScroll = false) {
            if (!event.isTrusted) {
                return;
            }

            const position = readerScroll
                ? event.currentTarget.scrollTop
                : window.scrollY;
            const previousPosition = readerScroll ? lastReaderScrollTop : lastWindowScrollY;
            if (Math.abs(position - previousPosition) < 16) {
                return;
            }

            if (readerScroll) {
                lastReaderScrollTop = position;
            } else {
                lastWindowScrollY = position;
            }
            lastProgressAt = performance.now();
        }

        function pauseForNoProgress() {
            if (!sessionId || isPausedForInactivity || isLeavingForQuiz || isLeavingPage) {
                return;
            }

            isPausedForInactivity = true;
            status.textContent = `Study timer paused · ${Math.floor(topicSeconds / 60)}m / 30m`;
            stopSession('inactive', false).then((data) => {
                if (!data?.capped) {
                    bootstrap.Modal.getOrCreateInstance(pausedModal).show();
                }
            });
        }

        async function startSession() {
            if (
                sessionId
                || requestPending
                || document.visibilityState !== 'visible'
                || !document.hasFocus()
                || isLeavingForQuiz
                || isLeavingPage
                || isPausedForInactivity
                || isUntimed
                || topicSeconds >= 1800
            ) {
                return;
            }
            requestPending = true;

            const operation = (async function () {
                try {
                    const data = await postJson(startUrl, { subtopic_id: tracker.dataset.subtopicId });
                    sessionId = data.session_id;
                    displayDuration(data.active_seconds);
                    if (data.capped || data.remaining_seconds <= 0) {
                        showTopicCapModal();
                        return;
                    }
                    lastProgressAt = performance.now();
                    heartbeatTimer = window.setInterval(sendHeartbeat, 30000);
                } catch (startError) {
                    status.textContent = `Study-time tracking unavailable: ${startError.message}`;
                } finally {
                    requestPending = false;
                    activeRequest = null;
                }
            }());
            activeRequest = operation;

            return operation;
        }

        async function sendHeartbeat() {
            if (
                !sessionId
                || requestPending
                || document.visibilityState !== 'visible'
                || !document.hasFocus()
                || isPausedForInactivity
                || performance.now() - lastProgressAt >= 45000
            ) {
                if (sessionId && performance.now() - lastProgressAt >= 45000) {
                    pauseForNoProgress();
                }
                return;
            }
            requestPending = true;
            let shouldRestart = false;

            const operation = (async function () {
                try {
                    const data = await postJson(heartbeatUrl, { session_id: sessionId });
                    displayDuration(data.active_seconds);
                    if (data.capped || data.remaining_seconds <= 0) {
                        showTopicCapModal();
                    }
                } catch (heartbeatError) {
                    if (heartbeatError.data?.capped) {
                        showTopicCapModal();
                    } else if (heartbeatError.status === 409) {
                        sessionId = null;
                        window.clearInterval(heartbeatTimer);
                        heartbeatTimer = null;
                        shouldRestart = true;
                    } else {
                        status.textContent = `Study-time tracking paused: ${heartbeatError.message}`;
                    }
                } finally {
                    requestPending = false;
                    activeRequest = null;
                }
            }());
            activeRequest = operation;
            await operation;

            if (shouldRestart && document.visibilityState === 'visible' && !isLeavingForQuiz && !isLeavingPage) {
                await startSession();
            }
        }

        async function stopSession(reason, keepalive) {
            if (activeRequest) {
                await activeRequest;
            }
            if (!sessionId) {
                return;
            }

            const activeSessionId = sessionId;
            sessionId = null;
            window.clearInterval(heartbeatTimer);
            heartbeatTimer = null;
            requestPending = true;

            let stopData = null;
            const operation = (async function () {
                try {
                    const data = await postJson(sessionUrl(stopUrlTemplate, activeSessionId), { reason }, keepalive);
                    stopData = data;
                    displayDuration(data.active_seconds);
                    if (
                        data.capped
                        && reason !== 'quiz'
                        && reason !== 'pagehide'
                        && document.visibilityState === 'visible'
                        && document.hasFocus()
                    ) {
                        showTopicCapModal();
                    }
                    if (reason === 'quiz') {
                        status.textContent = `Quiz started · ${Math.floor(data.active_seconds / 60)}m / 30m`;
                    } else if (reason === 'hidden') {
                        status.textContent = `Timer paused while this page is hidden · ${Math.floor(data.active_seconds / 60)}m / 30m`;
                    }
                } catch (stopError) {
                    status.textContent = `Study-time tracking could not be stopped cleanly: ${stopError.message}`;
                } finally {
                    requestPending = false;
                    activeRequest = null;
                }
            }());
            activeRequest = operation;

            await operation;

            return stopData;
        }

        window.addEventListener('scroll', (event) => progressFromScroll(event), { passive: true });
        const readerBody = document.getElementById('documentBody');
        if (readerBody) {
            readerBody.addEventListener('scroll', (event) => progressFromScroll(event, true), { passive: true });
        }

        function resumeSessionWhenVisible() {
            if (
                document.visibilityState === 'visible'
                && document.hasFocus()
                && !isLeavingForQuiz
                && !isPausedForInactivity
            ) {
                lastProgressAt = performance.now();
                startSession();
            }
        }

        document.getElementById('resumeStudyTimer').addEventListener('click', (event) => {
            if (!event.isTrusted) {
                return;
            }
            isPausedForInactivity = false;
            lastProgressAt = performance.now();
            bootstrap.Modal.getOrCreateInstance(pausedModal).hide();
            startSession();
        });

        document.getElementById('continueUntimedStudy').addEventListener('click', () => {
            isUntimed = true;
            window.clearInterval(progressTimer);
            status.textContent = '30-minute study limit reached · Reading continues untimed';
            bootstrap.Modal.getOrCreateInstance(capModal).hide();
        });

        document.querySelectorAll('#btn-take-quiz, #btn-take-identification-quiz, #studyTopicCapQuiz').forEach(function (link) {
            link.addEventListener('click', async function (event) {
                event.preventDefault();
                if (isLeavingForQuiz) {
                    return;
                }
                isLeavingForQuiz = true;
                const questionType = link.dataset.questionType === 'identification' ? 'identification' : 'multiple-choice';
                const modal = document.getElementById('quizGeneratingModal');
                document.getElementById('quizGeneratingModalLabel').textContent = `Preparing your ${questionType} quiz`;
                document.getElementById('quizGeneratingModalMessage').textContent = `Pausing your study timer before the AI generates 10 ${questionType} questions from ${link.dataset.quizTopic}'s reviewer material.`;
                bootstrap.Modal.getOrCreateInstance(modal).show();
                await stopSession('quiz', false);
                document.getElementById('quizGeneratingModalMessage').textContent = `The AI is generating 10 ${questionType} questions from ${link.dataset.quizTopic}'s reviewer material. This may take a moment.`;
                window.location.assign(link.href);
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                stopSession('hidden', true).then(resumeSessionWhenVisible);
            } else if (!isLeavingForQuiz && !isPausedForInactivity && document.hasFocus()) {
                lastProgressAt = performance.now();
                startSession();
            }
        });

        window.addEventListener('blur', function () {
            stopSession('hidden', true).then(resumeSessionWhenVisible);
        });

        window.addEventListener('focus', function () {
            if (document.visibilityState === 'visible' && !isLeavingForQuiz && !isPausedForInactivity) {
                lastProgressAt = performance.now();
                startSession();
            }
        });

        window.addEventListener('pagehide', function () {
            isLeavingPage = true;
            stopSession('pagehide', true);
        });

        progressTimer = window.setInterval(() => {
            if (
                sessionId
                && document.visibilityState === 'visible'
                && document.hasFocus()
                && performance.now() - lastProgressAt >= 45000
            ) {
                pauseForNoProgress();
            }
        }, 1000);

        if (topicSeconds >= 1800) {
            showTopicCapModal();
        }
        startSession();
    });
</script>
@endsection