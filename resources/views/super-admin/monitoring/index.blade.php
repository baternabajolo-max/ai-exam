@extends('layouts.app')

@section('title', 'Faculty Monitoring | LEMU')

@section('content')
<link rel="stylesheet" href="{{ asset('css/super-admin/super-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/super-admin/monitoring.css') }}">

<main class="super-admin-dashboard monitoring-dashboard py-4 py-lg-5">
    <div class="container">
        <header class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <span class="dashboard-eyebrow">Institution oversight</span>
                <h1 class="dashboard-title mb-1">Faculty Monitoring</h1>
                <p class="dashboard-subtitle mb-0">
                    Follow Question Bank contributions by college and academic program.
                </p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="{{ route('super-admin.question-bank.index') }}" class="btn btn-outline-primary rounded-3">
                    <i class="bi bi-journal-text me-1" aria-hidden="true"></i> Question Bank
                </a>
                <a href="{{ route('super-admin.item-analysis.index') }}" class="btn btn-primary rounded-3">
                    <i class="bi bi-bar-chart me-1" aria-hidden="true"></i> Item Analysis
                </a>
            </div>
        </header>

        <nav class="monitoring-breadcrumb mb-3" aria-label="Monitoring navigation">
            <button type="button" class="monitoring-breadcrumb-link" id="monitoring-home-link">Monitoring</button>
            <span class="monitoring-breadcrumb-separator" aria-hidden="true">/</span>
            <span id="monitoring-college-crumb" class="text-secondary" hidden></span>
            <span id="monitoring-program-separator" class="monitoring-breadcrumb-separator" aria-hidden="true" hidden>/</span>
            <span id="monitoring-program-crumb" class="fw-semibold text-dark" hidden></span>
        </nav>
        <p id="monitoring-status" class="visually-hidden" role="status" aria-live="polite"></p>

        <section class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" aria-label="Monitoring overview">
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Total Faculty
                        <button type="button" class="kpi-info-trigger" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About total faculty" title="Counts all Faculty accounts in the system.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ number_format($overview['total_faculty']) }}</strong>
                    <span class="system-kpi-note">All Faculty accounts</span>
                </article>
            </div>
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon is-green"><i class="bi bi-person-check" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Active Faculty
                        <button type="button" class="kpi-info-trigger" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About active faculty" title="Counts Faculty accounts with a verified email, matching the existing account status rule.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ number_format($overview['active_faculty']) }}</strong>
                    <span class="system-kpi-note">Verified accounts</span>
                </article>
            </div>
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon is-gold"><i class="bi bi-patch-question" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Total Questions Added
                        <button type="button" class="kpi-info-trigger" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About total questions added" title="Counts unique Question Bank items, regardless of how often a question is edited or reused in exams.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ number_format($overview['total_questions']) }}</strong>
                    <span class="system-kpi-note" id="monitoring-question-trend">
                        {{ number_format($monthlyContributions['current_month_count']) }} attributed this month
                    </span>
                </article>
            </div>
            <div class="col">
                <article class="system-kpi h-100">
                    <span class="system-kpi-icon"><i class="bi bi-file-earmark-check" aria-hidden="true"></i></span>
                    <span class="system-kpi-label">
                        Examination Submissions
                        <button type="button" class="kpi-info-trigger" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="About examination submissions" title="Counts recorded Results linked to scheduled examinations; practice quizzes are excluded.">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                        </button>
                    </span>
                    <strong class="system-kpi-value">{{ number_format($overview['total_exam_submissions']) }}</strong>
                    <span class="system-kpi-note">Scheduled examination Results</span>
                </article>
            </div>
        </section>

        <section class="analytics-panel mb-4" aria-labelledby="college-monitoring-title">
            <div class="analytics-panel-header">
                <div>
                    <h2 class="h5 mb-1" id="college-monitoring-title">College &amp; Program Monitoring</h2>
                    <p class="small text-secondary mb-0">Explore Question Bank contributions by college and program.</p>
                </div>
                <span class="monitoring-benchmark-key"><span aria-hidden="true"></span> Average questions</span>
            </div>

            <div class="monitoring-level-tabs" role="tablist" aria-label="Monitoring level">
                <button type="button" class="monitoring-level-tab is-active" id="college-monitoring-tab"
                        role="tab" aria-selected="true" aria-controls="college-monitoring-panel" tabindex="0">
                    Colleges
                </button>
                <button type="button" class="monitoring-level-tab" id="program-monitoring-tab"
                        role="tab" aria-selected="false" aria-controls="program-monitoring-panel" tabindex="-1" disabled>
                    Programs
                </button>
            </div>

            <div id="college-monitoring-panel" class="monitoring-level-panel" role="tabpanel"
                 aria-labelledby="college-monitoring-tab">
                <p class="small text-secondary mb-0">Select a college to open its programs in this same panel.</p>
                <div class="monitoring-chart-wrapper">
                    <canvas id="college-monitoring-chart" role="img" aria-label="Question Bank contributions by college"></canvas>
                </div>
                <div id="college-monitoring-actions" class="monitoring-chart-actions" aria-label="Select a college"></div>
                <div id="college-monitoring-error" class="alert alert-warning small mt-3 mb-0" role="alert" hidden></div>
                <div id="college-monitoring-empty" class="monitoring-empty mt-3" hidden>No colleges are available to monitor.</div>
            </div>

            <div id="program-monitoring-panel" class="monitoring-level-panel" role="tabpanel"
                 aria-labelledby="program-monitoring-tab" hidden>
                <div class="analytics-panel-header monitoring-program-panel-header">
                    <div>
                        <h3 class="h6 mb-1" id="program-monitoring-title">Programs</h3>
                        <p class="small text-secondary mb-0" id="program-monitoring-description">Select a college first.</p>
                    </div>
                </div>
                <div class="monitoring-chart-wrapper">
                    <canvas id="program-monitoring-chart" role="img" aria-label="Question Bank contributions by academic program"></canvas>
                </div>
                <div id="program-monitoring-actions" class="monitoring-chart-actions" aria-label="Select an academic program"></div>
                <div id="program-monitoring-loading" class="monitoring-loading" role="status" hidden>
                    <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                    Loading programs…
                </div>
                <div id="program-monitoring-error" class="alert alert-warning small mt-3 mb-0" role="alert" hidden></div>
                <div id="program-monitoring-empty" class="monitoring-empty mt-3" hidden>No academic programs are registered for this college.</div>
            </div>
        </section>

        <section id="program-contribution-section" class="mb-4" aria-labelledby="program-contribution-title" hidden>
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-3">
                <div>
                    <span class="dashboard-eyebrow mb-1">Selected academic program</span>
                    <h2 class="h4 mb-1" id="program-contribution-title"></h2>
                    <p class="small text-secondary mb-0" id="program-contribution-summary"></p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="monitoring-back-to-programs">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to programs
                </button>
            </div>
            <div id="program-details-loading" class="monitoring-loading mb-3" role="status" hidden>
                <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                Loading program contributions…
            </div>
            <div id="program-details-error" class="alert alert-warning small" role="alert" hidden></div>

            <section class="analytics-panel" aria-labelledby="faculty-contributions-title">
                <div class="analytics-panel-header">
                    <div>
                        <h3 class="h5 mb-1" id="faculty-contributions-title">Faculty Distribution</h3>
                        <p class="small text-secondary mb-0">See each faculty member’s questions and the folders they have contributed to.</p>
                    </div>
                    <span id="faculty-contribution-count" class="badge rounded-pill bg-light text-secondary border"></span>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 mb-3">
                    <div class="flex-grow-1">
                        <label class="visually-hidden" for="faculty-contribution-search">Search faculty</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input id="faculty-contribution-search" type="search" class="form-control" placeholder="Search faculty">
                        </div>
                    </div>
                    <div>
                        <label class="visually-hidden" for="faculty-contribution-sort">Sort faculty</label>
                        <select id="faculty-contribution-sort" class="form-select">
                            <option value="most">Most questions</option>
                            <option value="least">Fewest questions</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle monitoring-faculty-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Total Questions Added</th>
                                <th scope="col">Folders Contributed To</th>
                                <th scope="col" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="faculty-contribution-rows"></tbody>
                    </table>
                </div>
                <div id="faculty-contribution-empty" class="monitoring-empty mt-3" hidden>No faculty accounts are associated with this college.</div>
                <p id="faculty-unattributed-note" class="small text-secondary mt-3 mb-0" hidden></p>
            </section>
        </section>

        <section class="analytics-panel" aria-labelledby="monthly-contributions-title">
            <div class="analytics-panel-header">
                <div>
                    <h2 class="h5 mb-1" id="monthly-contributions-title">Monthly Faculty Contributions</h2>
                    <p class="small text-secondary mb-0" id="monthly-contributions-scope">Question Bank questions attributed to faculty by their original creation date.</p>
                </div>
                <span class="monitoring-benchmark-key"><span aria-hidden="true"></span> 12-month average</span>
            </div>
            <div class="monitoring-monthly-chart-wrapper">
                <canvas id="monthly-contributions-chart" role="img" aria-label="Faculty Question Bank contributions by month"></canvas>
            </div>
            <p id="monthly-contributions-empty" class="monitoring-empty mt-3" hidden>No faculty-attributed questions were added in the last 12 months.</p>
        </section>

        <div class="modal fade" id="faculty-contribution-modal" tabindex="-1" aria-labelledby="faculty-contribution-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <div>
                            <span class="dashboard-eyebrow mb-1">Contribution details</span>
                            <h2 class="modal-title h5 mb-0" id="faculty-contribution-modal-title">Faculty member</h2>
                            <p class="small text-secondary mb-0" id="faculty-contribution-context"></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="faculty-contribution-modal-status" class="small text-secondary" role="status" aria-live="polite"></p>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="monitoring-modal-stat">
                                    <span>Questions contributed</span>
                                    <strong id="faculty-modal-question-count">—</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="monitoring-modal-stat">
                                    <span>Folders contributed to</span>
                                    <strong id="faculty-modal-folder-count">—</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="monitoring-modal-stat">
                                    <span>Total folders</span>
                                    <strong id="faculty-modal-total-folder-count">—</strong>
                                </div>
                            </div>
                        </div>
                        <h3 class="h6 fw-bold mb-3">Folder Coverage</h3>
                        <div id="faculty-modal-folders" class="monitoring-folder-grid monitoring-modal-folder-grid"></div>
                        <div id="faculty-modal-empty" class="monitoring-empty" hidden>No folders exist in this program yet.</div>
                        <form id="faculty-message-form" class="monitoring-message-composer mt-4" hidden>
                            @csrf
                            <input id="faculty-message-course-id" type="hidden" name="course_id">
                            <label for="faculty-message-input" class="form-label fw-semibold">Message to faculty</label>
                            <textarea id="faculty-message-input" name="message" class="form-control" rows="4" maxlength="2000" required
                                      placeholder="Write a one-way message to this faculty member."></textarea>
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-2">
                                <span id="faculty-message-status" class="small text-secondary" role="status" aria-live="polite"></span>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-light rounded-pill px-3" id="faculty-message-cancel">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="faculty-message-send">
                                        <i class="bi bi-send me-1" aria-hidden="true"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary rounded-pill px-3" id="faculty-message-toggle">
                            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i> Message Faculty
                        </button>
                        <a class="btn btn-primary rounded-pill px-3" id="faculty-view-questions" href="#">
                            <i class="bi bi-journal-text me-1" aria-hidden="true"></i> View Questions
                        </a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.monitoringConfig = {{ Illuminate\Support\Js::from([
        'colleges' => $departments->map(fn ($department) => [
            'id' => $department->id,
            'name' => $department->name,
            'question_count' => (int) $department->question_bank_items_count,
        ])->values(),
        'monthlyContributions' => $monthlyContributions,
        'allCollegesMonthlyContributions' => $allCollegesMonthlyContributions,
        'selectedCollegeId' => $selectedDepartmentId,
        'selectedProgramId' => $selectedCourseId,
        'programsUrlTemplate' => route('super-admin.monitoring.programs', ['department' => '__ID__']),
        'programDetailsUrlTemplate' => route('super-admin.monitoring.program-details', ['course' => '__ID__']),
        'facultyContributionsUrlTemplate' => route('super-admin.monitoring.faculty-contributions', ['faculty' => '__ID__']),
        'facultyMessageUrlTemplate' => route('super-admin.monitoring.faculty-message', ['faculty' => '__ID__']),
        'questionBankUrl' => route('super-admin.question-bank.index'),
    ]) }};
</script>
<script src="{{ asset('js/super-admin/monitoring.js') }}"></script>
@endsection
