@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/super-admin/super-admin.css') }}">

<main class="super-admin-dashboard py-4 py-lg-5">
    <div class="container">
        <header class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <span class="dashboard-eyebrow">Institution overview</span>
                <h1 class="dashboard-title mb-1">System analytics</h1>
                <p class="dashboard-subtitle mb-0">
                    @if($selectedCourse)
                        Viewing engagement, assessment outcomes, and enrollment trends for {{ $selectedCourse->name }}.
                    @elseif($selectedDepartment)
                        Viewing engagement, assessment outcomes, and enrollment trends for {{ $selectedDepartment->name }}.
                    @else
                        Compare college engagement and general-test outcomes, and follow enrollment trends.
                    @endif
                </p>
            </div>
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                <form method="GET" action="{{ route('super-admin.dashboard') }}" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="period" value="{{ $period }}">
                    @if ($period === 'custom')
                        <input type="hidden" name="start_date" value="{{ $periodStartDate }}">
                        <input type="hidden" name="end_date" value="{{ $periodEndDate }}">
                    @endif
                    <label for="super-admin-college-filter" class="visually-hidden">View dashboard for a specific college</label>
                    <select id="super-admin-college-filter" name="college_id" class="form-select border rounded-3 shadow-sm" aria-label="View dashboard for a specific college">
                        <option value="" @selected((string) old('college_id', $selectedDepartmentId ?? '') === '')>All Colleges</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('college_id', $selectedDepartmentId) === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <form method="GET" action="{{ route('super-admin.dashboard') }}" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="college_id" value="{{ $selectedDepartmentId ?? '' }}">
                    <input type="hidden" name="period" value="{{ $period }}">
                    @if ($period === 'custom')
                        <input type="hidden" name="start_date" value="{{ $periodStartDate }}">
                        <input type="hidden" name="end_date" value="{{ $periodEndDate }}">
                    @endif
                    <label for="super-admin-program-filter" class="visually-hidden">View dashboard for a specific program</label>
                    <select id="super-admin-program-filter" name="course_id" class="form-select border rounded-3 shadow-sm" aria-label="View dashboard for a specific program">
                        <option value="">All Programs</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $selectedCourseId ?? '') === (string) $course->id)>
                                {{ $course->name }} · {{ $course->department->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <div class="dropdown analytics-period-picker">
                    <button
                        class="btn btn-white border rounded-3 shadow-sm d-flex align-items-center gap-3 px-3 py-2 text-start analytics-period-trigger"
                        id="super-admin-analytics-period-toggle"
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
                    <div class="dropdown-menu dropdown-menu-end analytics-period-menu p-3" aria-labelledby="super-admin-analytics-period-toggle">
                        <form method="GET" action="{{ route('super-admin.dashboard') }}" id="super-admin-analytics-filter">
                            <input type="hidden" name="college_id" value="{{ $selectedDepartmentId ?? '' }}">
                            <input type="hidden" name="course_id" value="{{ $selectedCourseId ?? '' }}">
                            <label for="super-admin-analytics-period" class="form-label small fw-semibold text-secondary">Reporting period</label>
                            <select id="super-admin-analytics-period" name="period" class="form-select form-select-sm analytics-filter mb-3">
                                @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $days => $label)
                                    <option value="{{ $days }}" @selected((string) old('period', $period) === (string) $days)>{{ $label }}</option>
                                @endforeach
                                <option value="custom" @selected((string) old('period', $period) === 'custom')>Custom range</option>
                            </select>
                            <div id="super-admin-custom-period-fields" class="{{ (string) old('period', $period) === 'custom' ? 'd-block' : 'd-none' }}">
                                <div class="d-flex gap-2 mb-2">
                                    <div class="flex-fill">
                                        <label for="super-admin-period-start" class="form-label small text-secondary">Start date</label>
                                        <input id="super-admin-period-start" type="date" name="start_date" class="form-control form-control-sm"
                                               value="{{ old('start_date', $periodStartDate) }}" max="{{ now()->toDateString() }}"
                                               @required((string) old('period', $period) === 'custom')
                                               @disabled((string) old('period', $period) !== 'custom')>
                                    </div>
                                    <div class="flex-fill">
                                        <label for="super-admin-period-end" class="form-label small text-secondary">End date</label>
                                        <input id="super-admin-period-end" type="date" name="end_date" class="form-control form-control-sm"
                                               value="{{ old('end_date', $periodEndDate) }}" max="{{ now()->toDateString() }}"
                                               @required((string) old('period', $period) === 'custom')
                                               @disabled((string) old('period', $period) !== 'custom')>
                                    </div>
                                </div>
                                <small class="text-secondary">Select up to 366 days.</small>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100 mt-2">Apply reporting period</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        @if($studentSearch !== '')
            <section class="dashboard-card-3d p-4 mb-4" aria-label="Student search results">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Student search results</h2>
                        <p class="text-secondary small mb-0">
                            Matches for “{{ $studentSearch }}”
                            @if($selectedCourse) in {{ $selectedCourse->name }}
                            @elseif($selectedDepartment) in {{ $selectedDepartment->name }}
                            @else across all colleges @endif
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-secondary border">{{ $matchingStudents->count() }} matches</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($matchingStudents as $student)
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 px-3 py-3 rounded-3 mb-1"
                           href="{{ route('super-admin.students.show', [
                               'student' => $student,
                               'college_id' => $selectedDepartmentId,
                               'course_id' => $selectedCourseId,
                               'student_search' => $studentSearch,
                           ]) }}">
                            <span class="min-w-0">
                                <strong class="text-dark d-block">{{ $student->name }}</strong>
                                <small class="text-muted d-block">{{ $student->email }} · {{ $student->student_id_number ?: 'No student ID' }}</small>
                                <small class="text-muted d-block">{{ $student->department?->name ?? 'No college' }} · {{ $student->course?->name ?? 'No program' }}</small>
                            </span>
                            <span class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0">View history</span>
                        </a>
                    @empty
                        <p class="text-muted small mb-0">No students match that search.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <section class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3 mb-4" aria-label="Institution summary">
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        {{ $selectedCourse ? 'Programs' : 'Colleges' }}
                        <button type="button" class="kpi-info-trigger" data-kpi-tooltip data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About {{ $selectedCourse ? 'programs' : 'colleges' }}" title="Counts {{ $selectedCourse ? 'programs' : 'colleges' }} in the current dashboard scope.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ $totalDepartments }}</strong>
                    <span class="system-kpi-note">{{ $selectedCourse ? 'Selected program' : ($selectedDepartment ? 'Selected college' : 'Configured in the system') }}</span>
                </article>
            </div>
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Faculty
                        <button type="button" class="kpi-info-trigger" data-kpi-tooltip data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About faculty" title="Counts faculty accounts associated with the selected college or program, or across the system.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ $totalAdmins }}</strong>
                    <span class="system-kpi-note">{{ $selectedCourse ? 'Associated with '.$selectedCourse->name : ($selectedDepartment ? 'In '.$selectedDepartment->name : 'Across all colleges') }}</span>
                </article>
            </div>
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Students
                        <button type="button" class="kpi-info-trigger" data-kpi-tooltip data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About students" title="Counts registered student accounts in the current dashboard scope.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ $totalStudents }}</strong>
                    <span class="system-kpi-note">{{ $selectedCourse ? 'Registered in '.$selectedCourse->name : ($selectedDepartment ? 'Registered in '.$selectedDepartment->name : 'All registered student accounts') }}</span>
                </article>
            </div>
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon is-green"><i class="bi bi-activity" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Active participation
                        <button type="button" class="kpi-info-trigger" data-kpi-tooltip data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About active participation" title="The percentage of students with at least one reading session or assessment during the selected period.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ $activeParticipationRate === null ? '—' : $activeParticipationRate.'%' }}</strong>
                    <span class="system-kpi-note">{{ $activeStudentCount }} / {{ $departmentStudentTotal }} {{ $selectedCourse ? 'program students' : ($selectedDepartment ? 'students' : 'college students') }} · last {{ $periodDays }} days</span>
                </article>
            </div>
        </section>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="small text-secondary">Active = at least one reading session or assessment</span>
        </div>

        <section class="row g-4 mb-4" aria-label="Enrollment and program performance charts">
            <div class="col-xl-6">
                <article class="analytics-panel h-100">
                    <header class="analytics-panel-header">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Monthly student registrations</h2>
                            <p class="small text-secondary mb-0">
                                New student accounts {{ $selectedCourse ? 'in '.$selectedCourse->name : ($selectedDepartment ? 'in '.$selectedDepartment->name : 'by college') }} · last 12 months
                            </p>
                        </div>
                    </header>
                    <div class="system-chart-wrapper">
                        <canvas id="departmentStudentChart" role="img" aria-label="Monthly new student registrations by program over the last 12 months"></canvas>
                    </div>
                    <p class="visually-hidden">
                        Registration trend chart. Data is grouped by calendar month and year.
                    </p>
                </article>
            </div>
            <div class="col-xl-6">
                <article class="analytics-panel h-100">
                    <header class="analytics-panel-header">
                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ $selectedCourse ? 'General-test score · '.$selectedCourse->name : ($selectedDepartment ? 'General-test score · '.$selectedDepartment->name : 'General-test score by college') }}</h2>
                            <p class="small text-secondary mb-0">Average latest student score · dashed line marks the 75% target</p>
                        </div>
                    </header>
                    @if($departmentAnalytics->isEmpty())
                        <div class="analytics-empty">No programs or colleges are available to compare yet.</div>
                    @elseif($departmentAnalytics->every(fn (array $department): bool => $department['average_score'] === null))
                        <div class="analytics-empty">No general-test attempts in the selected period.</div>
                    @else
                        <div class="system-chart-wrapper">
                            <canvas id="departmentPerformanceChart" role="img" aria-label="Average general-test score comparison by program for the last {{ $periodDays }} days" aria-describedby="department-summary-title"></canvas>
                        </div>
                    @endif
                    <p class="visually-hidden">Hover or focus on a bar to see student counts, pass rate, and active participation for that program.</p>
                </article>
            </div>
        </section>

        <section class="analytics-panel" aria-labelledby="department-summary-title">
            <header class="analytics-panel-header">
                <div>
                    <h2 id="department-summary-title" class="h5 fw-bold mb-1">{{ $selectedCourse ? 'Program performance and participation' : 'College performance and participation' }}</h2>
                    <p class="small text-secondary mb-0">Use the sample counts to compare outcomes fairly across cohort sizes.</p>
                </div>
                <span class="period-chip">{{ $periodLabel }}</span>
            </header>
            <div class="table-responsive">
                <table class="table align-middle mb-0 department-summary-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ $selectedCourse ? 'Program' : 'College' }}</th>
                            <th scope="col">Active students</th>
                            <th scope="col">Test participants</th>
                            <th scope="col">Average score</th>
                            <th scope="col">Pass rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departmentAnalytics as $department)
                            <tr>
                                <th scope="row">
                                    <span class="college-color-dot" style="--college-color: {{ $department['color'] }};"></span>
                                    {{ $department['name'] }}
                                    <span class="d-block small text-secondary fw-normal ms-4">{{ $department['students'] }} students enrolled</span>
                                </th>
                                <td>{{ $department['active_students'] }} / {{ $department['students'] }}</td>
                                <td>{{ $department['participants'] }}</td>
                                <td>{{ $department['average_score'] === null ? '—' : $department['average_score'].'%' }}</td>
                                <td>{{ $department['pass_rate'] === null ? '—' : $department['pass_rate'].'%' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No programs or colleges have been created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.dashboardAnalytics = {{ Illuminate\Support\Js::from([
        'registrationLabels' => $chartLabels,
        'registrationDatasets' => $departmentDatasets,
        'periodDays' => $periodDays,
        'performanceLabels' => $departmentAnalytics->pluck('name')->all(),
        'performanceDatasets' => $departmentScoreDatasets,
        'departments' => $departmentAnalytics->all(),
    ]) }};
</script>
<script src="{{ asset('js/super-admin/super-admin.js') }}"></script>
<script>
    const superAdminAnalyticsFilter = document.getElementById('super-admin-analytics-filter');
    const superAdminCollegeFilter = document.getElementById('super-admin-college-filter');
    const superAdminAnalyticsPeriod = document.getElementById('super-admin-analytics-period');
    const superAdminCustomPeriodFields = document.getElementById('super-admin-custom-period-fields');
    const superAdminCustomPeriodInputs = [
        document.getElementById('super-admin-period-start'),
        document.getElementById('super-admin-period-end'),
    ];

    const syncSuperAdminCustomPeriodFields = () => {
        const isCustom = superAdminAnalyticsPeriod.value === 'custom';
        superAdminCustomPeriodFields.classList.toggle('d-none', !isCustom);
        superAdminCustomPeriodFields.classList.toggle('d-block', isCustom);
        superAdminCustomPeriodInputs.forEach((input) => {
            input.required = isCustom;
            input.disabled = !isCustom;
        });
    };

    superAdminAnalyticsPeriod.addEventListener('change', () => {
        syncSuperAdminCustomPeriodFields();
        if (superAdminAnalyticsPeriod.value !== 'custom') {
            superAdminAnalyticsFilter.requestSubmit();
        }
    });

    superAdminCollegeFilter.addEventListener('change', () => {
        superAdminCollegeFilter.form.requestSubmit();
    });

    syncSuperAdminCustomPeriodFields();
</script>
@endsection
