@extends('layouts.app')

<!-- Tells layouts.app to skip rendering the navbar -->
@section('hide-navbar', true)
@section('hide-nav', true)

@section('content')
@php
    // 1. Check if the user just submitted the quiz and a result was flashed to the page
    $resultId = session('completed_result_id');
    $result = $resultId ? \App\Models\Result::find($resultId) : null;
@endphp

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
        --emerald-600: #059669;
        --emerald-50:  #ECFDF5;
        --rose-600:    #E11D48;
        --rose-50:     #FFF1F2;
        --amber-500:   #F59E0B;
    }

    .navbar, header.navbar, nav {
        display: none !important;
    }

    body {
        background-color: var(--slate-50);
        color: var(--slate-800);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    /* Sticky Examination Bar (Hidden automatically in results mode) */
    .quiz-sticky-header {
        position: sticky;
        top: 0;
        z-index: 1020;
        backdrop-filter: blur(12px);
        background-color: rgba(255, 255, 255, 0.92);
        border-bottom: 1px solid var(--slate-200);
    }

    /* Option Radio Cards */
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

    .btn-check:checked + .option-card-label {
        border-color: var(--indigo-600) !important;
        background-color: #EEF2FF !important;
        color: var(--indigo-700) !important;
        box-shadow: 0 0 0 1px var(--indigo-600), 0 4px 12px rgba(79, 70, 229, 0.12) !important;
    }

    .btn-check:checked + .option-card-label .option-letter-badge {
        background-color: var(--indigo-600) !important;
        color: #FFFFFF !important;
        border-color: var(--indigo-600) !important;
    }

    .option-letter-badge {
        width: 34px;
        height: 34px;
        font-size: 0.85rem;
        border: 1.5px solid var(--slate-200);
        transition: all 140ms ease;
    }

    /* Identification Input */
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
    }

    /* Modern Buttons */
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

    /* Score Ring Gauge */
    .score-hero-container {
        background: #FFFFFF;
        border: 1px solid var(--slate-200);
        position: relative;
    }

    .score-circle-badge {
        width: 148px;
        height: 148px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06);
    }

    .stat-pill {
        background-color: var(--slate-50);
        border: 1px solid var(--slate-200);
        border-radius: 12px;
        padding: 0.85rem 1.25rem;
    }

    .breakdown-card {
        background: #FFFFFF;
        border: 1px solid var(--slate-200);
        transition: transform 140ms ease, box-shadow 140ms ease;
    }
    .breakdown-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
    }
</style>

{{-- ========================================================================= --}}
{{-- 1. TEST MODE: Show Test Header & Sticky Exam HUD                          --}}
{{-- ========================================================================= --}}
@if(!$result)
    <!-- Sticky HUD during test taking -->
    <aside class="quiz-sticky-header py-3 mb-4 shadow-sm" aria-label="Assessment Control Bar">
        <div class="container-xl d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #EEF2FF; color: var(--indigo-600); font-size: 0.72rem; letter-spacing: 0.05em;">
                    Exam In Progress
                </span>
                <span class="text-muted d-none d-sm-inline">•</span>
                <span class="fw-semibold text-truncate d-none d-sm-inline" style="max-width: 320px; color: var(--slate-800);">
                    {{ $test->title }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                @if($test->time_limit)
                    <div class="small fw-semibold text-muted d-none d-md-flex align-items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>{{ $test->time_limit }} mins limit</span>
                    </div>
                @endif
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
            </div>
        </div>
    </aside>
@endif

<div class="container-xl py-3 py-md-4">
    
    @if($result)
        {{-- ========================================================================= --}}
        {{-- 2. RESULTS MODE: (Header is hidden; results UI takes full focus)          --}}
        {{-- ========================================================================= --}}
        @php
            $isGeneralTest = (bool) $test->is_general;
            $questionsData = session('quiz_snapshot', []);
            if (empty($questionsData) || !is_array($questionsData)) {
                $questionsData = [];
            }
            
            $totalQuestions = count($questionsData);
            $currentScore = $result->score;
            $incorrectCount = max(0, $totalQuestions - $currentScore);
            $percentage = $totalQuestions > 0 ? round(($currentScore / $totalQuestions) * 100) : 0;
            $isPassing = $percentage >= 85; // 85% aligns with the required mastery threshold
            
            // Points calculation logic
            $currentPoints = $totalQuestions > 0 ? round(($currentScore / $totalQuestions) * 30) : 0;

            $previousHighestScore = \App\Models\Result::where('user_id', auth()->id())
                ->where('test_id', $result->test_id)
                ->where('id', '!=', $result->id)
                ->max('score');

            $isFirstAttempt = is_null($previousHighestScore);
            $beatHighScore = $isFirstAttempt || ($currentScore > $previousHighestScore);
        @endphp

        <!-- HERO SCORE CARD -->
        <section class="card border-0 rounded-4 shadow-sm mb-5 overflow-hidden score-hero-container">
            <div class="p-4 p-md-5 text-center">
                
                <!-- Performance Badge -->
                <div class="mb-4">
                    @if($isPassing)
                        <span class="badge rounded-pill px-3 py-1.5 fw-bold text-uppercase" style="background-color: var(--emerald-50); color: var(--emerald-600); border: 1px solid #A7F3D0; letter-spacing: 0.05em; font-size: 0.76rem;">
                            Mastery Threshold Achieved (85%+)
                        </span>
                    @else
                        <span class="badge rounded-pill px-3 py-1.5 fw-bold text-uppercase" style="background-color: var(--rose-50); color: var(--rose-600); border: 1px solid #FECDD3; letter-spacing: 0.05em; font-size: 0.76rem;">
                            Remediation Recommended
                        </span>
                    @endif
                </div>

                <!-- Circular Score Indicator -->
                <div class="score-circle-badge mb-3" style="background-color: {{ $isPassing ? 'var(--emerald-50)' : 'var(--rose-50)' }}; border: 4px solid {{ $isPassing ? '#10B981' : '#F43F5E' }};">
                    <span class="display-5 fw-bold mb-0" style="color: {{ $isPassing ? 'var(--emerald-600)' : 'var(--rose-600)' }};">
                        {{ $currentScore }}
                    </span>
                    <span class="text-muted small fw-semibold">out of {{ $totalQuestions }}</span>
                </div>

                <h2 class="h3 fw-bold mb-1" style="color: var(--slate-900);">
                    {{ $percentage }}% Overall Accuracy
                </h2>
                <p class="text-muted small mb-4">
                    Assessment performance report for <strong>{{ $test->title ?? ($isGeneralTest ? 'General Test' : 'Topic Quiz') }}</strong>
                </p>

                <!-- 3-Column Score Metrics Strip -->
                <div class="row g-3 justify-content-center mb-4" style="max-width: 680px; margin: 0 auto;">
                    <div class="col-4">
                        <div class="stat-pill text-center">
                            <span class="text-uppercase fw-semibold text-muted d-block small" style="font-size: 0.7rem; letter-spacing: 0.04em;">Correct</span>
                            <span class="fs-5 fw-bold text-success">{{ $currentScore }}</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-pill text-center">
                            <span class="text-uppercase fw-semibold text-muted d-block small" style="font-size: 0.7rem; letter-spacing: 0.04em;">Incorrect</span>
                            <span class="fs-5 fw-bold text-danger">{{ $incorrectCount }}</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-pill text-center">
                            <span class="text-uppercase fw-semibold text-muted d-block small" style="font-size: 0.7rem; letter-spacing: 0.04em;">Total Items</span>
                            <span class="fs-5 fw-bold" style="color: var(--slate-900);">{{ $totalQuestions }}</span>
                        </div>
                    </div>
                </div>

                <!-- Points & High Score Feedback -->
                <div class="mb-4 d-flex flex-column align-items-center">
                    @if($beatHighScore)
                        <div class="d-inline-flex align-items-center gap-2 px-3.5 py-2 rounded-pill shadow-xs" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="1.5">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <span class="fw-bold small">{{ $currentPoints }} / 30 Mastery Points Earned</span>
                        </div>
                        @if(!$isFirstAttempt)
                            <div class="text-success small fw-bold mt-2 d-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                                <span>New Personal High Score!</span>
                            </div>
                        @endif
                    @else
                        <div class="d-inline-flex align-items-center gap-2 px-3.5 py-2 rounded-pill shadow-xs" style="background-color: #F1F5F9; border: 1px solid var(--slate-200); color: var(--slate-700);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <span class="fw-medium small">0 Points Earned</span>
                        </div>
                        <div class="text-muted small mt-2">
                            Beat your previous score of <strong class="text-dark">{{ $previousHighestScore }}</strong> on this test to earn points.
                        </div>
                    @endif
                </div>

                <!-- Navigation CTAs -->
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 pt-2">
                    <a href="{{ route('student.learn.index') }}" class="btn btn-outline-secondary px-4 py-2.5 rounded-pill fw-semibold shadow-sm bg-white" style="border-color: #CBD5E1;">
                        Return to Modules
                    </a>
                    
                    @if($isGeneralTest)
                        <a href="{{ route('student.tests.generate-general') }}"
                           data-ai-loading
                           data-ai-loading-title="Preparing your general test"
                           data-ai-loading-message="Setting up your personalized assessment. The AI will generate questions from your module materials next."
                           class="btn btn-indigo px-4 py-2.5 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                            <span>Take Another General Test</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        </a>
                    @else
                        @php
                            $currentSubtopic = \App\Models\Subtopic::where('title', $test->topic)->first();
                        @endphp

                        @if($currentSubtopic)
                            <a href="{{ route('student.learn.quiz', ['subtopic' => $currentSubtopic->id, 'question_type' => $test->question_type]) }}"
                               data-ai-loading
                               data-ai-loading-title="Preparing your {{ $test->question_type === 'identification' ? 'identification' : 'multiple-choice' }} quiz"
                               data-ai-loading-message="The AI is creating questions from this topic's reviewer material. This may take a moment."
                               class="btn btn-indigo px-4 py-2.5 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                <span>Take Another Quiz</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                            </a>
                        @else
                            <a href="{{ url()->current() }}" class="btn btn-indigo px-4 py-2.5 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                <span>Take Another Quiz</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                            </a>
                        @endif
                    @endif
                </div>

            </div>
        </section>

        <!-- AI STUDY ADVICE -->
        @if(session('study_advice'))
            <section class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden" style="border-left: 4px solid var(--indigo-600) !important;">
                <div class="card-body p-4 p-md-5" style="background-color: #FAFAFC;">
                    <div class="d-flex align-items-center gap-2.5 mb-3">
                        <div class="p-2 rounded-3" style="background-color: #EEF2FF; color: var(--indigo-600);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>
                            </svg>
                        </div>
                        <h4 class="h6 fw-bold mb-0 text-uppercase tracking-wide" style="color: var(--indigo-600); letter-spacing: 0.05em;">
                            AI Adaptive Remediation Advice
                        </h4>
                    </div>
                    <div class="lh-lg" style="color: var(--slate-800); white-space: pre-line; font-size: 0.95rem;">
                        {{ session('study_advice') }}
                    </div>
                </div>
            </section>
        @endif

        <!-- QUESTION CORRECTIONS BREAKDOWN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="h5 fw-bold mb-1" style="color: var(--slate-900);">Question Breakdown &amp; Explanations</h3>
                <p class="text-muted small mb-0">Detailed view of answers, selected choices, and conceptual rationales.</p>
            </div>
            <span class="badge bg-white text-secondary rounded-pill px-3 py-1.5 border fw-medium shadow-xs">
                {{ $totalQuestions }} Questions
            </span>
        </div>

        @foreach($questionsData as $data)
            @php
                $isCorrect = $data['is_correct'] ?? false;
                $userAnswer = $data['user_answer'] ?? null;
                $actualCorrect = $data['correct_answer'] ?? '';
                $options = isset($data['options']) ? array_values($data['options']) : [];
                $cleanQuestion = $data['question_text'] ?? 'Unknown Question';
            @endphp

            <article class="card breakdown-card rounded-4 mb-4 overflow-hidden" style="border-left: 5px solid {{ $userAnswer ? ($isCorrect ? 'var(--emerald-600)' : 'var(--rose-600)') : 'var(--amber-500)' }} !important;">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                        <h5 class="fw-bold mb-0 lh-base" style="color: var(--slate-900);">
                            <span class="{{ $isCorrect ? 'text-success' : 'text-danger' }} me-2">{{ $loop->iteration }}.</span>
                            {{ $cleanQuestion }}
                        </h5>
                        <span class="badge {{ $isCorrect ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} px-2.5 py-1.5 rounded-pill fw-semibold flex-shrink-0" style="font-size: 0.72rem;">
                            {{ $isCorrect ? 'Correct' : ($userAnswer ? 'Incorrect' : 'Unanswered') }}
                        </span>
                    </div>

                    @if(!empty($options))
                        <div class="row g-3 mb-4">
                            @foreach($options as $optIndex => $option)
                                @php
                                    $optionTrimmed = trim($option);
                                    $isUserChoice = (strcasecmp($userAnswer ?? '', $optionTrimmed) === 0);
                                    $isActualCorrectOption = (strcasecmp($actualCorrect, $optionTrimmed) === 0);
                                    
                                    $surfaceStyle = 'background-color: #FFFFFF; border: 1.5px solid var(--slate-200); color: var(--slate-700);';
                                    $badgeStyle = 'background-color: var(--slate-100); color: var(--slate-700); border: 1px solid var(--slate-200);';
                                    $stateIcon = '';

                                    if ($isActualCorrectOption) {
                                        $surfaceStyle = 'background-color: #ECFDF5; border: 1.5px solid #10B981; color: #065F46;';
                                        $badgeStyle = 'background-color: #10B981; color: #FFFFFF; border: 1px solid #10B981;';
                                        $stateIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ms-auto flex-shrink-0"><polyline points="20 6 9 17 4 12"/></svg>';
                                    } elseif ($isUserChoice && !$isCorrect) {
                                        $surfaceStyle = 'background-color: #FFF1F2; border: 1.5px solid #F43F5E; color: #9F1239;';
                                        $badgeStyle = 'background-color: #F43F5E; color: #FFFFFF; border: 1px solid #F43F5E;';
                                        $stateIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F43F5E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ms-auto flex-shrink-0"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>';
                                    }
                                @endphp

                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 rounded-3 h-100" style="{{ $surfaceStyle }}">
                                        <span class="rounded-circle d-inline-flex justify-content-center align-items-center me-3 fw-bold option-letter-badge flex-shrink-0" style="{{ $badgeStyle }}">
                                            {{ chr(65 + (int)$optIndex) }}
                                        </span>
                                        <span class="fs-6 {{ $isActualCorrectOption ? 'fw-bold' : ($isUserChoice ? 'fw-bold' : '') }}">
                                            {{ $optionTrimmed }}
                                        </span>
                                        {!! $stateIcon !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Identification Question Layout -->
                        <div class="p-3.5 rounded-3 mb-4" style="background-color: #F8FAFC; border: 1px solid var(--slate-200);">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <span class="text-uppercase small fw-semibold text-muted d-block" style="font-size: 0.72rem;">Your Answer</span>
                                    <div class="fw-bold {{ $isCorrect ? 'text-success' : 'text-danger' }}">
                                        {{ $userAnswer ?: '(No response submitted)' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-uppercase small fw-semibold text-muted d-block" style="font-size: 0.72rem;">Correct Answer</span>
                                    <div class="fw-bold text-success">
                                        {{ $actualCorrect }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Rationale Box -->
                    <div class="p-3.5 p-md-4 rounded-3" style="background-color: {{ $isCorrect ? '#F0FDF4' : '#FEF2F2' }}; border: 1px solid {{ $isCorrect ? '#DCFCE7' : '#FFE4E6' }};">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-1.5 rounded-2 flex-shrink-0" style="background-color: #FFFFFF; color: {{ $isCorrect ? 'var(--emerald-600)' : 'var(--rose-600)' }};">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/>
                                </svg>
                            </div>
                            <div class="w-100">
                                @if(!$isCorrect)
                                    <div class="text-danger fw-bold small text-uppercase tracking-wide mb-1" style="letter-spacing: 0.04em;">
                                        Incorrect Submission
                                    </div>
                                    <div class="small mb-2 text-dark">
                                        Submitted: <strong class="text-danger">{{ $userAnswer ?: 'Left Blank' }}</strong> &nbsp;|&nbsp; Correct: <strong class="text-success">{{ $actualCorrect }}</strong>
                                    </div>
                                @else
                                    <div class="text-success fw-bold small text-uppercase tracking-wide mb-1" style="letter-spacing: 0.04em;">
                                        Correct Submission
                                    </div>
                                @endif
                                <div class="text-muted fw-semibold small text-uppercase tracking-wider mb-1" style="font-size: 0.72rem;">Rationale</div>
                                <p class="mb-0 text-slate-800 small lh-lg" style="color: var(--slate-700);">
                                    {{ $data['explanation'] ?? 'No detailed explanation was provided for this question.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </article>
        @endforeach

    @else
        {{-- ========================================================================= --}}
        {{-- 3. TEST TAKING MODE                                                       --}}
        {{-- ========================================================================= --}}
        <header class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white" style="border: 1px solid var(--slate-200) !important;">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill fw-semibold" style="background-color: #EEF2FF; color: var(--indigo-600); font-size: 0.72rem;">
                                Active Examination
                            </span>
                            <span class="text-muted small">•</span>
                            <span class="text-muted small">{{ count($test->questions) }} Total Items</span>
                        </div>
                        <h1 class="h3 fw-bold mb-1" style="color: var(--slate-900); letter-spacing: -0.02em;">
                            {{ $test->title }}
                        </h1>
                        <p class="text-muted small mb-0">{{ $test->description }}</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        @if($test->time_limit)
                            <span class="badge bg-light text-secondary rounded-3 px-3 py-2 border fw-medium d-inline-flex align-items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>{{ $test->time_limit }} Minutes</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <form action="{{ route('student.tests.submit', $test->id) }}"
              method="POST"
              data-ai-loading
              data-ai-loading-title="{{ $test->is_general ? 'Checking your answers and preparing study advice' : 'Checking your answers' }}"
              data-ai-loading-message="{{ $test->is_general ? 'Your responses are being scored, and the AI is preparing personalized guidance based on your results.' : 'Your responses are being scored and your results are being saved.' }}">
            @csrf
            
            @foreach($test->questions->shuffle()->values() as $index => $question)
                <input type="hidden" name="question_order[]" value="{{ $question->id }}">
                
                <article class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white" style="border: 1px solid var(--slate-200) !important;">
                    <div class="card-body p-4 p-md-5">
                        
                        @php
                            $cleanQuestion = preg_replace('/^(Q?\d+[\.\)\-]?\s*)+/i', '', trim($question->question));
                        @endphp
                        
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <span class="badge rounded-pill d-inline-flex justify-content-center align-items-center flex-shrink-0 fw-bold" style="width: 32px; height: 32px; background-color: #EEF2FF; color: var(--indigo-600); font-size: 0.85rem;">
                                {{ $index + 1 }}
                            </span>
                            <h2 class="h5 fw-bold mb-0 lh-base pt-0.5" style="color: var(--slate-900);">
                                {{ $cleanQuestion }}
                            </h2>
                        </div>
                        @if ($question->image_path)
                            <img src="{{ asset('storage/'.$question->image_path) }}" alt="Question illustration" class="img-fluid rounded border mb-4 ms-5" style="max-height: 24rem; object-fit: contain; object-position: left;">
                        @endif

                        <div class="row g-3">
                            @php
                                $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;
                            @endphp
                            
                            @if(($question->question_type ?? $test->question_type) === \App\Models\Test::QUESTION_TYPE_IDENTIFICATION)
                                <div class="col-12">
                                    <label class="form-label text-uppercase small fw-semibold text-muted mb-2" for="answer-{{ $question->id }}" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        Enter Exact Term or Concept (Auto-Capitalized)
                                    </label>
                                    <input
                                        id="answer-{{ $question->id }}"
                                        type="text"
                                        name="answers[{{ $question->id }}]"
                                        class="form-control identification-answer identification-answer-box"
                                        placeholder="TYPE YOUR ANSWER"
                                        style="text-transform: uppercase;"
                                        autocomplete="off"
                                        autocapitalize="characters"
                                        required
                                    >
                                </div>
                            @else
                                @foreach($options as $optIndex => $option)
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="answers[{{ $question->id }}]" id="q{{ $question->id }}_opt{{ $optIndex }}" value="{{ $option }}" required>
                                        <label class="w-100 p-3 rounded-3 option-card-label d-flex align-items-center" for="q{{ $question->id }}_opt{{ $optIndex }}">
                                            <span class="option-letter-badge bg-light rounded-circle d-inline-flex justify-content-center align-items-center me-3 fw-bold flex-shrink-0">
                                                {{ chr(65 + $optIndex) }}
                                            </span>
                                            <span class="fs-6 fw-medium lh-sm">{{ $option }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                    </div>
                </article>
            @endforeach

            <!-- Action Bar -->
            <div class="d-flex justify-content-between align-items-center mb-5 p-3 rounded-4 bg-white border shadow-sm" style="border-color: var(--slate-200) !important;">
                <span class="text-muted small ps-2">Ensure all items are completed before submission.</span>
                <button type="submit" class="btn btn-indigo px-5 py-3 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <span>Submit Quiz</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </div>
        </form>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.identification-answer').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = input.value.toLocaleUpperCase();
        });
    });
});
</script>
@endsection