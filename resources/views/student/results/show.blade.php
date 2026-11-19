@extends('layouts.app')

<!-- Hides default navigation bars -->
@section('hide-navbar', true)
@section('hide-nav', true)

@push('styles')
<style>
    :root {
        --emerald-900: #064E3B;
        --emerald-800: #065F46;
        --emerald-700: #047857;
        --emerald-600: #059669;
        --emerald-500: #10B981;
        --emerald-100: #D1FAE5;
        --emerald-50:  #ECFDF5;
        
        --slate-900: #0F172A;
        --slate-800: #1E293B;
        --slate-700: #334155;
        --slate-500: #64748B;
        --slate-200: #E2E8F0;
        --slate-100: #F1F5F9;
        --slate-50:  #F8FAFC;

        --rose-600:  #E11D48;
        --rose-100:  #FFE4E6;
        --rose-50:   #FFF1F2;
    }

    /* Force-hide global application navbar/header */
    .navbar, header.navbar, nav {
        display: none !important;
    }

    body {
        background-color: #F4F7F5;
        color: var(--slate-800);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    /* Score Gauge Surface */
    .hero-score-panel {
        background: #FFFFFF;
        border: 1px solid var(--slate-200);
        position: relative;
    }

    .hero-score-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--emerald-600), var(--emerald-500));
    }

    .score-meter-circle {
        width: 154px;
        height: 154px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.15);
        background: #FFFFFF;
    }

    /* Metric Cards */
    .metric-badge-box {
        background-color: var(--slate-50);
        border: 1px solid var(--slate-200);
        border-radius: 14px;
        padding: 0.9rem 1.25rem;
        transition: all 140ms ease;
    }
    .metric-badge-box:hover {
        background-color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    /* Green Primary Buttons */
    .btn-emerald-primary {
        background-color: var(--emerald-600) !important;
        border-color: var(--emerald-600) !important;
        color: #FFFFFF !important;
        font-weight: 600;
        transition: all 150ms cubic-bezier(0.16, 1, 0.3, 1);
    }
    .btn-emerald-primary:hover, .btn-emerald-primary:focus-visible {
        background-color: var(--emerald-700) !important;
        border-color: var(--emerald-700) !important;
        color: #FFFFFF !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(5, 150, 105, 0.25) !important;
    }

    .btn-emerald-outline {
        border: 1.5px solid var(--emerald-600) !important;
        color: var(--emerald-700) !important;
        background-color: #FFFFFF !important;
        font-weight: 600;
        transition: all 150ms ease;
    }
    .btn-emerald-outline:hover, .btn-emerald-outline:focus-visible {
        background-color: var(--emerald-50) !important;
        color: var(--emerald-800) !important;
        border-color: var(--emerald-700) !important;
    }

    .option-letter-badge {
        width: 34px;
        height: 34px;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .question-review-card {
        background: #FFFFFF;
        border: 1px solid var(--slate-200);
        transition: transform 140ms ease, box-shadow 140ms ease;
    }
    .question-review-card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    /* Print Styles */
    @media print {
        .no-print, .navbar, footer { display: none !important; }
        .container { max-width: 100% !important; padding: 0 !important; }
        .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>
@endpush

@section('content')
@php
    $totalQuestions = count($questionsData);
    $currentScore = $result->score;
    $incorrectCount = max(0, $totalQuestions - $currentScore);
    $percentage = $totalQuestions > 0 ? round(($currentScore / $totalQuestions) * 100) : 0;
    $isPassing = $percentage >= 75;
@endphp

<div class="container-xl py-4 py-md-5">

    <!-- HERO SECTION: Score Summary -->
    <section class="card border-0 rounded-4 shadow-sm mb-5 overflow-hidden hero-score-panel">
        
        <!-- Top Toolbar -->
        <div class="py-3 px-4 d-flex justify-content-between align-items-center border-bottom bg-white">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill fw-semibold text-uppercase px-2.5 py-1" style="background-color: var(--emerald-50); color: var(--emerald-700); font-size: 0.72rem; letter-spacing: 0.06em; border: 1px solid var(--emerald-100);">
                    Performance Summary
                </span>
                <span class="text-muted small">•</span>
                <span class="fw-semibold small" style="color: var(--slate-800);">
                    {{ $result->test?->is_general ? 'General Assessment' : 'Topic Review' }}
                </span>
            </div>

            @unless($isAdminView)
                <a href="{{ route('student.results.print', $result) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-medium no-print d-inline-flex align-items-center gap-1.5 bg-white shadow-2xs" style="border-color: var(--slate-200);">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/>
                </svg>
                <span>Print Results</span>
                </a>
            @endunless
        </div>

        <div class="card-body p-4 p-md-5 text-center">
            
            <!-- Result Tag -->
            <div class="mb-4">
                <span class="badge rounded-pill px-3 py-1.5 fw-bold text-uppercase" style="background-color: {{ $isPassing ? 'var(--emerald-50)' : 'var(--rose-50)' }}; color: {{ $isPassing ? 'var(--emerald-700)' : 'var(--rose-600)' }}; border: 1px solid {{ $isPassing ? 'var(--emerald-100)' : 'var(--rose-100)' }}; font-size: 0.76rem; letter-spacing: 0.05em;">
                    {{ $isPassing ? 'Target Mastery Met' : 'Remediation Recommended' }}
                </span>
            </div>

            <!-- Emerald Radial Score Circle -->
            <div class="score-meter-circle mb-3" style="border: 4.5px solid {{ $isPassing ? 'var(--emerald-500)' : 'var(--rose-600)' }};">
                <span class="display-5 fw-bold mb-0" style="color: {{ $isPassing ? 'var(--emerald-700)' : 'var(--rose-600)' }};">
                    {{ $currentScore }}
                </span>
                <span class="text-muted small fw-semibold">of {{ $totalQuestions }} correct</span>
            </div>

            <h2 class="h3 fw-bold mb-1" style="color: var(--slate-900);">
                {{ $percentage }}% Overall Accuracy
            </h2>
            <p class="text-muted small mb-4">
                Score recorded for <strong>{{ $result->test->title ?? 'this quiz' }}</strong>
            </p>

            <!-- 3 Metric Cards Strip -->
            <div class="row g-3 justify-content-center mb-4" style="max-width: 600px; margin: 0 auto;">
                <div class="col-4">
                    <div class="metric-badge-box text-center">
                        <span class="text-uppercase fw-semibold text-muted d-block small" style="font-size: 0.7rem; letter-spacing: 0.04em;">Correct</span>
                        <span class="fs-5 fw-bold" style="color: var(--emerald-600);">{{ $currentScore }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="metric-badge-box text-center">
                        <span class="text-uppercase fw-semibold text-muted d-block small" style="font-size: 0.7rem; letter-spacing: 0.04em;">Incorrect</span>
                        <span class="fs-5 fw-bold" style="color: var(--rose-600);">{{ $incorrectCount }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="metric-badge-box text-center">
                        <span class="text-uppercase fw-semibold text-muted d-block small" style="font-size: 0.7rem; letter-spacing: 0.04em;">Total Items</span>
                        <span class="fs-5 fw-bold" style="color: var(--slate-900);">{{ $totalQuestions }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap justify-content-center gap-3 no-print pt-2">
                @if(! $isAdminView && $isPassing && ! $result->test?->is_general && $result->test?->subtopic)
                    <a href="{{ route('student.learn.review', $result->test->subtopic) }}" class="btn btn-emerald-primary rounded-pill px-5 py-2.5 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                        <span>Review again</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                    </a>
                @endif

                @if(! $isAdminView && $result->test?->is_general)
                    <a href="{{ route('student.tests.generate-general') }}"
                       data-ai-loading
                       data-ai-loading-title="Preparing your general test"
                       data-ai-loading-message="Setting up your personalized assessment. The AI will generate questions from your module materials next."
                       class="btn btn-emerald-primary rounded-pill px-5 py-2.5 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                        <span>Take test again</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                    </a>
                @endif

                <a
                    href="{{ $returnUrl }}"
                    class="btn btn-emerald-outline rounded-pill px-5 py-2.5 fw-semibold shadow-sm"
                    @if (! $isAdminView && $result->test?->is_scheduled_exam)
                        data-close-exam-tab
                    @endif
                >
                    {{ $returnLabel }}
                </a>
            </div>

        </div>
    </section>

    @if (! $isAdminView && $questionsData !== [])
        <section class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden" aria-labelledby="study-advice-heading" style="border-left: 4px solid #2563eb !important;">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14a6 6 0 1 0-6.18 0c.58.44.96 1.06 1.09 2h4c.13-.94.51-1.56 1.09-2Z"/>
                    </svg>
                    <h3 class="h5 fw-bold mb-0" id="study-advice-heading" style="color: var(--slate-900);">Personalized study advice</h3>
                </div>
                <p class="text-muted mb-4">{{ $studyAdvice['summary'] }}</p>

                @if ($studyAdvice['focus_areas'] !== [])
                    <h4 class="h6 fw-bold mb-3" style="color: var(--slate-900);">Focus areas</h4>
                    <ul class="list-unstyled d-grid gap-3 mb-4">
                        @foreach ($studyAdvice['focus_areas'] as $focusArea)
                            <li class="rounded-3 border bg-light p-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                    <h5 class="h6 fw-bold mb-0" style="color: var(--slate-900);">{{ $focusArea['topic'] }}</h5>
                                    <span class="badge rounded-pill bg-white text-secondary border">{{ $focusArea['accuracy'] }}% accuracy</span>
                                </div>
                                <p class="small text-muted mb-0">
                                    You answered {{ $focusArea['correct'] }} of {{ $focusArea['total'] }} correctly.
                                    @if ($focusArea['focus_points'] !== [])
                                        Revisit: {{ implode(', ', $focusArea['focus_points']) }}.
                                    @else
                                        Revisit the concepts behind your missed answers.
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($studyAdvice['strength_areas'] !== [])
                    <p class="small text-success mb-4">
                        <strong>Going well:</strong>
                        @foreach ($studyAdvice['strength_areas'] as $strengthArea)
                            {{ $strengthArea['topic'] }} ({{ $strengthArea['accuracy'] }}%){{ $loop->last ? '.' : ', ' }}
                        @endforeach
                    </p>
                @endif

                @if ($studyAdvice['answer_pattern'] !== null)
                    <div class="rounded-3 p-3 mb-4" style="background-color: #eff6ff;">
                        <h4 class="h6 fw-bold mb-1" style="color: var(--slate-900);">Pattern in your answers</h4>
                        @if ($studyAdvice['answer_pattern']['type'] === 'repeated_choice')
                            <p class="small text-muted mb-0">
                                You selected <strong>{{ $studyAdvice['answer_pattern']['selected_answer'] }}</strong>
                                instead of <strong>{{ $studyAdvice['answer_pattern']['correct_answer'] }}</strong>
                                on {{ $studyAdvice['answer_pattern']['count'] }} missed questions. These may be easy to mix up, so compare when each answer applies.
                            </p>
                        @else
                            <p class="small text-muted mb-0">{{ $studyAdvice['answer_pattern']['message'] }}</p>
                        @endif
                    </div>
                @endif

                <h4 class="h6 fw-bold mb-2" style="color: var(--slate-900);">Three practical ways to review</h4>
                <ol class="small text-muted mb-0 ps-3">
                    @foreach ($studyAdvice['practical_steps'] as $practicalStep)
                        <li class="mb-2">{{ $practicalStep }}</li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <!-- DETAILED REVIEW SECTION -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="h5 fw-bold mb-1" style="color: var(--slate-900);">Detailed Question Analysis</h3>
            <p class="text-muted small mb-0">Review correct answers, your choices, and conceptual rationales.</p>
        </div>
        <span class="badge bg-white text-secondary rounded-pill px-3 py-1.5 border fw-medium shadow-2xs">
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

        <article class="card question-review-card rounded-4 mb-4 overflow-hidden" style="border-left: 5px solid {{ $userAnswer ? ($isCorrect ? 'var(--emerald-600)' : 'var(--rose-600)') : '#F59E0B' }} !important;">
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
                                    $surfaceStyle = 'background-color: var(--emerald-50); border: 1.5px solid var(--emerald-500); color: var(--emerald-900);';
                                    $badgeStyle = 'background-color: var(--emerald-600); color: #FFFFFF; border: 1px solid var(--emerald-600);';
                                    $stateIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ms-auto flex-shrink-0"><polyline points="20 6 9 17 4 12"/></svg>';
                                } elseif ($isUserChoice && !$isCorrect) {
                                    $surfaceStyle = 'background-color: var(--rose-50); border: 1.5px solid #F43F5E; color: #9F1239;';
                                    $badgeStyle = 'background-color: #F43F5E; color: #FFFFFF; border: 1px solid #F43F5E;';
                                    $stateIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F43F5E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ms-auto flex-shrink-0"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>';
                                }
                            @endphp

                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 rounded-3 h-100" style="{{ $surfaceStyle }}">
                                    <span class="rounded-circle d-inline-flex justify-content-center align-items-center me-3 fw-bold option-letter-badge" style="{{ $badgeStyle }}">
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
                @endif

                <!-- Explanation Box -->
                <div class="p-3.5 p-md-4 rounded-3" style="background-color: {{ $isCorrect ? 'var(--emerald-50)' : 'var(--rose-50)' }}; border: 1px solid {{ $isCorrect ? 'var(--emerald-100)' : 'var(--rose-100)' }};">
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
                            <p class="mb-0 small lh-lg" style="color: var(--slate-700);">
                                {{ $data['explanation'] ?? 'No detailed explanation was provided for this question.' }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </article>
    @endforeach

</div>
@endsection

@if (! $isAdminView && $result->test?->is_scheduled_exam)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const returnLink = document.querySelector('[data-close-exam-tab]');

                if (!returnLink) {
                    return;
                }

                returnLink.addEventListener('click', function (event) {
                    event.preventDefault();

                    if ('BroadcastChannel' in window) {
                        const examUpdates = new BroadcastChannel('lemu-exam-updates');
                        examUpdates.postMessage({ type: 'exam-submitted' });
                        examUpdates.close();
                    }

                    if (window.opener && !window.opener.closed) {
                        window.opener.location.reload();
                    }

                    window.close();

                    window.setTimeout(function () {
                        if (!window.closed) {
                            window.location.assign(returnLink.href);
                        }
                    }, 100);
                });
            });
        </script>
    @endpush
@endif