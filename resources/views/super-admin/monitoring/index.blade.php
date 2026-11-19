@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="badge text-bg-primary mb-2">Institution oversight</span>
            <h1 class="h3 mb-1">Faculty &amp; Program Monitoring</h1>
            <p class="text-muted mb-0">
                Review Faculty accounts, Question Banks, and recent examination submissions
                @if($selectedCourse) for {{ $selectedCourse->name }} @else across academic programs @endif.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('super-admin.question-bank.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-journal-text me-1" aria-hidden="true"></i> Question Bank
            </a>
            <a href="{{ route('super-admin.item-analysis.index') }}" class="btn btn-primary">
                <i class="bi bi-bar-chart me-1" aria-hidden="true"></i> Item Analysis
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('super-admin.monitoring') }}" class="card card-body shadow-sm mb-4">
        <div class="row align-items-end g-3">
            <div class="col-md-6 col-lg-4">
                <label for="monitoring-program-filter" class="form-label fw-semibold">Academic program</label>
                <select id="monitoring-program-filter" name="course_id" class="form-select">
                    <option value="">All programs</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>
                            {{ $course->name }} · {{ $course->department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Apply filter</button>
                @if($selectedCourseId)
                    <a href="{{ route('super-admin.monitoring') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <section class="row row-cols-1 row-cols-sm-3 g-3 mb-4" aria-label="Monitoring summary">
        <div class="col">
            <article class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="rounded-circle bg-primary-subtle text-primary p-3"><i class="bi bi-person-badge fs-5" aria-hidden="true"></i></span>
                    <div><p class="small text-muted mb-1">Faculty accounts</p><p class="h4 mb-0">{{ $faculties->count() }}</p></div>
                </div>
            </article>
        </div>
        <div class="col">
            <article class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="rounded-circle bg-success-subtle text-success p-3"><i class="bi bi-diagram-3 fs-5" aria-hidden="true"></i></span>
                    <div><p class="small text-muted mb-1">Faculty with bank questions</p><p class="h4 mb-0">{{ $faculties->where('question_bank_items_count', '>', 0)->count() }}</p></div>
                </div>
            </article>
        </div>
        <div class="col">
            <article class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="rounded-circle bg-info-subtle text-info p-3"><i class="bi bi-file-earmark-check fs-5" aria-hidden="true"></i></span>
                    <div><p class="small text-muted mb-1">Recent exam submissions</p><p class="h4 mb-0">{{ $recentAssessments->count() }}</p></div>
                </div>
            </article>
        </div>
    </section>

    <section class="card shadow-sm mb-4" aria-labelledby="faculty-monitoring-heading">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div><h2 class="h5 mb-1" id="faculty-monitoring-heading">{{ $selectedCourse ? 'Faculty for '.$selectedCourse->name : 'All Faculty' }}</h2><p class="small text-muted mb-0">Academic program association, individual Question Bank size, and account status.</p></div>
            <span class="badge text-bg-light">{{ $faculties->count() }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th scope="col">Faculty member</th><th scope="col">Program</th><th scope="col">Own Question Bank</th><th scope="col">Account</th><th scope="col">Last active</th></tr>
                </thead>
                <tbody>
                    @forelse ($faculties as $faculty)
                        <tr>
                            <td><div class="fw-semibold">{{ $faculty->name }}</div><div class="small text-muted">{{ $faculty->email }}</div></td>
                            <td>{{ $selectedCourse?->name ?? $faculty->course?->name ?? 'Varies by Question Bank' }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.question-bank.index', ['author_id' => $faculty->id]) }}">
                                    <i class="bi bi-journal-text me-1" aria-hidden="true"></i>
                                    {{ $faculty->question_bank_items_count }} question{{ $faculty->question_bank_items_count === 1 ? '' : 's' }}
                                </a>
                            </td>
                            <td>
                                @if ($faculty->email_verified_at)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-warning">Invitation pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($faculty->last_active_at)
                                    <time datetime="{{ $faculty->last_active_at->toIso8601String() }}" title="{{ $faculty->last_active_at->format('M j, Y g:i A') }}">{{ $faculty->last_active_at->diffForHumans() }}</time>
                                @else
                                    <span class="text-muted">No activity recorded</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No Faculty accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card shadow-sm" aria-labelledby="recent-assessments-heading">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0" id="recent-assessments-heading">Recent scheduled-examination submissions</h2>
            <a href="{{ route('super-admin.item-analysis.index') }}" class="btn btn-sm btn-outline-primary">Cross-program Item Analysis</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Student</th><th scope="col">Program</th><th scope="col">Examination</th><th scope="col">Score</th><th scope="col">Submitted</th></tr></thead>
                <tbody>
                    @forelse ($recentAssessments as $result)
                        <tr>
                            <td>{{ $result->user?->name ?? 'Unknown student' }}</td>
                            <td>{{ $result->test?->course?->name ?? $result->user?->course?->name ?? '—' }}</td>
                            <td>{{ $result->test?->title ?? 'Deleted examination' }}</td>
                            <td>
                                @if ($result->total_questions > 0)
                                    {{ $result->score }} / {{ $result->total_questions }}
                                    <span class="small text-muted">({{ (int) round(($result->score / $result->total_questions) * 100) }}%)</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $result->created_at->format('M j, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No scheduled-examination submissions have been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
