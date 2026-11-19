@extends('layouts.app')

@push('styles')
<style>
    .student-performance {
        --performance-ink: #172033;
        --performance-muted: #64748b;
        --performance-line: #e2e8f0;
        color: var(--performance-ink);
    }

    html[data-bs-theme="dark"] .student-performance {
        --performance-ink: #e2e8f0;
        --performance-muted: #94a3b8;
        --performance-line: #334155;
    }

    .performance-hero {
        background:
            radial-gradient(circle at 88% 18%, rgb(255 255 255 / 13%) 0 4rem, transparent 4.1rem),
            linear-gradient(125deg, #39101b 0%, #70253a 58%, #9b4960 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.25rem;
        box-shadow: 0 1.15rem 2.4rem rgb(57 16 27 / 18%);
        color: #fff;
        padding: clamp(1.3rem, 3vw, 2rem);
    }

    .performance-card,
    .performance-section {
        background: #fff;
        border: 1px solid var(--performance-line);
        border-radius: 1rem;
        box-shadow: 0 .7rem 1.6rem rgb(15 23 42 / 6%);
    }

    .performance-card {
        height: 100%;
        padding: 1.15rem;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .performance-card:hover {
        box-shadow: 0 1rem 2rem rgb(15 23 42 / 10%);
        transform: translateY(-2px);
    }

    .performance-label {
        color: var(--performance-muted);
        font-size: .73rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .performance-value {
        color: var(--performance-ink);
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.2;
    }

    .performance-section {
        overflow: hidden;
    }

    .performance-section__header {
        border-bottom: 1px solid var(--performance-line);
        padding: 1rem 1.2rem;
    }

    .performance-section__body {
        padding: 1.2rem;
    }

    html[data-bs-theme="dark"] .performance-card,
    html[data-bs-theme="dark"] .performance-section,
    html[data-bs-theme="dark"] .performance-question {
        background: #1e293b;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .performance-table {
        --bs-table-bg: #1e293b;
        --bs-table-color: #e2e8f0;
        --bs-table-hover-bg: #29374a;
        --bs-table-hover-color: #f8fafc;
    }

    html[data-bs-theme="dark"] .performance-table thead th {
        background: #172033;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .performance-chart-track,
    html[data-bs-theme="dark"] .performance-answer {
        background: #172033;
    }

    html[data-bs-theme="dark"] .performance-question__text {
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .performance-audit-tabs .nav-link {
        color: #cbd5e1;
    }

    .performance-chart-row {
        display: grid;
        gap: .75rem;
        grid-template-columns: minmax(7rem, 13rem) minmax(8rem, 1fr) 4.2rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .performance-chart-track {
        background: #f1f5f9;
        border-radius: 999px;
        height: .85rem;
        overflow: visible;
        position: relative;
    }

    .performance-chart-bar {
        background: linear-gradient(90deg, #9b4960, #c06a7e);
        border-radius: inherit;
        height: 100%;
        min-width: .25rem;
        position: relative;
        z-index: 1;
    }

    .performance-chart-threshold {
        border-left: 2px dashed #e11d48;
        height: 1.35rem;
        left: 75%;
        position: absolute;
        top: -.25rem;
        z-index: 2;
    }

    .performance-question {
        border: 1px solid var(--performance-line);
        border-radius: .9rem;
        padding: 1rem;
        transition: border-color .16s ease, box-shadow .16s ease;
    }

    .performance-question:hover {
        border-color: #cbd5e1;
        box-shadow: 0 .5rem 1rem rgb(15 23 42 / 5%);
    }

    .performance-question__text {
        color: #1e293b;
        font-weight: 700;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .performance-answer {
        background: #f8fafc;
        border-radius: .65rem;
        padding: .8rem;
        overflow-wrap: anywhere;
    }

    .performance-audit-tabs .nav-link {
        border-radius: 999px;
        color: #475569;
        font-weight: 700;
        padding: .5rem .85rem;
        transition: background-color .16s ease, color .16s ease, transform .16s ease;
    }

    .performance-audit-tabs .nav-link:hover {
        background: #fff1f2;
        color: #9f1239;
        transform: translateY(-1px);
    }

    .performance-audit-tabs .nav-link.active {
        background: #881337;
        color: #fff;
    }

    .performance-table {
        min-width: 650px;
    }

    .performance-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .performance-hero { border-radius: .9rem; }
        .performance-chart-row {
            gap: .5rem;
            grid-template-columns: minmax(5.5rem, 1fr) minmax(5rem, 1.5fr) 3.5rem;
        }
        .performance-section__body { padding: .9rem; }
    }
</style>
@endpush

@section('content')
<main class="container-fluid px-3 px-xl-4 student-performance">
    <div class="mb-3">
        <a class="btn btn-outline-secondary rounded-pill px-3" href="{{ $backUrl }}">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Results
        </a>
    </div>

    <header class="performance-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-4">
            <div class="min-w-0">
                <div class="text-white-50 small fw-bold text-uppercase mb-2" style="letter-spacing:.12em">Student exam performance</div>
                <h1 class="h2 fw-bold mb-1">{{ $student->name }}</h1>
                <div class="text-white-50">{{ $student->email }}</div>
                <h2 class="h5 fw-semibold mt-3 mb-2">{{ $exam->title }}</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 small">
                    <span>Date taken: {{ $result->created_at?->format('M j, Y · g:i A') ?? 'Date unavailable' }}</span>
                    <span class="text-white-50" aria-hidden="true">·</span>
                    @if ($result->submission_type === 'time_expired')
                        <span class="badge rounded-pill text-bg-warning">Auto-Submitted: Time Expired</span>
                    @else
                        <span class="badge rounded-pill bg-white text-dark">Submitted Manually</span>
                    @endif
                </div>
            </div>
            <div class="text-start text-lg-end">
                <div class="text-white-50 small fw-bold text-uppercase mb-1" style="letter-spacing:.1em">Overall score</div>
                <div class="display-4 fw-bold lh-1">{{ $result->score }} <span class="fs-3 text-white-50">/ {{ $totalQuestions }}</span></div>
                <div class="d-flex align-items-center justify-content-lg-end gap-2 mt-2">
                    <span class="fs-5 fw-bold">{{ number_format($scorePercentage, 1) }}%</span>
                    @if ($passed)
                        <span class="badge rounded-pill bg-success">Passed</span>
                    @else
                        <span class="badge rounded-pill bg-danger">Failed</span>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <section class="row g-3 mb-4" aria-label="Time pressure and pacing">
        <div class="col-12 col-md-4">
            <article class="performance-card">
                <div class="performance-label mb-2">Time used</div>
                <div class="performance-value">{{ $timeUsedLabel }} <span class="text-muted fs-6 fw-semibold">/ {{ $timeLimitLabel }}</span></div>
                @php
                    $timeProgress = $timeUsedSeconds !== null && $timeLimitSeconds
                        ? min(100, (int) round(($timeUsedSeconds / $timeLimitSeconds) * 100))
                        : 0;
                @endphp
                <div class="progress mt-3" role="progressbar" aria-label="Time used" aria-valuenow="{{ $timeProgress }}" aria-valuemin="0" aria-valuemax="100" style="height:.55rem">
                    <div class="progress-bar bg-danger" style="width:{{ $timeProgress }}%"></div>
                </div>
                <div class="small text-muted mt-2">{{ $timeUsedSeconds === null ? 'Timing data is unavailable for this attempt.' : $timeProgress.'% of the exam limit used' }}</div>
            </article>
        </div>
        <div class="col-12 col-md-4">
            <article class="performance-card">
                <div class="performance-label mb-2">Average pace</div>
                <div class="performance-value">{{ $averagePaceSeconds === null ? '—' : $averagePaceSeconds.' sec / item' }}</div>
                <div class="small text-muted mt-3">
                    Target pace: {{ $targetPaceSeconds === null ? '—' : $targetPaceSeconds.' sec / item' }}
                </div>
            </article>
        </div>
        <div class="col-12 col-md-4">
            <article class="performance-card">
                <div class="performance-label mb-2">Unanswered items</div>
                <div class="performance-value">{{ $unansweredCount }}</div>
                <div class="small text-muted mt-3">Items left blank in this attempt</div>
            </article>
        </div>
    </section>

    <section class="mb-4" aria-labelledby="difficulty-heading">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <div class="performance-label">Randomized exam snapshot</div>
                <h2 class="h4 fw-bold mb-0" id="difficulty-heading">Question difficulty breakdown</h2>
            </div>
            <span class="small text-muted">{{ $totalItems }} items in this attempt</span>
        </div>
        <div class="row g-3">
            @foreach ($difficultyStats as $difficulty)
                <div class="col-12 col-md-4">
                    <article class="performance-card">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="performance-label mb-2">{{ $difficulty['label'] }}</div>
                                <div class="performance-value">{{ $difficulty['correct'] }} / {{ $difficulty['total'] }} Correct</div>
                            </div>
                            <span class="badge rounded-pill {{ $difficulty['accuracy'] !== null && $difficulty['accuracy'] >= 75 ? 'text-bg-success' : 'text-bg-light border' }}">
                                {{ $difficulty['accuracy'] === null ? 'No items' : number_format($difficulty['accuracy'], 1).'%' }}
                            </span>
                        </div>
                        @if ($difficulty['total'] > 0)
                            <div class="progress mt-3" role="progressbar" aria-label="{{ $difficulty['label'] }} accuracy" aria-valuenow="{{ $difficulty['accuracy'] }}" aria-valuemin="0" aria-valuemax="100" style="height:.55rem">
                                <div class="progress-bar {{ $difficulty['accuracy'] >= 75 ? 'bg-success' : 'bg-warning' }}" style="width:{{ $difficulty['accuracy'] }}%"></div>
                            </div>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    </section>

    <section class="performance-section mb-4" aria-labelledby="topic-heading">
        <div class="performance-section__header">
            <div class="performance-label">Topic Boxes</div>
            <h2 class="h4 fw-bold mb-0" id="topic-heading">Topic-by-topic performance</h2>
        </div>
        <div class="performance-section__body">
            @if ($topicStats->isEmpty())
                <p class="text-muted mb-0">No topic information is available in this exam snapshot.</p>
            @else
                <div class="mb-4" role="img" aria-label="Horizontal bar chart of topic accuracy. The dashed line marks the 75 percent reference threshold.">
                    <div class="d-flex justify-content-end small text-muted mb-2">
                        <span class="d-inline-flex align-items-center gap-2"><span class="border-start border-2 border-danger border-dashed" style="height:1rem"></span>75% threshold</span>
                    </div>
                    @foreach ($topicStats as $topic)
                        <div class="performance-chart-row">
                            <div class="small fw-semibold text-truncate" title="{{ $topic['topic'] }}">{{ $topic['topic'] }}</div>
                            <div class="performance-chart-track">
                                <div class="performance-chart-bar" style="width:{{ $topic['accuracy'] }}%"></div>
                                <div class="performance-chart-threshold" aria-hidden="true"></div>
                            </div>
                            <div class="small fw-bold text-end">{{ number_format($topic['accuracy'], 1) }}%</div>
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle performance-table mb-0">
                        <thead>
                            <tr>
                                <th>Topic name</th>
                                <th>Items drawn</th>
                                <th>Correct</th>
                                <th>Accuracy (%)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topicStats as $topic)
                                <tr>
                                    <td class="fw-semibold">{{ $topic['topic'] }}</td>
                                    <td>{{ $topic['total'] }}</td>
                                    <td>{{ $topic['correct'] }}</td>
                                    <td>{{ number_format($topic['accuracy'], 1) }}%</td>
                                    <td>
                                        <span class="badge rounded-pill {{ $topic['status'] === 'Strong' ? 'text-bg-success' : ($topic['status'] === 'Needs Review' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                            {{ $topic['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="alert alert-warning border rounded-4 shadow-sm mb-4" aria-labelledby="diagnostic-heading">
        <div class="d-flex gap-3">
            <span class="fs-4" aria-hidden="true"><i class="bi bi-lightbulb"></i></span>
            <div>
                <h2 class="h6 fw-bold mb-2" id="diagnostic-heading">Performance diagnostic</h2>
                <ul class="mb-0 ps-3">
                    <li>
                        Lowest-scoring topic:
                        <strong>{{ $weakestTopic ? $weakestTopic['topic'].' ('.number_format($weakestTopic['accuracy'], 1).'%)' : 'Not enough topic data' }}</strong>
                    </li>
                    <li>
                        Weakest difficulty:
                        <strong>{{ $weakestDifficulty ? $weakestDifficulty['label'].' ('.number_format($weakestDifficulty['accuracy'], 1).'%)' : 'Not enough difficulty data' }}</strong>
                    </li>
                    <li>
                        @if ($unansweredCount > 0)
                            {{ $unansweredCount }} unanswered {{ \Illuminate\Support\Str::plural('item', $unansweredCount) }} may have affected the score.
                        @else
                            No unanswered items affected the score.
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="performance-section" aria-labelledby="audit-heading">
        <div class="performance-section__header">
            <div class="performance-label">Attempt review</div>
            <h2 class="h4 fw-bold mb-0" id="audit-heading">Randomized question audit log</h2>
        </div>
        <div class="performance-section__body">
            @php
                $auditRouteParameters = fn (string $filter): array => array_merge(
                    $detailRouteParameters,
                    array_filter([
                        'item_filter' => $filter,
                        'topic' => $selectedTopic !== '' ? $selectedTopic : null,
                        'difficulty' => $selectedDifficulty !== '' ? $selectedDifficulty : null,
                    ], fn ($value) => $value !== null && $value !== ''),
                );
            @endphp
            <ul class="nav nav-pills performance-audit-tabs gap-2 mb-3" aria-label="Filter questions">
                <li class="nav-item">
                    <a class="nav-link {{ $itemFilter === 'all' ? 'active' : '' }}" href="{{ route($studentRoute, $auditRouteParameters('all')) }}">All Items <span class="opacity-75">({{ $items->count() }})</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $itemFilter === 'incorrect' ? 'active' : '' }}" href="{{ route($studentRoute, $auditRouteParameters('incorrect')) }}">Incorrect Only <span class="opacity-75">({{ $items->filter(fn ($item) => ! $item['is_correct'] && ! $item['is_unanswered'])->count() }})</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $itemFilter === 'unanswered' ? 'active' : '' }}" href="{{ route($studentRoute, $auditRouteParameters('unanswered')) }}">Unanswered <span class="opacity-75">({{ $unansweredCount }})</span></a>
                </li>
            </ul>

            <form method="GET" action="{{ route($studentRoute, ['exam' => $exam->id, 'student' => $student->id]) }}" class="row g-2 align-items-end mb-4">
                <input type="hidden" name="item_filter" value="{{ $itemFilter }}">
                @foreach (['college_id', 'course_id', 'student_search', 'status'] as $filterKey)
                    @if (isset($detailRouteParameters[$filterKey]))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $detailRouteParameters[$filterKey] }}">
                    @endif
                @endforeach
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold" for="audit-topic">Topic</label>
                    <select class="form-select" id="audit-topic" name="topic">
                        <option value="">All topics</option>
                        @foreach ($items->pluck('topic')->unique()->sort() as $topic)
                            <option value="{{ $topic }}" @selected($selectedTopic === $topic)>{{ $topic }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="audit-difficulty">Difficulty</label>
                    <select class="form-select" id="audit-difficulty" name="difficulty">
                        <option value="">All difficulties</option>
                        @foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'hard' => 'Hard'] as $difficulty => $label)
                            <option value="{{ $difficulty }}" @selected($selectedDifficulty === $difficulty)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-outline-danger rounded-pill px-4" type="submit">Apply filters</button>
                </div>
            </form>

            @if ($filteredItems->isEmpty())
                <div class="rounded-4 bg-light text-center p-4 text-muted">
                    No questions match the selected audit filters.
                </div>
            @else
                <div class="d-grid gap-3">
                    @foreach ($filteredItems as $index => $item)
                        <article class="performance-question">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill text-bg-primary">{{ $item['topic'] }}</span>
                                    @if ($item['difficulty'] !== '')
                                        <span class="badge rounded-pill text-bg-light border text-dark">{{ ucfirst($item['difficulty']) }}</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-light border text-dark">Difficulty unavailable</span>
                                    @endif
                                </div>
                                @if ($item['is_unanswered'])
                                    <span class="badge rounded-pill text-bg-secondary">Unanswered</span>
                                @elseif ($item['is_correct'])
                                    <span class="badge rounded-pill text-bg-success">Correct</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger">Incorrect</span>
                                @endif
                            </div>
                            <h3 class="h6 performance-question__text mb-3">Item {{ $item['item_number'] }}. {{ $item['question'] }}</h3>
                            <div class="row g-2">
                                <div class="col-12 col-lg-6">
                                    <div class="performance-answer h-100">
                                        <span class="d-block small text-muted fw-bold mb-1">Student's selected answer</span>
                                        <span>{{ $item['selected_answer'] !== '' ? $item['selected_answer'] : 'No Answer' }}</span>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="performance-answer h-100">
                                        <span class="d-block small text-muted fw-bold mb-1">Correct answer</span>
                                        <span>{{ $item['correct_answer'] !== '' ? $item['correct_answer'] : 'Unavailable' }}</span>
                                    </div>
                                </div>
                                @if ($item['explanation'] !== '')
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 mb-0">
                                            <span class="d-block small fw-bold mb-1">Explanation / rationale</span>
                                            {{ $item['explanation'] }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</main>
@endsection
