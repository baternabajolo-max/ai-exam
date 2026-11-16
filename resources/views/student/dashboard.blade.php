@extends('layouts.app')

@push('styles')
<style>
    .student-dashboard {
        --dash-primary: var(--student-theme, #2563eb);
        --dash-primary-hover: color-mix(in srgb, var(--dash-primary) 82%, #000);
        --dash-primary-soft: color-mix(in srgb, var(--dash-primary) 22%, white);
        --dash-border: #e2e8f0;
        background: linear-gradient(180deg, color-mix(in srgb, var(--dash-primary) 18%, white), #f8fafc 28rem);
        min-height: calc(100vh - 72px);
        color: #0f172a;
    }

    .student-dashboard .dash-card {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .student-dashboard .dash-card:hover {
        border-color: color-mix(in srgb, var(--dash-primary) 28%, var(--dash-border));
        box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
    }

    .student-dashboard .stat-icon-wrapper {
        width: 2.75rem;
        height: 2.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        background: var(--dash-primary-soft);
        color: var(--dash-primary);
        font-size: 1.25rem;
    }

    .student-dashboard .dashboard-heading {
        letter-spacing: -.035em;
    }

    .student-dashboard .college-badge {
        background: color-mix(in srgb, var(--dash-primary) 24%, white);
        border: 1px solid color-mix(in srgb, var(--dash-primary) 42%, white);
        color: var(--dash-primary-hover);
        font-weight: 700;
    }

    .student-dashboard .curriculum-progress {
        background: linear-gradient(115deg, var(--dash-primary-soft), #fff 54%);
        border: 1px solid color-mix(in srgb, var(--dash-primary) 18%, var(--dash-border));
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
    }

    .student-dashboard .curriculum-progress .progress {
        background: color-mix(in srgb, var(--dash-primary) 12%, white);
    }

    .student-dashboard .curriculum-progress .progress-bar {
        background-color: var(--dash-primary);
    }

    .student-dashboard .dashboard-action {
        border-color: var(--dash-primary);
        color: var(--dash-primary-hover);
    }

    .student-dashboard .dashboard-action:hover {
        background: var(--dash-primary);
        border-color: var(--dash-primary);
        color: #fff;
    }

    /* Chart Canvas & Grid */
    .student-dashboard .chart-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1.5rem 1.25rem 1rem;
        position: relative;
    }

    .student-dashboard .analytics-summary-card {
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--dash-primary) 18%, #e2e8f0);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        height: 100%;
        transform-style: preserve-3d;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .student-dashboard .analytics-summary-card:hover {
        border-color: color-mix(in srgb, var(--dash-primary) 42%, #e2e8f0);
        box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
        transform: translateY(-3px) rotateX(1.5deg) rotateY(-1deg);
    }

    .student-dashboard .analytics-summary-value {
        color: var(--dash-primary-hover);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 800;
        letter-spacing: -.04em;
    }

    .student-dashboard .analytics-chart-card {
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--dash-primary) 18%, #e2e8f0);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        color: #0f172a;
    }

    .student-dashboard .analytics-summary-grid {
        perspective: 1200px;
    }

    .student-dashboard .analytics-chart-scroll {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .student-dashboard .analytics-performance-chart {
        display: block;
        height: auto;
        min-width: 0;
        width: 100%;
    }

    .student-dashboard .general-test-chart-scroll {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .student-dashboard .analytics-chart-grid {
        stroke: #e2e8f0;
        stroke-dasharray: 4 5;
    }

    .student-dashboard .analytics-chart-target {
        stroke: var(--dash-primary);
        stroke-dasharray: 7 5;
        stroke-width: 2;
    }

    .student-dashboard .analytics-chart-bar {
        rx: 5;
        transition: filter .2s ease;
    }

    .student-dashboard .analytics-chart-bar:hover {
        filter: brightness(1.12);
    }

    .student-dashboard .chart-tracks {
        position: absolute;
        inset: 1.5rem 1.25rem 3.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        pointer-events: none;
    }

    .student-dashboard .chart-track-line {
        border-bottom: 1px dashed #e2e8f0;
        width: 100%;
        position: relative;
    }

    .student-dashboard .chart-track-label {
        position: absolute;
        right: 0;
        top: -12px;
        font-size: 0.65rem;
        color: #94a3b8;
        font-weight: 600;
    }

    .student-dashboard .bar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: clamp(0.5rem, 2vw, 1.25rem);
        height: 220px;
        align-items: end;
        position: relative;
        z-index: 1;
        padding-bottom: 2.25rem;
    }

    .student-dashboard .bar-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
        position: relative;
    }

    .student-dashboard .bar-pill {
        width: clamp(1.25rem, 50%, 2.5rem);
        border-radius: 0.5rem 0.5rem 0.25rem 0.25rem;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), filter 0.2s ease;
        transform-origin: bottom;
    }

    .student-dashboard .bar-col:hover .bar-pill {
        transform: scaleY(1.03);
        filter: brightness(0.95);
    }

    .student-dashboard .bar-val-badge {
        font-size: 0.72rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
        color: #334155;
    }

    .student-dashboard .bar-day-name {
        position: absolute;
        bottom: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Chart Bar Color Classes */
    .bar-tier-high   { background: #059669; }
    .bar-tier-medium { background: var(--dash-primary); }
    .bar-tier-low    { background: #ef4444; }
    .bar-tier-zero   { background: #cbd5e1; height: 3px !important; }

    /* Legend */
    .student-dashboard .legend-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #475569;
        padding: 0.25rem 0.65rem;
        background: #f1f5f9;
        border-radius: 9999px;
    }

    .student-dashboard .legend-dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
    }

    /* Test History Items */
    .student-dashboard .history-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.15rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        color: inherit;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .student-dashboard .history-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        transform: translateY(-2px);
        color: inherit;
    }

    .student-dashboard .history-card:focus-visible {
        outline: 2px solid var(--dash-primary);
        outline-offset: 2px;
    }

    .student-dashboard .index-badge {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.6rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        color: #475569;
        flex-shrink: 0;
    }

    .student-dashboard .score-pill {
        padding: 0.25rem 0.6rem;
        border-radius: 0.5rem;
        font-size: 0.78rem;
        font-weight: 700;
        text-align: right;
        display: inline-block;
    }

    @media (max-width: 575.98px) {
        .student-dashboard .activity-calendar {
            grid-template-columns: repeat(7, minmax(0, 1fr));
            padding: .25rem;
        }

        .student-dashboard .activity-day {
            min-height: 2.35rem;
        }

        .student-dashboard .readiness-panel,
        .student-dashboard .readiness-simulator {
            padding: 1rem;
        }

        .student-dashboard .readiness-slider-list {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>
@endpush

@section('content')
<div class="student-dashboard py-4 py-md-5">
    <div class="container">
        
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <p class="text-primary text-uppercase fw-bold small mb-1">{{ $student->department?->name ?? 'Student' }}</p>
                <h1 class="h2 fw-bold mb-1 dashboard-heading text-slate-900 d-flex align-items-center gap-2">
                    <img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="" width="42" height="42" class="rounded-circle object-fit-cover">
                    <span>Your learning dashboard</span>
                </h1>
                <p class="text-secondary mb-0">Track your study progress, activity, and assessment results.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge college-badge px-3 py-2 rounded-pill small">
                    <i class="bi bi-mortarboard me-1 text-primary"></i>
                    {{ $student->course?->name ?? 'Student account' }}
                </span>
                <a href="{{ route('student.learn.index') }}" class="btn btn-outline-primary dashboard-action rounded-pill px-3">
                    <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i>
                    Browse modules
                </a>
            </div>
        </header>

        @php
            $totalSubtopics = (int) $modules->sum(fn ($module): int => $module->subtopics->count());
            $completionRate = $totalSubtopics > 0 ? round(($completedSubtopics / $totalSubtopics) * 100) : 0;
        @endphp
        <section class="curriculum-progress p-4 mb-4" aria-labelledby="curriculum-progress-title">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper" aria-hidden="true">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <div>
                        <h2 id="curriculum-progress-title" class="h6 fw-bold mb-1">Curriculum progress</h2>
                        <p class="text-secondary small mb-0">
                            {{ $completedSubtopics }} of {{ $totalSubtopics }} topics mastered
                        </p>
                    </div>
                </div>
                <span class="badge rounded-pill px-3 py-2" style="background: var(--dash-primary-soft); color: var(--dash-primary-hover);">
                    {{ $completionRate }}% complete
                </span>
            </div>
            <div class="progress" style="height: 0.65rem; border-radius: 999px;">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ $completionRate }}%;"
                     aria-label="Curriculum completion"
                     aria-valuenow="{{ $completionRate }}"
                     aria-valuemin="0"
                     aria-valuemax="100"></div>
            </div>
        </section>

        @include('partials.student-readiness', [
            'readinessAudience' => 'your',
            'readinessFocusUrl' => $weakestModuleRecord ? route('student.learn.show', $weakestModuleRecord) : null,
        ])

        @include('partials.student-activity-analytics', [
            'analyticsUrl' => route('student.activity.index'),
            'topicQuizzesUrl' => route('student.activity.topics.quizzes', ['subtopic' => '__SUBTOPIC__']),
        ])
        <section class="mb-4" aria-labelledby="general-test-performance-title" id="student-performance">
            @include('partials.general-test-performance-chart', [
                'performanceCardClass' => 'analytics-chart-card p-3 p-md-4',
                'performanceTitle' => 'Your general-test performance',
                'performanceAudience' => 'Your',
                'performanceRoute' => route('student.dashboard'),
                'performanceChartPrimaryColor' => 'var(--dash-primary)',
            ])
        </section>

        {{-- General Test History --}}
        <section class="dash-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                <div>
                    <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2 text-slate-800">
                        <i class="bi bi-clock-history text-primary"></i>
                        Recent General Tests
                    </h2>
                    <p class="text-secondary small mb-0 mt-1">Review your 10 most recent general test attempts. Subtopic quiz history is available in Study Activity.</p>
                </div>
                <a href="{{ route('student.results.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-pill px-3">
                    <span>View General Test History</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <hr class="my-3 border-slate-200" style="opacity: 0.6;">

            <div class="row g-3">
                @forelse($testHistory as $result)
                    @php
                        $scorePct = (float) ($result->display_percentage ?? 0);
                        $badgeStyle = 'background: #fef2f2; color: #dc2626;';
                        if ($scorePct >= 85) {
                            $badgeStyle = 'background: #ecfdf5; color: #059669;';
                        } elseif ($scorePct >= 75) {
                            $badgeStyle = 'background: var(--dash-primary-soft); color: var(--dash-primary-hover);';
                        }
                    @endphp
                    <div class="col-lg-6">
                        <a class="history-card" href="{{ route('student.results.show', $result) }}">
                            <div class="index-badge">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <h3 class="h6 fw-bold text-truncate mb-1 text-slate-800" title="{{ $result->test?->title ?? 'General Test' }}">
                                    {{ $result->test?->title ?? 'General Test' }}
                                </h3>
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="bi bi-calendar3" style="font-size: 0.75rem;"></i>
                                    <span>{{ $result->created_at->format('M d, Y · h:i A') }}</span>
                                </div>
                            </div>

                            <div class="text-end ps-2">
                                <div class="score-pill" style="{{ $badgeStyle }}">
                                    {{ $result->display_percentage }}%
                                </div>
                                <small class="d-block text-muted mt-1 fw-medium" style="font-size: 0.72rem;">
                                    {{ $result->score }} / {{ $result->display_total_questions }} pts
                                </small>
                            </div>

                            <div class="text-slate-300 ps-1">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12 py-5 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-light mb-3 text-secondary" style="width: 4rem; height: 4rem;">
                            <i class="bi bi-clipboard2-x fs-3"></i>
                        </div>
                        <h3 class="h6 fw-bold text-slate-700">No general test attempts recorded yet</h3>
                        <p class="text-muted small mx-auto mb-0" style="max-width: 380px;">
                            Take a general test to start tracking your assessment results here. Subtopic quiz attempts are shown in Study Activity.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>

    </div>
</div>
@endsection

@push('scripts')
@include('partials.student-readiness-script')
@include('partials.student-activity-script')
@endpush