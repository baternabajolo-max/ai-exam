@extends('layouts.app')

@section('hide-navbar', true)
@section('hide-nav', true)

@section('content')
<style>
    :root {
        --slate-900: #0F172A;
        --slate-800: #1E293B;
        --slate-700: #334155;
        --slate-500: #64748B;
        --slate-200: #E2E8F0;
        --slate-100: #F1F5F9;
        --slate-50:  #F8FAFC;
        --indigo-600: #4F46E5;
        --indigo-700: #4338CA;
        --indigo-50:  #EEF2FF;
        --emerald-600: #059669;
        --emerald-50:  #ECFDF5;
        --rose-600:    #E11D48;
        --rose-50:     #FFF1F2;
    }

    body {
        background-color: var(--slate-50);
        color: var(--slate-800);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    /* Sticky Control HUD */
    .quiz-sticky-header {
        position: sticky;
        top: 0;
        z-index: 1040;
        backdrop-filter: blur(12px);
        background-color: rgba(255, 255, 255, 0.92);
        border-bottom: 1px solid var(--slate-200);
    }

    .exam-countdown {
        font-variant-numeric: tabular-nums;
        min-width: 6.25rem;
        justify-content: center;
    }

    /* Interactive Radio Cards */
    .option-card-label {
        transition: all 160ms cubic-bezier(0.16, 1, 0.3, 1);
        border: 1.5px solid var(--slate-200);
        background-color: #FFFFFF;
        color: var(--slate-700);
        cursor: pointer;
        user-select: none;
    }

    .option-card-label:hover {
        border-color: #94A3B8;
        background-color: #F8FAFC;
        transform: translateY(-1px);
    }

    .form-check-input:checked + .option-card-label {
        border-color: var(--indigo-600) !important;
        background-color: var(--indigo-50) !important;
        color: var(--indigo-700) !important;
        box-shadow: 0 0 0 1px var(--indigo-600), 0 4px 12px rgba(79, 70, 229, 0.12) !important;
    }

    .form-check-input:checked + .option-card-label .option-letter-badge {
        background-color: var(--indigo-600) !important;
        color: #FFFFFF !important;
        border-color: var(--indigo-600) !important;
    }

    .option-letter-badge {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
        border: 1.5px solid var(--slate-200);
        transition: all 140ms ease;
    }

    /* Identification Inputs */
    .identification-answer-box {
        letter-spacing: 0.08em;
        font-weight: 700;
        border: 2px solid var(--slate-200);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        background-color: #FFFFFF;
        color: var(--slate-900);
        transition: all 150ms ease;
    }

    .identification-answer-box:focus {
        border-color: var(--indigo-600);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        background-color: #FFFFFF;
        outline: none;
    }

    /* Custom Buttons */
    .btn-indigo {
        background-color: var(--indigo-600);
        border-color: var(--indigo-600);
        color: #FFFFFF;
        font-weight: 600;
        transition: all 150ms ease;
    }

    .btn-indigo:hover, .btn-indigo:focus-visible {
        background-color: var(--indigo-700);
        border-color: var(--indigo-700);
        color: #FFFFFF;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
    }

    /* Step Progress Indicator */
    .batch-step-pill {
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        background-color: var(--slate-100);
        color: var(--slate-500);
        border: 1px solid var(--slate-200);
    }

    .batch-step-pill.active {
        background-color: var(--indigo-50);
        color: var(--indigo-600);
        border-color: rgba(79, 70, 229, 0.3);
    }
</style>

{{-- ========================================================================= --}}
{{-- 1. ASYNCHRONOUS OVERLAY (Generation & Response Evaluation)                --}}
{{-- ========================================================================= --}}
<div id="test-status-overlay" 
     data-preparing-title="{{ $test->is_general ? 'AI is generating your general test' : 'AI is generating your topic quiz' }}"
     data-preparing-message="{{ $test->is_general ? 'The AI is generating 30 personalized questions from your available course modules and subtopics. This may take a moment.' : 'The AI is generating 20 practice questions about ' . $test->topic . ' from your reviewer material. This may take a moment.' }}"
     data-evaluating-message="{{ $test->is_general ? 'Your responses are being scored, and the AI is preparing personalized guidance based on your results.' : 'Your responses are being scored and your results are being saved.' }}"
     class="position-fixed top-0 start-0 w-100 h-100 {{ $test->questions->count() === 0 ? 'd-flex' : 'd-none' }} justify-content-center align-items-center bg-slate-900 bg-opacity-75" 
     role="dialog" aria-modal="true" aria-labelledby="test-status-title" aria-describedby="test-status-message"
     style="z-index: 1050; backdrop-filter: blur(8px);">
    <div class="card border-0 shadow-lg p-4 p-md-5 text-center rounded-4 bg-white" style="max-width: 440px;">
        <div id="test-status-spinner" class="spinner-border text-indigo mx-auto mb-4" role="status" style="width: 3rem; height: 3rem; color: var(--indigo-600);"></div>
        <h2 class="h5 fw-bold mb-2" id="test-status-title" style="color: var(--slate-900);">
            {{ $test->questions->count() === 0 ? ($test->is_general ? 'AI is generating your general test' : 'AI is generating your topic quiz') : 'Checking your answers' }}
        </h2>
        <p class="text-muted small mb-0 lh-lg" id="test-status-message">
            {{ $test->questions->count() === 0 ? ($test->is_general ? 'The AI is generating 30 personalized questions from your available course modules and subtopics. This may take a moment.' : 'The AI is generating 20 practice questions about ' . $test->topic . ' from your reviewer material. This may take a moment.') : ($test->is_general ? 'Your responses are being scored, and the AI is preparing personalized guidance based on your results.' : 'Your responses are being scored and your results are being saved.') }}
        </p>
        
        <div id="test-generation-error" class="d-none mt-4" role="alert">
            <p id="test-generation-error-message" class="alert alert-danger text-start mb-3 border-0 small rounded-3" style="background-color: var(--rose-50); color: var(--rose-600); border: 1px solid #FECDD3 !important;"></p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <button id="retry-test-generation" type="button" class="btn btn-indigo rounded-pill px-4 py-2 small">Try again</button>
                <a href="{{ route('student.learn.index') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 small">Back to Learn</a>
            </div>
        </div>
    </div>
</div>

@if($test->questions->count() > 0)
    {{-- ========================================================================= --}}
    {{-- 2. STICKY EXAMINATION HUD                                                --}}
    {{-- ========================================================================= --}}
    <aside class="quiz-sticky-header py-3 mb-4 shadow-sm" aria-label="Assessment Control Bar">
        <div class="container-xl d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: var(--indigo-50); color: var(--indigo-600); font-size: 0.72rem; letter-spacing: 0.05em;">
                    Assessment In Progress
                </span>
                <span class="text-muted d-none d-sm-inline">•</span>
                <span class="fw-semibold text-truncate d-none d-sm-inline" style="max-width: 320px; color: var(--slate-800);">
                    {{ $test->title }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                @if($remainingSeconds !== null)
                    <div class="d-flex flex-column align-items-center lh-sm" role="timer" aria-label="Time remaining">
                        <span class="small fw-semibold text-muted d-none d-sm-inline">Time remaining</span>
                        <time id="exam-countdown"
                              class="badge rounded-3 bg-light text-dark border exam-countdown"
                              data-remaining-seconds="{{ $remainingSeconds }}"
                              aria-live="off">{{ gmdate('H:i:s', $remainingSeconds) }}</time>
                    </div>
                @endif
                @unless($test->is_scheduled_exam)
                    <a href="{{ route('student.learn.index') }}"
                       data-confirm-title="Leave this quiz?"
                       data-confirm-text="Your quiz progress will not be saved."
                       data-confirm-button="Exit Quiz"
                       class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill fw-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                        </svg>
                        <span>Exit Quiz</span>
                    </a>
                @endunless
            </div>
        </div>
    </aside>
@endif

<div class="container-xl py-3 py-md-4">
    
    <!-- Assessment Metadata Header -->
    <header class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white" style="border: 1px solid var(--slate-200) !important;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge rounded-pill fw-semibold" style="background-color: var(--indigo-50); color: var(--indigo-600); font-size: 0.72rem;">
                            {{ $test->is_general ? 'General Assessment' : 'Topic Quiz' }}
                        </span>
                        <span class="text-muted small">•</span>
                        <span class="text-muted small">{{ $test->questions->count() }} Total Questions</span>
                    </div>
                    <h1 class="h3 fw-bold mb-1" style="color: var(--slate-900); letter-spacing: -0.02em;">
                        {{ $test->title }}
                    </h1>
                    <p class="text-muted small mb-0">{{ $test->description }}</p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-light text-secondary rounded-3 px-3 py-2 border fw-medium d-inline-flex align-items-center gap-1.5" style="border-color: var(--slate-200) !important;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <span>{{ count($test->questions->chunk(10)) }} Batch Section(s)</span>
                    </span>
                </div>
            </div>
        </div>
    </header>

    @if($test->is_scheduled_exam)
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <div>
                <strong>Stay on this exam page.</strong>
                Switching tabs, closing the tab, or leaving this page submits your current answers automatically.
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- 3. TEST FORM & QUESTION BATCH CONTAINER                                   --}}
    {{-- ========================================================================= --}}
    <form id="test-form" action="{{ route('student.tests.submit', $test->id) }}" method="POST">
        @csrf

        <div id="test-batches-container">
            @php
                $chunks = $test->questions->chunk(10);
            @endphp

            @if($chunks->count() > 0)
                @foreach($chunks as $index => $batch)
                    @php $batchNum = $index + 1; @endphp
                    
                    <div class="batch-section" id="batch-{{ $batchNum }}" style="{{ $batchNum > 1 ? 'display:none;' : '' }}">
                        
                        <!-- Batch Banner & Steps HUD -->
                        <div class="card border-0 shadow-xs rounded-4 mb-4 overflow-hidden" style="background-color: var(--indigo-50); border: 1px solid rgba(79, 70, 229, 0.2) !important;">
                            <div class="card-body p-3.5 p-md-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="batch-step-pill active">Batch {{ $batchNum }} of {{ count($chunks) }}</span>
                                    </div>
                                    <div class="fw-bold fs-6" style="color: var(--slate-900);">
                                        Showing items {{ ($index * 10) + 1 }} to {{ min(($index + 1) * 10, $test->questions->count()) }} of {{ $test->questions->count() }}
                                    </div>
                                </div>
                                <div class="progress w-100 style-progress" style="max-width: 180px; height: 8px; background-color: rgba(79, 70, 229, 0.15);">
                                    <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ ($batchNum / count($chunks)) * 100 }}%; background-color: var(--indigo-600);" aria-valuenow="{{ $batchNum }}" aria-valuemin="0" aria-valuemax="{{ count($chunks) }}"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Question Items -->
                        @foreach($batch->values() as $qIndex => $question)
                            @php
                                $questionNumber = ($index * 10) + $qIndex + 1;
                                $cleanQuestion = preg_replace('/^(Q?\d+[\.\)\-:]\s*)+/i', '', trim($question->question));
                                $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;
                                $questionType = $test->is_general
                                    ? \App\Models\Test::QUESTION_TYPE_MULTIPLE_CHOICE
                                    : ($question->question_type ?? $test->question_type);
                            @endphp

                            <article class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white question-item" data-batch="{{ $batchNum }}" style="border: 1px solid var(--slate-200) !important;">
                                <div class="card-body p-4 p-md-5">
                                    
                                    <div class="d-flex align-items-start gap-3 mb-4">
                                        <span class="badge rounded-pill d-inline-flex justify-content-center align-items-center flex-shrink-0 fw-bold" style="width: 34px; height: 34px; background-color: var(--indigo-50); color: var(--indigo-600); font-size: 0.85rem;">
                                            {{ $questionNumber }}
                                        </span>
                                        <h2 class="h5 fw-bold mb-0 lh-base pt-0.5" style="color: var(--slate-900);">
                                            {{ $cleanQuestion }}
                                        </h2>
                                    </div>
                                    @if ($question->image_path)
                                        <img src="{{ asset('storage/'.$question->image_path) }}" alt="Question illustration" class="img-fluid rounded border mb-4 ms-5" style="max-height: 24rem; object-fit: contain; object-position: left;">
                                    @endif

                                    @if($questionType === \App\Models\Test::QUESTION_TYPE_IDENTIFICATION)
                                        <div class="col-12">
                                            <label class="form-label text-uppercase small fw-semibold text-muted mb-2" for="answer-{{ $question->id }}" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                                Enter Exact Term or Concept (Auto-Capitalized)
                                            </label>
                                            <input
                                                id="answer-{{ $question->id }}"
                                                type="text"
                                                name="answers[{{ $question->id }}]"
                                                class="form-control identification-answer identification-answer-box question-input-b{{ $batchNum }}"
                                                placeholder="TYPE YOUR ANSWER"
                                                style="text-transform: uppercase;"
                                                autocomplete="off"
                                                autocapitalize="characters"
                                                required
                                            >
                                        </div>
                                    @else
                                        <div class="row g-3">
                                            @foreach($options as $optIndex => $option)
                                                <div class="col-md-6">
                                                    <input 
                                                        type="radio" 
                                                        class="btn-check form-check-input question-input-b{{ $batchNum }}" 
                                                        name="answers[{{ $question->id }}]" 
                                                        id="q{{ $question->id }}_opt{{ $optIndex }}" 
                                                        value="{{ $option }}" 
                                                        required
                                                    >
                                                    <label class="w-100 p-3 rounded-3 option-card-label d-flex align-items-center" for="q{{ $question->id }}_opt{{ $optIndex }}">
                                                        <span class="option-letter-badge rounded-circle d-inline-flex justify-content-center align-items-center me-3 fw-bold flex-shrink-0" style="background-color: var(--slate-100); color: var(--slate-700);">
                                                            {{ chr(65 + $optIndex) }}
                                                        </span>
                                                        <span class="fs-6 fw-medium lh-sm">{{ $option }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            </article>
                        @endforeach

                        <!-- Batch Navigation Bar -->
                        <div class="d-flex justify-content-between align-items-center my-5 p-3.5 rounded-4 bg-white border shadow-sm" style="border-color: var(--slate-200) !important;">
                            @if($batchNum > 1)
                                <button type="button" class="btn btn-outline-secondary px-4 py-2.5 rounded-pill fw-semibold prev-batch" data-target="{{ $batchNum - 1 }}">
                                    ← Previous Batch
                                </button>
                            @else
                                <div></div>
                            @endif

                            @if($batchNum < count($chunks))
                                <button type="button" class="btn btn-indigo px-5 py-2.5 rounded-pill fw-bold shadow-sm next-batch" data-current="{{ $batchNum }}" data-target="{{ $batchNum + 1 }}">
                                    Next Batch →
                                </button>
                            @else
                                <button type="submit" class="btn btn-success px-5 py-3 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-2" id="submit-btn" style="background-color: var(--emerald-600); border-color: var(--emerald-600);">
                                    <span>Submit Test</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                    </svg>
                                </button>
                            @endif
                        </div>

                    </div>
                @endforeach
            @endif
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionCount = {{ $test->questions->count() }};
    const testStatusOverlay = document.getElementById('test-status-overlay');
    const retryTestGenerationButton = document.getElementById('retry-test-generation');
    const testStatusTitle = document.getElementById('test-status-title');
    const testStatusMessage = document.getElementById('test-status-message');
    const testStatusSpinner = document.getElementById('test-status-spinner');
    const testGenerationError = document.getElementById('test-generation-error');
    const testGenerationErrorMessage = document.getElementById('test-generation-error-message');
    const examCountdown = document.getElementById('exam-countdown');
    const isScheduledExam = @json($test->is_scheduled_exam);
    let countdownInterval = null;
    let examSubmissionStarted = false;

    function submitWhenTimeExpires() {
        const form = document.getElementById('test-form');
        if (!form) {
            return;
        }

        if (countdownInterval !== null) {
            window.clearInterval(countdownInterval);
        }
        examSubmissionStarted = true;
        document.getElementById('test-status-title').textContent = 'Time is up';
        document.getElementById('test-status-message').textContent = 'Your answers are being submitted.';
        testStatusOverlay.classList.replace('d-none', 'd-flex');
        HTMLFormElement.prototype.submit.call(form);
    }

    if (examCountdown) {
        let remainingSeconds = Number.parseInt(examCountdown.dataset.remainingSeconds, 10);
        const countdownDeadline = Date.now() + remainingSeconds * 1000;
        const updateCountdown = function() {
            remainingSeconds = Math.max(0, Math.ceil((countdownDeadline - Date.now()) / 1000));
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            const formattedTime = [hours, minutes, seconds]
                .map((value) => String(value).padStart(2, '0'))
                .join(':');

            examCountdown.textContent = formattedTime;
            examCountdown.dataset.remainingSeconds = String(remainingSeconds);
            examCountdown.classList.toggle('text-danger', remainingSeconds <= 300);
            examCountdown.setAttribute('aria-label', `Time remaining ${formattedTime}`);

            if (remainingSeconds <= 0) {
                submitWhenTimeExpires();
            }
        };

        updateCountdown();
        if (remainingSeconds > 0) {
            countdownInterval = window.setInterval(updateCountdown, 1000);
        }
    }

    if (isScheduledExam) {
        function submitExamOnLeave() {
            const form = document.getElementById('test-form');
            if (examSubmissionStarted || !form) {
                return;
            }

            examSubmissionStarted = true;
            if (countdownInterval !== null) {
                window.clearInterval(countdownInterval);
            }
            const submission = new FormData(form);
            if (!navigator.sendBeacon || !navigator.sendBeacon(form.action, submission)) {
                fetch(form.action, {
                    method: 'POST',
                    body: submission,
                    credentials: 'same-origin',
                    keepalive: true
                }).catch(function (error) {
                    console.error('Could not submit the examination while leaving the page.', error);
                });
            }
        }

        let submittedAfterTabSwitch = false;
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                if (!examSubmissionStarted) {
                    submittedAfterTabSwitch = true;
                    submitExamOnLeave();
                }

                return;
            }

            if (submittedAfterTabSwitch) {
                const form = document.getElementById('test-form');
                if (!form) {
                    return;
                }

                submittedAfterTabSwitch = false;
                if (countdownInterval !== null) {
                    window.clearInterval(countdownInterval);
                }
                testStatusTitle.textContent = 'Your exam was submitted';
                testStatusMessage.textContent = 'You left the exam page, so your current answers have been submitted.';
                testStatusOverlay.classList.replace('d-none', 'd-flex');
                HTMLFormElement.prototype.submit.call(form);
            }
        });

        window.addEventListener('pagehide', submitExamOnLeave);
    }

    function showTestGenerationError(message) {
        testStatusSpinner.classList.add('d-none');
        testStatusTitle.textContent = 'We could not prepare your test';
        testStatusMessage.textContent = 'Your answers have not been started or saved. You can try generating the questions again.';
        testGenerationErrorMessage.textContent = message;
        testGenerationError.classList.remove('d-none');
        retryTestGenerationButton.disabled = false;
    }

    async function buildTestQuestions() {
        retryTestGenerationButton.disabled = true;
        testGenerationError.classList.add('d-none');
        testStatusSpinner.classList.remove('d-none');
        testStatusTitle.textContent = testStatusOverlay.dataset.preparingTitle;
        testStatusMessage.textContent = testStatusOverlay.dataset.preparingMessage;
        testStatusOverlay.classList.replace('d-none', 'd-flex');

        try {
            const response = await fetch("{{ route('student.tests.build', $test->id) }}", {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = response.headers.get('content-type')?.includes('application/json')
                ? await response.json()
                : {};

            if (!response.ok || (data.status !== 'success' && data.status !== 'already_generated')) {
                throw new Error(data.message || 'The server could not prepare your test. Please try again.');
            }

            window.location.reload();
        } catch (error) {
            const message = error instanceof Error && !(error instanceof SyntaxError)
                ? error.message
                : 'We could not get a valid response from the server. Check that LM Studio is running, then try again.';
            showTestGenerationError(message);
        }
    }

    if (questionCount === 0) {
        buildTestQuestions();
        retryTestGenerationButton.addEventListener('click', buildTestQuestions);
    }

    function questionIsAnswered(question) {
        const selectedOption = question.querySelector('input[type="radio"]:checked');
        const typedAnswer = question.querySelector('.identification-answer');

        return Boolean(selectedOption || (typedAnswer && typedAnswer.value.trim() !== ''));
    }

    document.querySelectorAll('.identification-answer').forEach(function(input) {
        input.addEventListener('input', function() {
            input.value = input.value.toLocaleUpperCase();
        });
    });

    // Batch Navigation & Validation
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('next-batch')) {
            let currentBatch = e.target.getAttribute('data-current');
            let targetBatch = e.target.getAttribute('data-target');
            
            const questionsInBatch = Array.from(document.querySelectorAll('#batch-' + currentBatch + ' .question-item'));

            if (questionsInBatch.some(question => !questionIsAnswered(question))) {
                window.showToast('Please answer all questions in this batch before proceeding to the next batch.', 'warning', 'Questions still unanswered');
                return;
            }

            document.getElementById('batch-' + currentBatch).style.display = 'none';
            document.getElementById('batch-' + targetBatch).style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        if (e.target.classList.contains('prev-batch')) {
            let targetBatch = e.target.getAttribute('data-target');
            document.querySelectorAll('.batch-section').forEach(el => el.style.display = 'none');
            document.getElementById('batch-' + targetBatch).style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // Final Submit Validation across all items
    let testForm = document.getElementById('test-form');
    if (testForm) {
        testForm.addEventListener('submit', function(e) {
            if (examCountdown && Number.parseInt(examCountdown.dataset.remainingSeconds, 10) <= 0) {
                return;
            }

            const allQuestions = Array.from(document.querySelectorAll('.question-item'));
            if (allQuestions.some(question => !questionIsAnswered(question))) {
                e.preventDefault();
                window.showToast('Please answer every question across all batches before submitting.', 'warning', 'Questions still unanswered');
                return;
            }

            document.getElementById('test-status-title').textContent = 'Checking your answers';
            document.getElementById('test-status-message').textContent = document.getElementById('test-status-overlay').dataset.evaluatingMessage;
            document.getElementById('test-status-overlay').classList.replace('d-none', 'd-flex');
            examSubmissionStarted = true;
        });
    }
});
</script>
@endsection