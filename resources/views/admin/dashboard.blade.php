@extends('layouts.app')

@push('styles')
<style>
    /* ==========================================================
       LUXURY BURGUNDY & GOLD ADMIN DASHBOARD THEME
    ========================================================== */
    :root {
        --department-color: #4a0e17;
        --lemu-burgundy: #4a0e17;
        --lemu-burgundy-dark: #2c070e;
        --lemu-gold: #eab308;
        --lemu-gold-light: #fef08a;
        --card-radius: 1.5rem;
        --transition-smooth: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes luxuryFadeUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes cardFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }

    .animate-entrance {
        animation: luxuryFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .admin-dashboard {
        background: #f8fafc;
        min-height: calc(100vh - 72px);
    }

    /* 3D Perspective & Floating Dashboard Cards */
    .dashboard-perspective-scene {
        perspective: 1400px;
    }

    .dashboard-card-3d {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.25) !important;
        border-radius: var(--card-radius);
        box-shadow: 0 15px 35px -10px rgba(74, 14, 23, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03) !important;
        transform-style: preserve-3d;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.4s ease, border-color 0.4s ease;
        position: relative;
        overflow: hidden;
        animation: cardFloat 5s ease-in-out infinite;
    }

    .row.g-4 > div:nth-child(1) .dashboard-card-3d { animation-delay: 0s; }
    .row.g-4 > div:nth-child(2) .dashboard-card-3d { animation-delay: 1.2s; }
    .row.g-4 > div:nth-child(3) .dashboard-card-3d { animation-delay: 2.4s; }
    .row.g-4 > div:nth-child(4) .dashboard-card-3d { animation-delay: 3.6s; }

    .dashboard-card-3d:hover {
        transform: translateY(-8px) rotateX(3deg) scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(74, 14, 23, 0.18), 0 0 20px rgba(234, 179, 8, 0.2) !important;
        border-color: rgba(234, 179, 8, 0.6) !important;
        animation-play-state: paused;
    }

    .section-title {
        align-items: center;
        display: flex;
        font-size: 1.15rem;
        font-weight: 800;
        gap: 0.75rem;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .section-title i {
        color: var(--department-color);
        background: rgba(234, 179, 8, 0.15);
        padding: 0.55rem;
        border-radius: 0.85rem;
        font-size: 1.1rem;
        border: 1px solid rgba(234, 179, 8, 0.35);
    }

    .metric-value {
        color: var(--department-color);
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.03em;
    }

    .performance-chart {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.25);
        border-radius: 1.25rem;
        overflow-x: auto;
        padding: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(74, 14, 23, 0.04);
    }

    .performance-chart svg {
        display: block;
        min-width: 680px;
        width: 100%;
    }

    .chart-label {
        fill: #64748b;
        font-size: 12px;
        font-weight: 500;
    }

    .chart-axis-title {
        fill: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .chart-grid {
        stroke: #f1f5f9;
        stroke-width: 1;
        stroke-dasharray: 4 4;
    }

    .chart-axis {
        stroke: #cbd5e1;
        stroke-width: 2;
    }

    .chart-line {
        fill: none;
        stroke: var(--department-color);
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 3.5;
    }

    .chart-point {
        fill: #fff;
        stroke: var(--department-color);
        stroke-width: 3;
    }

    .chart-legend {
        align-items: center;
        color: #64748b;
        display: flex;
        font-size: 0.8rem;
        font-weight: 500;
        gap: 0.5rem;
    }

    .chart-legend-swatch {
        background: var(--department-color);
        border-radius: 999px;
        height: 0.35rem;
        width: 1.5rem;
    }

    /* Custom Form & Search Focus States */
    .admin-dashboard .form-control {
        border-radius: 0.85rem;
        border-color: #cbd5e1;
        padding: 0.7rem 1rem;
    }

    .admin-dashboard .form-control:focus {
        border-color: var(--department-color);
        box-shadow: 0 0 0 3px rgba(74, 14, 23, 0.15);
    }

    .analytics-period-picker {
        z-index: 20;
    }

    .analytics-period-trigger {
        min-width: 13rem;
    }

    .analytics-period-menu {
        min-width: min(22rem, calc(100vw - 2rem));
    }

    .badge-burgundy-gold {
        background: linear-gradient(135deg, #4a0e17 0%, #2c070e 100%);
        color: #fef08a;
        border: 1px solid rgba(234, 179, 8, 0.4);
    }

    .tracking-widest-caps {
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 800;
    }

    /* Table row hover elevation */
    .table-hover tbody tr {
        transition: all 0.2s ease;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(234, 179, 8, 0.04) !important;
        transform: scale(1.002);
    }

    .analytics-filter {
        max-width: 14rem;
    }

    .analytics-support-item {
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        transition: border-color .2s ease, transform .2s ease;
    }

    .analytics-support-item:hover {
        border-color: rgba(74, 14, 23, .35);
        transform: translateY(-2px);
    }

    .analytics-target-line {
        stroke: #ca8a04;
        stroke-dasharray: 6 5;
        stroke-width: 2;
    }

    @media (prefers-reduced-motion: reduce) {
        .animate-entrance,
        .dashboard-card-3d {
            animation: none;
            transition: none;
        }
    }
</style>
@endpush

@section('content')
<div class="admin-dashboard py-3 animate-entrance">
    <div class="container">
        
        <!-- Page Header (Flushed UP) -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-extrabold text-slate-900 mb-1 tracking-tight">Faculty Dashboard</h1>
                <p class="text-secondary small mb-0 fw-medium">{{ $programName }} · Track participation, general-test outcomes, and students who may need support.</p>
            </div>
            <div class="dropdown analytics-period-picker">
                <button
                    class="btn btn-white border rounded-3 shadow-sm d-flex align-items-center gap-3 px-3 py-2 text-start analytics-period-trigger"
                    id="admin-analytics-period-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Choose reporting period"
                >
                    <i class="bi bi-calendar3 text-primary fs-5" aria-hidden="true"></i>
                    <span class="d-flex flex-column">
                        <span class="small text-secondary">Reporting period</span>
                        <span class="small fw-semibold text-dark">{{ $periodLabel }}</span>
                    </span>
                    <i class="bi bi-chevron-down ms-auto small text-secondary" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end analytics-period-menu p-3" aria-labelledby="admin-analytics-period-toggle">
                    <form method="GET" action="{{ route('admin.dashboard') }}" id="admin-analytics-filter">
                        <label for="admin-analytics-period" class="form-label small fw-semibold text-secondary">Reporting period</label>
                        <select id="admin-analytics-period" name="period" class="form-select form-select-sm analytics-filter mb-3">
                            @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $days => $label)
                                <option value="{{ $days }}" @selected((string) old('period', $period) === (string) $days)>{{ $label }}</option>
                            @endforeach
                            <option value="custom" @selected((string) old('period', $period) === 'custom')>Custom range</option>
                        </select>
                        <div id="admin-custom-period-fields" class="{{ (string) old('period', $period) === 'custom' ? 'd-block' : 'd-none' }}">
                            <div class="d-flex gap-2 mb-2">
                                <div class="flex-fill">
                                    <label for="admin-period-start" class="form-label small text-secondary">Start date</label>
                                    <input id="admin-period-start" type="date" name="start_date" class="form-control form-control-sm analytics-filter"
                                           value="{{ old('start_date', $periodStartDate) }}" max="{{ now()->toDateString() }}"
                                           @required((string) old('period', $period) === 'custom')
                                           @disabled((string) old('period', $period) !== 'custom')>
                                </div>
                                <div class="flex-fill">
                                    <label for="admin-period-end" class="form-label small text-secondary">End date</label>
                                    <input id="admin-period-end" type="date" name="end_date" class="form-control form-control-sm analytics-filter"
                                           value="{{ old('end_date', $periodEndDate) }}" max="{{ now()->toDateString() }}"
                                           @required((string) old('period', $period) === 'custom')
                                           @disabled((string) old('period', $period) !== 'custom')>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <small class="text-secondary">Select up to 366 days.</small>
                                <button id="admin-custom-period-apply" type="submit" class="btn btn-sm btn-primary">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-xxl-5 g-4 mb-4 dashboard-perspective-scene">
            <!-- 1. Total Students -->
            <div class="col">
                <a class="dashboard-card-3d p-4 h-100 d-flex flex-column justify-content-between text-decoration-none" href="{{ route('admin.students').'#approved-panel' }}" aria-label="Manage approved students">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary shadow-2xs">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                        </div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider mb-1">Total Students</div>
                        <div class="metric-value">{{ $departmentStudents }}</div>
                    </div>
                    <div class="text-secondary small mt-3 d-flex align-items-center gap-2 fw-medium">
                        <i class="bi bi-info-circle text-primary"></i> Enrolled in your program
                    </div>
                </a>
            </div>

            <!-- 2. Active Students -->
            <div class="col">
                <div class="dashboard-card-3d p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="p-3 rounded-4 bg-success bg-opacity-10 text-success shadow-2xs">
                                <i class="bi bi-check-circle-fill fs-4"></i>
                            </div>
                        </div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider mb-1">Active Students</div>
                        <div class="metric-value text-success">{{ $activeStudentsCount }}</div>
                    </div>
                    <div class="text-secondary small mt-3 d-flex align-items-center gap-2 fw-medium">
                        <i class="bi bi-activity text-success"></i> Study or assessment activity · {{ $periodLabel }}
                    </div>
                </div>
            </div>

            <!-- 3. Inactive Students -->
            <div class="col">
                <div class="dashboard-card-3d p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="p-3 rounded-4 bg-warning bg-opacity-10 text-warning shadow-2xs" style="background-color: rgba(234, 179, 8, 0.15) !important;">
                                <i class="bi bi-person-dash-fill fs-4 text-dark"></i>
                            </div>
                        </div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider mb-1">Inactive Students</div>
                        <div class="metric-value text-dark">{{ $inactiveStudentsCount }}</div>
                    </div>
                    <div class="text-secondary small mt-3 d-flex align-items-center gap-2 fw-medium">
                        <i class="bi bi-dash-circle text-warning"></i> No activity · {{ $periodLabel }}
                    </div>
                </div>
            </div>

            <div class="col">
                <section class="dashboard-card-3d p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="p-3 rounded-4 shadow-2xs" style="background-color: rgba(234, 179, 8, 0.2); color: #ca8a04;">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                            </div>
                        </div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider mb-1">Average General-Test Score</div>
                        <div class="metric-value" style="color: #ca8a04;">{{ $averageCollegeScore === null ? '—' : $averageCollegeScore.'%' }}</div>
                    </div>
                    <div class="text-secondary small mt-3 d-flex align-items-center gap-2 fw-medium">
                        <i class="bi bi-people text-warning"></i> Latest result · {{ $generalTestParticipants }} students
                    </div>
                </section>
            </div>

            <div class="col">
                <section class="dashboard-card-3d p-4 h-100 d-flex flex-column justify-content-between" aria-label="Program general-test pass rate">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="p-3 rounded-4 bg-success bg-opacity-10 text-success shadow-2xs">
                                <i class="bi bi-bullseye fs-4"></i>
                            </div>
                        </div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider mb-1">Pass Rate</div>
                        <div class="metric-value text-success">{{ $collegePassRate === null ? '—' : $collegePassRate.'%' }}</div>
                    </div>
                    <div class="text-secondary small mt-3">{{ $generalTestParticipants }} participating students · latest score ≥ 75%</div>
                </section>
            </div>
        </div>

        @if($supportStudents->isNotEmpty())
            <section class="dashboard-card-3d p-4 mb-4" aria-labelledby="student-support-title">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 id="student-support-title" class="section-title mb-1"><i class="bi bi-heart-pulse"></i>Students to check in with</h2>
                        <p class="small text-secondary mb-0">Suggested follow-ups based on the selected period. Send a private message directly from this list.</p>
                    </div>
                    <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">{{ $supportStudents->count() }} suggested</span>
                </div>
                <div class="row g-3">
                    @foreach($supportStudents as $supportItem)
                        <div class="col-md-6 col-xl-4">
                            <article class="analytics-support-item p-3 h-100 d-flex align-items-center justify-content-between gap-3">
                                <div class="min-w-0">
                                    <strong class="d-block text-dark text-truncate">{{ $supportItem['student']->name }}</strong>
                                    <span class="small text-secondary d-block">{{ $supportItem['reason'] }}</span>
                                    @if($supportItem['score'] !== null)
                                        <span class="small fw-bold text-danger">Latest score: {{ $supportItem['score'] }}%</span>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark rounded-pill flex-shrink-0"
                                    data-bs-toggle="modal"
                                    data-bs-target="#messageStudentModal"
                                    data-message-action="{{ route('admin.students.notifications.store', $supportItem['student']) }}"
                                    data-student-name="{{ $supportItem['student']->name }}"
                                    aria-label="Message {{ $supportItem['student']->name }}"
                                ><i class="bi bi-envelope" aria-hidden="true"></i><span class="visually-hidden">Message</span></button>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if($studentSearch !== '')
            <section class="dashboard-card-3d p-4 mb-4" aria-label="Student search results">
                <div class="list-group list-group-flush">
                    @forelse($matchingStudents as $student)
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-2.5 rounded-3 mb-1" href="{{ route('admin.students.show', $student) }}">
                            <div>
                                <strong class="text-dark">{{ $student->name }}</strong>
                                <small class="d-block text-muted">{{ $student->email }}</small>
                            </div>
                            <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill fw-semibold">
                                View profile <i class="bi bi-arrow-right ms-1"></i>
                            </span>
                        </a>
                    @empty
                        <p class="text-muted small mb-0">No students match that search.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <!-- Program General Test Performance Chart -->
        @php
            $chartWidth = max(1140, $periodDays * 24);
            $chartLeft = 58;
            $chartRight = $chartWidth - 24;
            $chartTop = 24;
            $chartBottom = 216;
            $chartStep = ($chartRight - $chartLeft) / max(1, $periodDays - 1);
            $chartSegments = [];
            $currentSegment = [];
            foreach ($departmentPerformance as $dayIndex => $day) {
                if ($day['score'] === null) {
                    if ($currentSegment !== []) {
                        $chartSegments[] = $currentSegment;
                        $currentSegment = [];
                    }

                    continue;
                }
                $x = $chartLeft + ($dayIndex * $chartStep);
                $y = $chartBottom - (($day['score'] / 100) * ($chartBottom - $chartTop));
                $currentSegment[] = round($x, 2).','.round($y, 2);
            }
            if ($currentSegment !== []) {
                $chartSegments[] = $currentSegment;
            }
        @endphp
        <section class="dashboard-card-3d p-4 mb-4" id="college-performance">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <h2 class="section-title mb-1"><i class="bi bi-graph-up"></i>Program General-Test Trend</h2>
                    <p class="small text-secondary mb-0">Daily average of each participating student's latest score that day. The timeline covers every calendar day; days without test results have no score point. The dashed line marks the 75% target.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium">{{ $periodDays }} calendar days</span>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium">{{ $periodDateRange }}</span>
                </div>
            </div>
            <div class="performance-chart">
                <svg viewBox="0 0 {{ $chartWidth }} 270" style="width: {{ $chartWidth }}px; max-width: none;" role="img" aria-label="Daily college average general-test scores across {{ $periodDays }} calendar days, {{ $periodDateRange }}">
                    @foreach([100, 75, 50, 25, 0] as $scale)
                        @php $y = $chartBottom - (($scale / 100) * ($chartBottom - $chartTop)); @endphp
                        <line class="{{ $scale === 75 ? 'analytics-target-line' : 'chart-grid' }}" x1="{{ $chartLeft }}" x2="{{ $chartRight }}" y1="{{ $y }}" y2="{{ $y }}"></line>
                        <text class="chart-label" text-anchor="end" x="{{ $chartLeft - 12 }}" y="{{ $y + 4 }}">{{ $scale }}%</text>
                    @endforeach
                    @foreach($chartSegments as $segment)
                        @if(count($segment) > 1)
                            <polyline class="chart-line" points="{{ implode(' ', $segment) }}"></polyline>
                        @endif
                    @endforeach
                    @foreach($departmentPerformance as $day)
                        @php
                            $x = $chartLeft + ($loop->index * $chartStep);
                            $y = $day['score'] === null ? null : $chartBottom - (($day['score'] / 100) * ($chartBottom - $chartTop));
                        @endphp
                        @if($y !== null)
                            <circle class="chart-point" cx="{{ $x }}" cy="{{ $y }}" r="5">
                                <title>{{ $day['date'] }}: {{ $day['score'] }}% average, {{ $day['participants'] }} students</title>
                            </circle>
                        @endif
                        @if($loop->first || $loop->last || $loop->index % max(1, (int) ceil($periodDays / 12)) === 0)
                            <text class="chart-label" text-anchor="middle" x="{{ $x }}" y="244">{{ $day['label'] }}</text>
                        @endif
                    @endforeach
                </svg>
                <div class="chart-legend mt-3 pt-2 border-top">
                    <span class="chart-legend-swatch"></span>Daily latest-score average
                    <span class="ms-3 border-top border-2 border-warning border-dashed"></span>75% target
                </div>
            </div>
        </section>

    </div>
</div>
<div class="modal fade" id="messageStudentModal" tabindex="-1" aria-labelledby="messageStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="messageStudentForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="messageStudentModalLabel">Send a message</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small" id="messageStudentRecipient"></p>
                    <label for="messageStudentText" class="form-label">Message</label>
                    <textarea id="messageStudentText" name="message" class="form-control" rows="5" maxlength="2000" required></textarea>
                    @error('message')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    <p class="form-text">Your name will be shown as the sender.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send message</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const analyticsFilter = document.getElementById('admin-analytics-filter');
    const analyticsPeriod = document.getElementById('admin-analytics-period');
    const customPeriodFields = document.getElementById('admin-custom-period-fields');
    const customPeriodInputs = [
        document.getElementById('admin-period-start'),
        document.getElementById('admin-period-end'),
    ];

    const syncCustomPeriodFields = () => {
        const isCustom = analyticsPeriod.value === 'custom';

        customPeriodFields.classList.toggle('d-none', !isCustom);
        customPeriodFields.classList.toggle('d-block', isCustom);
        customPeriodInputs.forEach((input) => {
            input.required = isCustom;
            input.disabled = !isCustom;
        });

        if (!isCustom) {
            customPeriodInputs.forEach((input) => {
                input.value = '';
            });
        }
    };

    analyticsPeriod.addEventListener('change', () => {
        syncCustomPeriodFields();

        if (analyticsPeriod.value !== 'custom') {
            analyticsFilter.requestSubmit();
        }
    });

    syncCustomPeriodFields();

    document.addEventListener('show.bs.modal', function (event) {
        if (event.target.id !== 'messageStudentModal') {
            return;
        }

        const trigger = event.relatedTarget;
        const form = document.getElementById('messageStudentForm');
        const recipient = document.getElementById('messageStudentRecipient');
        const message = document.getElementById('messageStudentText');

        form.action = trigger.dataset.messageAction;
        recipient.textContent = `Message to ${trigger.dataset.studentName}`;
        message.value = '';
    });
</script>
@endpush