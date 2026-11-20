@extends('layouts.app')

@section('content')
<style>
    .module-review-hero { background:linear-gradient(125deg,#102a56,#2948a3 58%,#5143a8); border-radius:1.25rem; color:#fff; padding:2rem; box-shadow:0 1rem 2rem rgb(35 54 115 / 16%); }
    .module-tabs { align-items:center; background:#f8fafc; border:1px solid rgb(148 163 184 / 28%); border-radius:999px; display:inline-flex; gap:.25rem; margin-top:2rem; max-width:100%; overflow-x:auto; padding:.25rem; position:relative; scrollbar-width:none; }
    .module-tabs::-webkit-scrollbar { display:none; }
    .module-tab { align-items:center; background:transparent; border:0; border-radius:999px; color:#0d6efd; display:inline-flex; flex:0 0 auto; font-size:.925rem; font-weight:700; gap:.45rem; min-height:40px; padding:.55rem 1rem; position:relative; transition:color .2s ease; z-index:1; }
    .module-tab:hover, .module-tab:focus-visible { color:#0a58ca; }
    .module-tab:focus-visible { outline:2px solid #0d6efd; outline-offset:2px; }
    .module-tab.is-active { color:#fff; }
    .module-tab-count { align-items:center; background:#e9ecef; border-radius:999px; color:#475569; display:inline-flex; font-size:.75rem; font-variant-numeric:tabular-nums; font-weight:700; justify-content:center; min-width:1.35rem; padding:.1rem .35rem; }
    .module-tab.is-active .module-tab-count { background:#ffc107; color:#212529; }
    .module-tab-indicator { background:#0d6efd; border-radius:999px; height:calc(100% - .5rem); left:0; position:absolute; top:.25rem; transition:transform .22s ease-out,width .22s ease-out; }
    .module-tab-panel { opacity:0; transform:translateY(5px); transition:opacity .2s ease-out,transform .2s ease-out; }
    .module-tab-panel.is-active { opacity:1; transform:translateY(0); }
    .module-tab-panel[hidden] { display:none; }
    .module-review-panel { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; box-shadow:0 .75rem 1.75rem rgb(15 23 42 / 7%); }
    .module-topic-row { border-bottom:1px solid #edf2f7; padding:1rem; }
    .module-topic-row:last-child { border-bottom:0; }
    .module-topic-card { border:1px solid #e2e8f0; border-radius:.85rem; }
    .module-review-controls { display:grid; gap:.75rem; grid-template-columns:minmax(12rem,1fr) minmax(12rem,1fr) minmax(10rem,.7fr); width:min(100%,44rem); }
    .module-review-table th { color:#64748b; font-size:.72rem; letter-spacing:.04em; text-transform:uppercase; }
    .module-review-table td { border-color:#edf2f7; padding:.9rem .75rem; }
    .module-tabular { font-variant-numeric:tabular-nums; }
    .module-review-student-row { cursor:pointer; }
    .module-review-student-row:hover, .module-review-student-row:focus-visible { background:#eff6ff; outline:0; }
    .module-student-activity-button { background:transparent; border:0; color:#0d6efd; font:inherit; font-weight:700; padding:0; text-align:left; text-decoration-color:transparent; text-underline-offset:.15em; transition:text-decoration-color .18s ease; }
    .module-student-activity-button:hover, .module-student-activity-button:focus-visible { color:#0a58ca; text-decoration:underline; }
    .module-student-activity-button:focus-visible { outline:2px solid #0d6efd; outline-offset:3px; }
    .module-activity-kpis { display:grid; gap:.75rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    .module-activity-kpi { background:#fbf8f6; border:1px solid #eee5e2; border-radius:.875rem; min-width:0; padding:1rem; }
    .module-activity-kpi-label { align-items:center; color:#6b5558; display:flex; font-size:.72rem; font-weight:700; gap:.25rem; justify-content:space-between; margin-bottom:.35rem; }
    .module-activity-kpi-label .module-info-tooltip { flex-shrink:0; }
    .module-info-tooltip { align-items:center; background:transparent; border:0; color:#806a6d; display:inline-flex; font-size:.85rem; justify-content:center; line-height:1; min-height:32px; min-width:32px; padding:.25rem; vertical-align:middle; }
    .module-info-tooltip:hover, .module-info-tooltip:focus-visible { color:#4a0e17; }
    .tooltip .tooltip-inner { max-width:320px; text-align:left; }
    .module-info-tooltip-popup { transition:opacity 150ms ease-out; }
    .module-activity-kpi-value { color:#2c070e; font-size:1rem; font-weight:800; overflow-wrap:anywhere; }
    .module-topic-progress { height:.45rem; }
    .module-status-pill { border-radius:999px; display:inline-flex; font-size:.75rem; font-weight:700; padding:.3rem .65rem; white-space:nowrap; }
    .module-status-pill--completed, .module-status-pill--on-track { background:#dcfce7; color:#166534; }
    .module-status-pill--in-progress { background:#fef3c7; color:#92400e; }
    .module-status-pill--needs-review { background:#ffedd5; color:#9a3412; }
    .module-status-pill--inactive, .module-status-pill--not-started { background:#f1f5f9; color:#475569; }
    .module-topic-breakdown { min-width:760px; }
    .module-attempt-history { min-width:520px; }
    .module-chart-card { background:#fff; border:1px solid #ece7e5; border-radius:1rem; padding:1rem; }
    .module-activity-tabs { border-bottom:1px solid #ece7e5; display:flex; gap:.5rem; margin-bottom:1rem; overflow-x:auto; }
    .module-activity-tab { background:transparent; border:0; border-bottom:3px solid transparent; color:#6b5558; flex:0 0 auto; font-size:.875rem; font-weight:700; min-height:44px; padding:.5rem .85rem; transition:color .18s ease,border-color .18s ease; }
    .module-activity-tab.is-active { border-bottom-color:#4a0e17; color:#4a0e17; }
    .module-activity-tab:disabled { color:#a8a29e; cursor:not-allowed; }
    .module-activity-tab:focus-visible { outline:2px solid #92400e; outline-offset:-2px; }
    .module-chart-unit-label { color:#6b5558; font-size:.75rem; font-weight:700; margin-bottom:.5rem; }
    .module-chart-layout { align-items:start; display:grid; gap:.5rem; grid-template-columns:3.25rem minmax(0,1fr); }
    .module-chart-layout.is-calendar { grid-template-columns:minmax(0,1fr); }
    .module-chart-stage { min-width:0; width:100%; }
    .module-chart-y-axis { align-items:flex-end; color:#6b5558; display:flex; flex-direction:column; font-size:.65rem; font-variant-numeric:tabular-nums; height:8rem; justify-content:space-between; line-height:1; margin-top:1.25rem; text-align:right; }
    .module-chart-y-axis[hidden] { display:none; }
    .module-chart-bars { align-items:start; display:grid; grid-auto-columns:minmax(3.25rem,1fr); grid-auto-flow:column; gap:.5rem; grid-template-columns:none; height:10.25rem; overflow-x:auto; padding:0 .1rem; }
    .module-chart-bar { align-items:center; display:flex; flex-direction:column; height:10.25rem; min-width:0; padding-top:1.25rem; position:relative; }
    .module-chart-bar--interactive { background:transparent; border:0; border-radius:.5rem; color:inherit; cursor:pointer; font:inherit; padding:1.25rem .25rem 0; }
    .module-chart-bar--interactive:hover, .module-chart-bar--interactive.is-selected { background:#fffbeb; }
    .module-chart-bar--interactive:focus-visible, .module-calendar-cell:focus-visible { outline:2px solid #92400e; outline-offset:2px; }
    .module-chart-value { color:#5c4749; font-size:.62rem; font-variant-numeric:tabular-nums; left:0; position:absolute; right:0; text-align:center; top:0; white-space:nowrap; }
    .module-chart-plot { align-items:end; border-bottom:1px solid #ded8d5; display:flex; height:8rem; justify-content:center; position:relative; width:100%; }
    .module-chart-gridline { border-top:1px solid #f0edeb; left:0; pointer-events:none; position:absolute; right:0; z-index:0; }
    .module-chart-fill { background:linear-gradient(180deg,#d6a11d,#4a0e17); border-radius:.45rem .45rem 0 0; max-width:2.5rem; min-height:0; transform:scaleY(0); transform-origin:bottom; transition:transform .48s cubic-bezier(.2,.8,.2,1); width:72%; z-index:1; }
    .module-chart-fill.is-animated { transform:scaleY(1); }
    .module-chart-label { color:#6b5558; font-size:.68rem; line-height:1.25; margin-top:.45rem; max-width:100%; overflow-wrap:anywhere; text-align:center; }
    .module-chart-view { opacity:0; transform:translateY(4px); transition:opacity .18s ease-out,transform .18s ease-out; }
    .module-chart-view.is-active { opacity:1; transform:translateY(0); }
    .module-chart-view[hidden] { display:none!important; }
    .module-activity-calendar { display:grid; gap:.5rem; grid-template-columns:repeat(7,minmax(0,1fr)); margin-inline:auto; max-width:28rem; width:100%; }
    .module-activity-calendar .module-chart-label { text-align:center; }
    .module-calendar-cell { align-items:center; aspect-ratio:1; background:#f3f4f6; border:0; border-radius:.35rem; color:#475569; cursor:pointer; display:flex; font-size:.7rem; justify-content:center; min-height:2.5rem; padding:0; width:100%; }
    .module-calendar-cell[data-level="1"], .module-calendar-legend-swatch[data-level="1"] { background:#fca5a5; color:#7f1d1d; }
    .module-calendar-cell[data-level="2"], .module-calendar-legend-swatch[data-level="2"] { background:#fdba74; color:#7c2d12; }
    .module-calendar-cell[data-level="3"], .module-calendar-legend-swatch[data-level="3"] { background:#86efac; color:#14532d; }
    .module-calendar-cell[data-level="4"], .module-calendar-legend-swatch[data-level="4"] { background:#16a34a; color:#fff; }
    .module-calendar-cell--selected { outline:2px solid #4a0e17; outline-offset:1px; }
    .module-calendar-cell--empty { background:transparent; }
    .module-calendar-cell--empty:focus-visible { outline:none; }
    .module-calendar-legend { align-items:center; color:#6b5558; display:flex; flex-wrap:wrap; font-size:.75rem; gap:.45rem; justify-content:flex-end; margin:.85rem auto 0; max-width:28rem; }
    .module-calendar-legend[hidden] { display:none; }
    .module-calendar-legend-swatch { border:1px solid rgb(71 85 105 / 12%); border-radius:.2rem; display:inline-block; height:1rem; width:1rem; }
    .module-calendar-legend-none { background:#f3f4f6; }
    .module-chart-error { color:#b91c1c; }
    #moduleStudentActivityModal .modal-dialog, #moduleStudentNoticeModal .modal-dialog { transform:scale(.96); transition:transform .22s ease-out; }
    #moduleStudentActivityModal.show .modal-dialog, #moduleStudentNoticeModal.show .modal-dialog { transform:scale(1); }
    @media (max-width:767.98px) { .module-review-controls { grid-template-columns:1fr; width:100%; } }
    html[data-bs-theme="dark"] .module-review-panel, html[data-bs-theme="dark"] .module-topic-card { background:#1e293b; border-color:#334155; }
    @media (max-width:767.98px) { .module-activity-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:420px) { .module-activity-kpis { grid-template-columns:minmax(0,1fr); } }
    @media (prefers-reduced-motion:reduce) {
        .module-tab, .module-tab-indicator, .module-tab-panel, .module-student-activity-button,
        .module-chart-fill, .module-activity-tab, .module-chart-view, .module-info-tooltip-popup,
        #moduleStudentActivityModal .modal-dialog, #moduleStudentNoticeModal .modal-dialog { transition:none; }
    }
</style>

<main class="container pb-5">
    <header class="module-review-hero mb-4">
        <a href="{{ route('super-admin.modules.index', ['department_id' => $module->department_id, 'course_id' => $module->course_id]) }}" class="text-white text-decoration-none small fw-bold d-inline-flex align-items-center gap-2 mb-3">
            <i class="bi bi-arrow-left"></i> Back to Modules
        </a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-4">
            <div>
                <span class="badge text-bg-light mb-2">{{ $module->department->name ?? 'Unknown college' }} · {{ $module->course->name ?? 'Unknown program' }}</span>
                <h1 class="h2 fw-bold mb-2">{{ $module->title }}</h1>
                <p class="text-white-50 mb-0">{{ $module->description ?: 'No description provided.' }}</p>
            </div>
            @if($module->logo_path)
                <img src="{{ asset('storage/'.$module->logo_path) }}" alt="{{ $module->title }}" class="rounded-4 border border-white border-opacity-25" style="width:140px;height:140px;object-fit:cover;">
            @else
                <div class="rounded-4 border border-white border-opacity-25 d-flex align-items-center justify-content-center" style="width:140px;height:140px;">
                    <i class="bi bi-journal-bookmark fs-1"></i>
                </div>
            @endif
        </div>
        <nav class="module-tabs" id="moduleDetailTabs" role="tablist" aria-label="Module review sections">
            <span class="module-tab-indicator" aria-hidden="true"></span>
            <button type="button" class="module-tab is-active" id="moduleTopicsTab" role="tab" aria-selected="true" aria-controls="moduleTopicsPanel" data-review-tab="topics" tabindex="0">Topics <span class="module-tab-count">{{ $module->subtopics->count() }}</span></button>
            <button type="button" class="module-tab" id="moduleLeaderboardTab" role="tab" aria-selected="false" aria-controls="moduleLeaderboardPanel" data-review-tab="leaderboard" tabindex="-1">Leaderboards <span class="module-tab-count">{{ $leaderboard->count() }}</span></button>
            <button type="button" class="module-tab" id="moduleStudentsTab" role="tab" aria-selected="false" aria-controls="moduleStudentsPanel" data-review-tab="students" tabindex="-1">Students <span class="module-tab-count">{{ $students->count() }}</span></button>
        </nav>
    </header>

    <section class="module-review-panel module-tab-panel is-active p-4" id="moduleTopicsPanel" role="tabpanel" aria-labelledby="moduleTopicsTab" data-review-panel="topics">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Topics</h2>
                <p class="text-secondary small mb-0">View uploaded reviewer materials and flag topics that need editing.</p>
            </div>
            <div class="border rounded-3 px-3 py-2 bg-light text-dark">
                <span class="small text-secondary d-block">Total topics</span>
                <strong class="fs-5">{{ $module->subtopics->count() }}</strong>
            </div>
        </div>

        @forelse($module->subtopics as $subtopic)
            <article class="module-topic-card mb-3 p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-3 bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi bi-file-earmark-richtext fs-5"></i>
                    </span>
                    <div>
                        <h3 class="h6 fw-bold mb-1">{{ $subtopic->title }}</h3>
                        @if($subtopic->needs_revision)
                            <span class="badge text-bg-warning"><i class="bi bi-flag-fill me-1"></i>Needs revision</span>
                        @else
                            <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Ready</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($subtopic->file_path)
                        <a href="{{ asset('storage/'.$subtopic->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-eye me-1"></i>View file
                        </a>
                    @endif
                    <form method="POST" action="{{ route('super-admin.modules.subtopics.revision', $subtopic) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="needs_revision" value="{{ $subtopic->needs_revision ? '0' : '1' }}">
                        <button type="submit" class="btn btn-sm {{ $subtopic->needs_revision ? 'btn-outline-success' : 'btn-outline-warning' }} rounded-pill px-3">
                            <i class="bi {{ $subtopic->needs_revision ? 'bi-check2-circle' : 'bi-flag' }} me-1"></i>
                            {{ $subtopic->needs_revision ? 'Clear flag' : 'Flag for revision' }}
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
                No topics have been uploaded.
            </div>
        @endforelse
    </section>

    <section class="module-review-panel module-tab-panel p-4" id="moduleLeaderboardPanel" role="tabpanel" aria-labelledby="moduleLeaderboardTab" data-review-panel="leaderboard" hidden>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Quiz Points Leaderboard</h2>
                <p class="text-secondary small mb-0">Points earned from topic quiz attempts in this module.</p>
            </div>
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="moduleLeaderboardTopicFilter" class="form-label small fw-semibold mb-1">Filter by Topic</label>
                    <select id="moduleLeaderboardTopicFilter" class="form-select">
                        <option value="all">All Topics</option>
                        @foreach($module->subtopics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                        @endforeach
                    </select>
                </div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $leaderboard->count() }} students</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table module-review-table align-middle mb-0">
                <thead><tr><th>Rank</th><th>Student Name</th><th>Average Quiz Score (%)</th><th class="text-end">Total Points</th></tr></thead>
                <tbody id="moduleLeaderboardRows">
                    @forelse($leaderboard as $student)
                        <tr data-leaderboard-row data-rank="{{ $student['rank'] }}" data-name="{{ mb_strtolower($student['name']) }}"
                            data-topic-stats="{{ json_encode($student['topic_stats'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                            data-all-average="{{ $student['average_score'] === null ? 'No attempts' : number_format($student['average_score'], 2).'%' }}"
                            data-all-points="{{ number_format($student['total_points'], 2) }}"
                            @if($student['rank'] > 15) hidden @endif>
                            <td><span class="badge text-bg-light border module-tabular" data-rank-badge>{{ $student['rank'] }}</span></td>
                            <td class="fw-semibold">{{ $student['name'] }}</td>
                            <td class="module-tabular" data-average-score>{{ $student['average_score'] === null ? 'No attempts' : number_format($student['average_score'], 2).'%' }}</td>
                            <td class="fw-bold module-tabular text-end" data-total-points>{{ number_format($student['total_points'], 2) }} pts</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4" data-leaderboard-empty>No students are enrolled in this module's academic program.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaderboard->count() > 15)
            <div class="border-top p-3 text-center" id="moduleLeaderboardViewAllWrap">
                <button class="btn btn-outline-primary rounded-pill px-4" id="viewAllLeaderboard" type="button" aria-expanded="false">View All</button>
            </div>
        @endif
    </section>

    <section class="module-review-panel module-tab-panel p-4" id="moduleStudentsPanel" role="tabpanel" aria-labelledby="moduleStudentsTab" data-review-panel="students" hidden>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Student Study Activity</h2>
                <p class="text-secondary small mb-0">Track verified study time, topic completion, and quiz attempts.</p>
            </div>
            <div class="module-review-controls">
                <div>
                    <label for="moduleStudentSearch" class="form-label small fw-semibold mb-1">Search student</label>
                    <input id="moduleStudentSearch" type="search" class="form-control" placeholder="Type a student name...">
                </div>
                <div>
                    <label for="moduleStudentTopicFilter" class="form-label small fw-semibold mb-1">Filter by Topic</label>
                    <select id="moduleStudentTopicFilter" class="form-select">
                        <option value="all">All Topics</option>
                        @foreach($module->subtopics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="moduleStudentSort" class="form-label small fw-semibold mb-1">Sort by</label>
                    <select id="moduleStudentSort" class="form-select">
                        <option value="rank">By Rank</option>
                        <option value="name">A-Z</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table module-review-table align-middle mb-0">
                <thead><tr>
                    <th>Rank</th><th>Student Name</th>
                    <th>Verified Study Time <span class="text-secondary" title="Active reading time is counted after a quiz attempt and capped at 30 minutes per topic." aria-label="About verified study time">ⓘ</span></th>
                    <th data-topic-completion-header>Topics Completed <span class="text-secondary" title="Completed requires at least 5 minutes of active study and a quiz score of 75% or higher." aria-label="About topic completion">ⓘ</span></th>
                    <th>Quiz Attempts</th><th>Last Active</th><th>Status</th><th class="text-end">Action</th>
                </tr></thead>
                <tbody id="moduleStudentRows">
                    @forelse($reviewStudents as $student)
                        <tr class="module-review-student-row" data-review-student data-rank="{{ $student['rank'] }}"
                            data-name="{{ mb_strtolower($student['name']) }}" data-student-name="{{ $student['name'] }}"
                            data-topic-stats="{{ json_encode($student['topic_stats'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                            data-activity-url="{{ $student['activity_url'] }}" data-overview-url="{{ $student['overview_url'] }}"
                            data-notice-url="{{ $student['notice_url'] }}"
                            tabindex="0" role="button" aria-label="View study activity for {{ $student['name'] }}"
                            @if($student['rank'] > 15) hidden @endif>
                            <td><span class="badge text-bg-light border module-tabular" data-rank-badge>{{ $student['rank'] }}</span></td>
                            <td class="fw-semibold"><button type="button" class="module-student-activity-button" data-student-activity-open aria-label="View study activity for {{ $student['name'] }}">{{ $student['name'] }}</button></td>
                            <td data-verified-time>{{ $student['verified_seconds'] > 0 ? gmdate('H\\h i\\m', min($student['verified_seconds'], 86400)) : '0m' }}</td>
                            <td data-topic-completed>{{ $student['topics_completed'] }} / {{ $student['topic_count'] }} Topics</td>
                            <td data-quiz-attempts>{{ $student['quiz_attempts'] }}</td>
                            <td data-last-active>{{ $student['last_active_at'] ? \Carbon\CarbonImmutable::parse($student['last_active_at'])->diffForHumans() : 'Never' }}</td>
                            <td><span class="badge {{ $student['status'] === 'On Track' ? 'text-bg-success' : ($student['status'] === 'Needs Review' ? 'text-bg-warning' : 'text-bg-secondary') }}" data-status-pill>{{ $student['status'] }}</span></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-student-notice>Message</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4" data-student-empty-state>No students are enrolled in this module's academic program.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviewStudents->count() > 15)
            <div class="border-top p-3 text-center" id="moduleStudentsViewAllWrap">
                <button class="btn btn-outline-primary rounded-pill px-4" id="viewAllModuleStudents" type="button" aria-expanded="false">View All</button>
            </div>
        @endif
    </section>

    <div class="modal fade" id="moduleStudentActivityModal" data-course-title="{{ $module->title }}" tabindex="-1" aria-labelledby="moduleStudentActivityTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1" id="moduleStudentActivityTitle">Student Study Activity</h2>
                        <p class="small text-secondary mb-0" id="moduleActivityContext"></p>
                    </div>
                    <span class="module-status-pill ms-auto me-3" id="moduleActivityStatus"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close study activity"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="moduleActivityTopicFilter" value="all">
                    <div class="module-activity-kpis mb-4" id="moduleActivityKpis">
                        <article class="module-activity-kpi">
                            <h3 class="module-activity-kpi-label">
                                <span data-kpi-label="0">Verified Study Time</span>
                                <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                        aria-label="About verified study time" title="Counts active reading time after at least one quiz attempt, capped at 30 minutes per topic.">ⓘ</button>
                            </h3>
                            <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="0">—</p>
                            <div class="progress module-topic-progress mt-2" data-kpi-progress hidden>
                                <div class="progress-bar bg-danger" data-kpi-progress-bar role="progressbar" aria-label="Topic study time limit"></div>
                            </div>
                        </article>
                        <article class="module-activity-kpi">
                            <h3 class="module-activity-kpi-label">
                                <span data-kpi-label="1">Topics Completed</span>
                                <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                        aria-label="About topic completion" title="Completed requires at least 5 minutes of active study and a quiz score of 75% or higher. A selected topic also shows In Progress or Not Started.">ⓘ</button>
                            </h3>
                            <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="1">—</p>
                        </article>
                        <article class="module-activity-kpi">
                            <h3 class="module-activity-kpi-label">
                                <span data-kpi-label="2">Average Quiz Score</span>
                                <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                        aria-label="About quiz score" title="Shows the average across quiz attempts. For a selected topic, the card shows both the best and average score.">ⓘ</button>
                            </h3>
                            <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="2">—</p>
                        </article>
                        <article class="module-activity-kpi">
                            <h3 class="module-activity-kpi-label">
                                <span data-kpi-label="3">Last Active</span>
                                <button type="button" class="module-info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                        aria-label="About last active" title="Most recent review session or quiz attempt. For a selected topic, the card also shows its quiz-attempt count.">ⓘ</button>
                            </h3>
                            <p class="module-activity-kpi-value module-tabular mb-0" data-kpi-value="3">—</p>
                        </article>
                    </div>
                    <div class="mb-3">
                        <h3 class="h6 fw-bold mb-1" id="moduleChartStageTitle">Monthly Active Study Time</h3>
                        <p class="small text-secondary mb-0" id="moduleChartStageHint">Select a month to view its daily activity.</p>
                    </div>
                    <nav class="module-activity-tabs" id="moduleActivityDrilldownTabs" role="tablist" aria-label="Student activity drill-down">
                        <button type="button" class="module-activity-tab is-active" role="tab" id="moduleMonthsTab" aria-selected="true" aria-controls="moduleActivityChartPanel" data-activity-stage="months">Months</button>
                        <button type="button" class="module-activity-tab" role="tab" id="moduleDaysTab" aria-selected="false" aria-controls="moduleActivityChartPanel" data-activity-stage="days" disabled tabindex="-1">Daily activity</button>
                        <button type="button" class="module-activity-tab" role="tab" id="moduleTopicsTab" aria-selected="false" aria-controls="moduleActivityChartPanel" data-activity-stage="topics" disabled tabindex="-1">Topics</button>
                    </nav>
                    <div class="module-chart-card mb-4">
                        <div id="moduleActivityChartPanel" role="tabpanel" aria-labelledby="moduleMonthsTab" tabindex="0">
                            <p class="module-chart-unit-label" id="moduleChartUnitLabel">Minutes (min)</p>
                            <div class="module-chart-layout">
                                <div class="module-chart-y-axis" id="moduleChartYAxis" aria-label="Chart scale"></div>
                                <div class="module-chart-stage">
                                    <div id="moduleMonthlyActivityChart" class="module-chart-bars module-chart-view is-active" role="group" aria-label="Monthly active study time chart. Select a month to view daily activity."></div>
                                    <div id="moduleActivityCalendar" class="module-activity-calendar module-chart-view" aria-label="Daily study activity calendar" hidden></div>
                                    <div id="moduleTopicsActivityChart" class="module-chart-bars module-chart-view" role="img" aria-label="Reviewed time by topic chart" hidden></div>
                                </div>
                            </div>
                            <div id="moduleCalendarLegend" class="module-calendar-legend" role="group"
                                 aria-label="Daily study time legend. Red means less study time; green means more." hidden>
                                <span>No activity</span>
                                <span class="module-calendar-legend-swatch module-calendar-legend-none" aria-label="No activity"></span>
                                <span>Low time</span>
                                <span class="module-calendar-legend-swatch" data-level="1" title="Under 10 minutes" aria-label="Under 10 minutes"></span>
                                <span class="module-calendar-legend-swatch" data-level="2" title="10 to 29 minutes" aria-label="10 to 29 minutes"></span>
                                <span class="module-calendar-legend-swatch" data-level="3" title="30 to 59 minutes" aria-label="30 to 59 minutes"></span>
                                <span class="module-calendar-legend-swatch" data-level="4" title="60 minutes or more" aria-label="60 minutes or more"></span>
                                <span>Most time</span>
                            </div>
                            <p class="small text-secondary mt-2 mb-0" id="moduleCalendarLabel" aria-live="polite"></p>
                        </div>
                    </div>
                    <section class="mb-4" id="moduleTopicBreakdown">
                        <h3 class="h6 fw-bold mb-3">Topic-by-Topic Breakdown</h3>
                        <div class="table-responsive">
                            <table class="table module-review-table module-topic-breakdown">
                                <thead>
                                    <tr><th>Topic</th><th>Verified Time</th><th>Quiz Attempts</th><th>Best Score</th><th>Status</th></tr>
                                </thead>
                                <tbody id="moduleTopicBreakdownRows">
                                    <tr><td class="py-4 text-center text-secondary" colspan="5">Loading topic progress…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <section class="mb-4" id="moduleTopicAttemptHistory" hidden>
                        <h3 class="h6 fw-bold mb-3">Topic Quiz Attempt History</h3>
                        <div class="table-responsive">
                            <table class="table module-review-table module-attempt-history">
                                <thead><tr><th>Attempt #</th><th>Score</th><th>Points Earned</th><th>Date / Time</th></tr></thead>
                                <tbody id="moduleTopicAttemptRows"></tbody>
                            </table>
                        </div>
                    </section>
                    <p class="module-chart-error mt-3 mb-0" id="moduleActivityError" role="alert" hidden></p>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-danger rounded-pill px-4" id="moduleActivitySendNotice">Send Notice</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moduleStudentNoticeModal" tabindex="-1" aria-labelledby="moduleStudentNoticeTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h2 class="h5 fw-bold mb-1" id="moduleStudentNoticeTitle">Send Student Notice</h2>
                        <p class="small text-secondary mb-0">To <span class="fw-semibold" id="moduleNoticeStudentName"></span> · {{ $module->title }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close notice"></button>
                </div>
                <form id="moduleStudentNoticeForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Notice presets">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-notice-preset="Great study progress!">Great study progress!</button>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-notice-preset="Reminder to review topics">Reminder to review topics</button>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-notice-preset="Please catch up on quizzes">Please catch up on quizzes</button>
                        </div>
                        <label class="form-label fw-semibold" for="moduleNoticeMessage">Notice</label>
                        <textarea class="form-control" id="moduleNoticeMessage" name="message" rows="4" maxlength="2000" placeholder="Write a one-way notice..." required></textarea>
                        <div class="form-text">The student can read this notice but cannot reply.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Send Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#moduleStudentActivityModal [data-bs-toggle="tooltip"]').forEach((element) => {
        bootstrap.Tooltip.getOrCreateInstance(element, {
            container: 'body',
            customClass: 'module-info-tooltip-popup',
        });
    });

    const tabBar = document.getElementById('moduleDetailTabs');
    const tabs = Array.from(document.querySelectorAll('[data-review-tab]'));
    const tabIndicator = tabBar.querySelector('.module-tab-indicator');
    const panels = Array.from(document.querySelectorAll('[data-review-panel]'));

    function positionTabIndicator(tab) {
        tabIndicator.style.width = `${tab.offsetWidth}px`;
        tabIndicator.style.transform = `translateX(${tab.offsetLeft}px)`;
    }

    function activateTab(tab, moveFocus = false) {
        tabs.forEach((item) => {
            const isActive = item === tab;
            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-selected', String(isActive));
            item.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
            const isActive = panel.dataset.reviewPanel === tab.dataset.reviewTab;
            if (isActive) {
                panel.hidden = false;
                requestAnimationFrame(() => panel.classList.add('is-active'));
            } else {
                panel.classList.remove('is-active');
                window.setTimeout(() => {
                    if (!panel.classList.contains('is-active')) {
                        panel.hidden = true;
                    }
                }, 210);
            }
        });

        positionTabIndicator(tab);
        if (moveFocus) {
            tab.focus();
        }
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab));
        tab.addEventListener('keydown', (event) => {
            let targetIndex = null;
            if (event.key === 'ArrowRight') {
                targetIndex = (index + 1) % tabs.length;
            } else if (event.key === 'ArrowLeft') {
                targetIndex = (index - 1 + tabs.length) % tabs.length;
            } else if (event.key === 'Home') {
                targetIndex = 0;
            } else if (event.key === 'End') {
                targetIndex = tabs.length - 1;
            }

            if (targetIndex !== null) {
                event.preventDefault();
                activateTab(tabs[targetIndex], true);
            }
        });
    });
    positionTabIndicator(tabs[0]);
    window.addEventListener('resize', () => positionTabIndicator(tabs.find((tab) => tab.classList.contains('is-active'))));

    const leaderboardRows = Array.from(document.querySelectorAll('[data-leaderboard-row]'));
    const leaderboardTopicFilter = document.getElementById('moduleLeaderboardTopicFilter');
    const studentRows = Array.from(document.querySelectorAll('[data-review-student]'));
    const studentSearch = document.getElementById('moduleStudentSearch');
    const studentTopicFilter = document.getElementById('moduleStudentTopicFilter');
    const studentSort = document.getElementById('moduleStudentSort');
    const studentRowsContainer = document.getElementById('moduleStudentRows');
    const leaderboardRowsContainer = document.getElementById('moduleLeaderboardRows');
    const viewAllLeaderboard = document.getElementById('viewAllLeaderboard');
    const viewAllStudents = document.getElementById('viewAllModuleStudents');
    const studentTopicCompletionHeader = document.querySelector('[data-topic-completion-header]');
    const studentEmptyStateCell = studentRowsContainer.querySelector('[data-student-empty-state]');
    let showAllLeaderboard = false;
    let showAllStudents = false;

    function parseTopicStats(row) {
        return JSON.parse(row.dataset.topicStats || '{}');
    }

    function getStudentStats(row, topicId) {
        const allTopics = Object.values(parseTopicStats(row));
        const topics = topicId === 'all'
            ? allTopics
            : allTopics.filter((topic) => String(topic.id) === topicId);
        const stats = topics.reduce((summary, topic) => {
            summary.points += Number(topic.points) || 0;
            summary.scorePercentageSum += Number(topic.score_percentage_sum) || 0;
            summary.quizAttempts += Number(topic.quiz_attempts) || 0;
            summary.verifiedSeconds += Number(topic.verified_seconds) || 0;
            summary.topicsCompleted += topic.completed ? 1 : 0;
            if (topic.last_active_at && (!summary.lastActiveAt || topic.last_active_at > summary.lastActiveAt)) {
                summary.lastActiveAt = topic.last_active_at;
            }
            return summary;
        }, {
            points: 0,
            scorePercentageSum: 0,
            quizAttempts: 0,
            verifiedSeconds: 0,
            topicsCompleted: 0,
            lastActiveAt: null,
        });
        stats.averageScore = stats.quizAttempts > 0 ? stats.scorePercentageSum / stats.quizAttempts : null;
        stats.topicCount = topics.length;
        stats.status = topics.length === 0 ? 'Not Started' : topics[0].status;
        stats.completed = stats.topicCount > 0 && stats.topicsCompleted === stats.topicCount;

        return stats;
    }

    function formatDuration(seconds) {
        const totalSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
        const minutes = Math.floor(totalSeconds / 60);
        if (minutes === 0 && totalSeconds > 0) {
            return `${totalSeconds}s`;
        }
        return minutes >= 60 ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : `${minutes}m`;
    }

    function formatDate(dateValue) {
        if (!dateValue) {
            return 'Never';
        }
        const date = new Date(dateValue);
        return Number.isNaN(date.getTime())
            ? 'Never'
            : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
    }

    function formatLastActive(dateValue) {
        if (!dateValue) {
            return 'Never';
        }
        const activeAt = new Date(dateValue);
        if (Number.isNaN(activeAt.getTime())) {
            return 'Never';
        }
        const elapsedMilliseconds = Math.max(0, Date.now() - activeAt.getTime());
        const elapsedHours = Math.floor(elapsedMilliseconds / 3600000);
        if (elapsedHours < 1) {
            return `${Math.max(1, Math.floor(elapsedMilliseconds / 60000))}m ago`;
        }
        if (elapsedHours < 24) {
            return `${elapsedHours}h ago`;
        }
        return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(activeAt);
    }

    function setActivityKpi(index, label, value) {
        activityKpiLabels.find((element) => element.dataset.kpiLabel === String(index)).textContent = label;
        activityKpiValues.find((element) => element.dataset.kpiValue === String(index)).textContent = value;
    }

    function applyStatusPill(pill, status) {
        pill.className = `module-status-pill module-status-pill--${status.toLocaleLowerCase().replaceAll(' ', '-')}`;
        pill.textContent = status;
    }

    function applyRank(row, rank) {
        row.dataset.rank = String(rank);
        const badge = row.querySelector('[data-rank-badge]');
        badge.textContent = String(rank);
    }

    function updateLeaderboard() {
        const topicId = leaderboardTopicFilter.value;
        const rankedRows = leaderboardRows.map((row) => ({
            row,
            stats: getStudentStats(row, topicId),
        })).sort((first, second) => second.stats.points - first.stats.points
            || first.row.dataset.name.localeCompare(second.row.dataset.name));

        rankedRows.forEach(({ row, stats }, index) => {
            const rank = index + 1;
            applyRank(row, rank);
            row.querySelector('[data-average-score]').textContent = stats.averageScore === null
                ? 'No attempts'
                : `${stats.averageScore.toFixed(2)}%`;
            row.querySelector('[data-total-points]').textContent = `${stats.points.toFixed(2)} pts`;
            row.hidden = !showAllLeaderboard && rank > 15;
            leaderboardRowsContainer.appendChild(row);
        });

        if (viewAllLeaderboard) {
            viewAllLeaderboard.parentElement.hidden = rankedRows.length <= 15;
            viewAllLeaderboard.setAttribute('aria-expanded', String(showAllLeaderboard));
            viewAllLeaderboard.textContent = showAllLeaderboard ? 'Show Top 15' : 'View All';
        }
    }

    function getStudentStatus(stats) {
        const lastActive = stats.lastActiveAt ? new Date(stats.lastActiveAt) : null;
        if (!lastActive || Number.isNaN(lastActive.getTime()) || Date.now() - lastActive.getTime() > 14 * 86400000) {
            return 'Inactive';
        }
        return stats.completed ? 'On Track' : 'Needs Review';
    }

    function updateStudents() {
        const topicId = studentTopicFilter.value;
        const searchTerm = studentSearch.value.trim().toLocaleLowerCase();
        const showTopicCompletion = topicId === 'all';
        studentTopicCompletionHeader.hidden = !showTopicCompletion;
        if (studentEmptyStateCell) {
            studentEmptyStateCell.colSpan = showTopicCompletion ? 8 : 7;
        }

        const rankedRows = studentRows.map((row) => ({
            row,
            stats: getStudentStats(row, topicId),
        })).sort((first, second) => second.stats.verifiedSeconds - first.stats.verifiedSeconds
            || first.row.dataset.studentName.localeCompare(second.row.dataset.studentName));
        rankedRows.forEach(({ row }, index) => applyRank(row, index + 1));

        const visibleOrder = studentSort.value === 'name'
            ? [...rankedRows].sort((first, second) => first.row.dataset.studentName.localeCompare(second.row.dataset.studentName))
            : rankedRows;

        visibleOrder.forEach(({ row, stats }, index) => {
            const status = getStudentStatus(stats);
            row.querySelector('[data-verified-time]').textContent = formatDuration(stats.verifiedSeconds);
            const completion = row.querySelector('[data-topic-completed]');
            completion.hidden = !showTopicCompletion;
            completion.textContent = `${stats.topicsCompleted} / ${stats.topicCount} Topics`;
            row.querySelector('[data-quiz-attempts]').textContent = String(stats.quizAttempts);
            row.querySelector('[data-last-active]').textContent = formatDate(stats.lastActiveAt);
            const statusBadge = row.querySelector('[data-status-pill]');
            statusBadge.className = `badge ${status === 'On Track' ? 'text-bg-success' : (status === 'Needs Review' ? 'text-bg-warning' : 'text-bg-secondary')}`;
            statusBadge.textContent = status;
            studentRowsContainer.appendChild(row);
            row.hidden = !row.dataset.name.includes(searchTerm) || (!searchTerm && !showAllStudents && index >= 15);
        });

        if (viewAllStudents) {
            viewAllStudents.parentElement.hidden = searchTerm.length > 0 || rankedRows.length <= 15;
            viewAllStudents.setAttribute('aria-expanded', String(showAllStudents));
            viewAllStudents.textContent = showAllStudents ? 'Show Top 15' : 'View All';
        }
    }

    leaderboardTopicFilter.addEventListener('change', () => {
        showAllLeaderboard = false;
        updateLeaderboard();
    });
    if (viewAllLeaderboard) {
        viewAllLeaderboard.addEventListener('click', () => {
            showAllLeaderboard = !showAllLeaderboard;
            updateLeaderboard();
        });
    }
    studentSearch.addEventListener('input', updateStudents);
    studentTopicFilter.addEventListener('change', () => {
        showAllStudents = false;
        updateStudents();
    });
    studentSort.addEventListener('change', updateStudents);
    if (viewAllStudents) {
        viewAllStudents.addEventListener('click', () => {
            showAllStudents = !showAllStudents;
            updateStudents();
        });
    }

    const activityModalElement = document.getElementById('moduleStudentActivityModal');
    const activityModal = bootstrap.Modal.getOrCreateInstance(activityModalElement);
    const noticeModalElement = document.getElementById('moduleStudentNoticeModal');
    const noticeModal = bootstrap.Modal.getOrCreateInstance(noticeModalElement);
    const activityCourseTitle = activityModalElement.dataset.courseTitle;
    const activityTopicFilter = document.getElementById('moduleActivityTopicFilter');
    const activityError = document.getElementById('moduleActivityError');
    const activityContext = document.getElementById('moduleActivityContext');
    const activityStatus = document.getElementById('moduleActivityStatus');
    const activityTabs = Array.from(document.querySelectorAll('[data-activity-stage]'));
    const activityChartPanel = document.getElementById('moduleActivityChartPanel');
    const activityChartLayout = document.querySelector('.module-chart-layout');
    const chartStageTitle = document.getElementById('moduleChartStageTitle');
    const chartStageHint = document.getElementById('moduleChartStageHint');
    const chartUnitLabel = document.getElementById('moduleChartUnitLabel');
    const chartYAxis = document.getElementById('moduleChartYAxis');
    const monthlyChart = document.getElementById('moduleMonthlyActivityChart');
    const activityCalendar = document.getElementById('moduleActivityCalendar');
    const calendarLegend = document.getElementById('moduleCalendarLegend');
    const calendarLabel = document.getElementById('moduleCalendarLabel');
    const topicsChart = document.getElementById('moduleTopicsActivityChart');
    const activityKpiLabels = Array.from(document.querySelectorAll('[data-kpi-label]'));
    const activityKpiValues = Array.from(document.querySelectorAll('[data-kpi-value]'));
    const activityKpiProgress = document.querySelector('[data-kpi-progress]');
    const activityKpiProgressBar = document.querySelector('[data-kpi-progress-bar]');
    const topicBreakdown = document.getElementById('moduleTopicBreakdown');
    const topicRows = document.getElementById('moduleTopicBreakdownRows');
    const attemptHistory = document.getElementById('moduleTopicAttemptHistory');
    const attemptRows = document.getElementById('moduleTopicAttemptRows');
    let activeStudentRow = null;
    let selectedMonth = null;
    let selectedDate = null;
    let overviewRequestId = 0;
    let activityRequestId = 0;
    let selectedActivityTopicId = 'all';
    const activityChartScales = { months: null, topics: null };

    function openNotice(row, topicId) {
        const topic = Object.values(parseTopicStats(row)).find((item) => String(item.id) === topicId);
        const message = topicId !== 'all' && topic
            ? (topic.status === 'Completed'
                ? `Great work completing ${topic.title}! Keep up the excellent study progress.`
                : `Please review ${topic.title} and complete its topic quiz.`)
            : 'Reminder to review your module topics and complete the related quizzes.';
        document.getElementById('moduleNoticeStudentName').textContent = row.dataset.studentName;
        document.getElementById('moduleStudentNoticeForm').action = row.dataset.noticeUrl;
        document.getElementById('moduleNoticeMessage').value = message;
        noticeModal.show();
    }

    function renderTopicBreakdown(data) {
        topicRows.replaceChildren();
        const topics = data.topics || [];
        if (topics.length === 0) {
            topicRows.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-secondary">No topics have been added to this module yet.</td></tr>';
            return;
        }

        topics.forEach((topic) => {
            const row = document.createElement('tr');
            const values = [
                topic.title,
                `${formatDuration(topic.verified_seconds)} / 30m`,
                String(topic.quiz_attempts),
                topic.best_score === null ? 'No attempts' : `${Number(topic.best_score).toFixed(2)}%`,
            ];
            values.forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.appendChild(cell);
            });
            const statusCell = document.createElement('td');
            const statusPill = document.createElement('span');
            applyStatusPill(statusPill, topic.status);
            statusCell.appendChild(statusPill);
            row.appendChild(statusCell);
            topicRows.appendChild(row);
        });
    }

    function renderAttempts(attempts) {
        attemptRows.replaceChildren();
        if (attempts.length === 0) {
            attemptRows.innerHTML = '<tr><td colspan="4" class="text-secondary">No quiz attempts have been recorded for this topic.</td></tr>';
            return;
        }
        attempts.forEach((attempt) => {
            const row = document.createElement('tr');
            [
                String(attempt.attempt),
                `${Number(attempt.score).toFixed(2)}%`,
                `${Number(attempt.points).toFixed(2)} pts`,
                new Intl.DateTimeFormat(undefined, {
                    month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
                }).format(new Date(attempt.created_at)),
            ].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.appendChild(cell);
            });
            attemptRows.appendChild(row);
        });
    }

    function renderOverview(overview) {
        const topicId = activityTopicFilter.value;
        const topic = overview.selected_topic;
        activityContext.textContent = topicId === 'all'
            ? `${overview.student.name} · ${activityCourseTitle} (All Topics)`
            : `${overview.student.name} · ${activityCourseTitle} → ${topic.title}`;
        applyStatusPill(activityStatus, overview.status);
        activityKpiProgress.hidden = topicId === 'all';
        if (topicId === 'all') {
            setActivityKpi(0, 'Verified Study Time', formatDuration(overview.verified_seconds));
            setActivityKpi(1, 'Topics Completed', `${overview.topics_completed} / ${overview.topic_count} Topics`);
            setActivityKpi(2, 'Average Quiz Score', overview.average_score === null ? 'No attempts' : `${Number(overview.average_score).toFixed(2)}%`);
            setActivityKpi(3, 'Last Active', formatLastActive(overview.last_active_at));
            topicBreakdown.hidden = false;
            attemptHistory.hidden = true;
            renderTopicBreakdown(overview);
            return;
        }

        setActivityKpi(0, 'Topic Study Time', `${formatDuration(topic.verified_seconds)} / 30m cap`);
        setActivityKpi(1, 'Topic Status', topic.status);
        setActivityKpi(2, 'Quiz Score', topic.quiz_attempts === 0
            ? 'No attempts'
            : `Best: ${Number(topic.best_score).toFixed(2)}% · Avg: ${Number(topic.average_score).toFixed(2)}%`);
        setActivityKpi(3, 'Attempts & Last Active', `${topic.quiz_attempts} · ${formatLastActive(topic.last_active_at)}`);
        activityKpiProgressBar.style.width = `${Math.min(100, (topic.verified_seconds / 1800) * 100)}%`;
        activityKpiProgressBar.setAttribute('aria-valuenow', String(Math.min(1800, topic.verified_seconds)));
        activityKpiProgressBar.setAttribute('aria-valuemax', '1800');
        topicBreakdown.hidden = true;
        attemptHistory.hidden = false;
        if (topic) {
            renderAttempts(overview.attempts);
        }
    }

    function setActivityStage(stage) {
        if (
            (stage === 'days' && !selectedMonth)
            || (stage === 'topics' && (!selectedDate || selectedActivityTopicId !== 'all'))
        ) {
            return;
        }
        const stageViews = {
            months: monthlyChart,
            days: activityCalendar,
            topics: topicsChart,
        };
        const activeTab = activityTabs.find((tab) => tab.dataset.activityStage === stage);
        activityTabs.find((tab) => tab.dataset.activityStage === 'topics').hidden = selectedActivityTopicId !== 'all';
        activityTabs.forEach((tab) => {
            const isActive = tab === activeTab;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
            tab.disabled = tab.dataset.activityStage === 'days'
                ? !selectedMonth
                : tab.dataset.activityStage === 'topics'
                    && (!selectedDate || selectedActivityTopicId !== 'all');
        });
        Object.entries(stageViews).forEach(([viewStage, view]) => {
            const isActive = viewStage === stage;
            if (isActive) {
                view.hidden = false;
                requestAnimationFrame(() => view.classList.add('is-active'));
            } else {
                view.classList.remove('is-active');
                view.hidden = true;
            }
        });
        const isCalendar = stage === 'days';
        activityChartLayout.classList.toggle('is-calendar', isCalendar);
        calendarLegend.hidden = !isCalendar;
        const stageScale = activityChartScales[stage];
        chartYAxis.hidden = isCalendar || !stageScale;
        chartUnitLabel.hidden = isCalendar || !stageScale;
        calendarLabel.hidden = stage === 'months';
        if (stageScale) {
            renderYAxis(stageScale);
        }
        activityChartPanel.setAttribute('aria-labelledby', activeTab.id);
        const stageContent = {
            months: ['Monthly Active Study Time', 'Select a month to view its daily activity.'],
            days: [`Daily Activity · ${calendarLabel.textContent}`, 'Select a date to view its topic breakdown.'],
            topics: ['Topics Breakdown', `Verified review time on ${selectedDate}.`],
        };
        chartStageTitle.textContent = stageContent[stage][0];
        chartStageHint.textContent = stageContent[stage][1];
    }

    function getChartScale(records, secondsForRecord) {
        const maximumSeconds = Math.max(...records.map(secondsForRecord), 0);
        const isHours = maximumSeconds >= 3600;
        const unitSeconds = isHours ? 3600 : 60;
        const maximumValue = maximumSeconds / unitSeconds;
        const step = isHours
            ? (maximumValue <= 2 ? 0.5 : maximumValue <= 5 ? 1 : 2)
            : maximumValue <= 30 ? 5 : 10;
        const scaleMaximum = Math.max(isHours ? step * 2 : 10, Math.ceil(maximumValue / step) * step);
        const tickCount = Math.round(scaleMaximum / step);

        return {
            unitSeconds,
            scaleMaximum,
            ticks: Array.from({ length: tickCount + 1 }, (_, index) => Number((scaleMaximum - index * step).toFixed(2))),
            unitLabel: isHours ? 'Hours (h)' : 'Minutes (min)',
        };
    }

    function renderYAxis(scale) {
        chartUnitLabel.textContent = scale.unitLabel;
        chartYAxis.replaceChildren();
        scale.ticks.forEach((tick) => {
            const label = document.createElement('span');
            label.textContent = String(tick);
            chartYAxis.appendChild(label);
        });
    }

    function renderBars(container, records, nameForRecord, secondsForRecord, onSelectRecord = null, selectedKey = null, keyForRecord = null) {
        container.replaceChildren();
        const scale = getChartScale(records, secondsForRecord);
        records.forEach((record, index) => {
            const seconds = Number(secondsForRecord(record)) || 0;
            const bar = document.createElement(onSelectRecord ? 'button' : 'div');
            bar.className = onSelectRecord ? 'module-chart-bar module-chart-bar--interactive' : 'module-chart-bar';
            if (onSelectRecord) {
                bar.type = 'button';
                const isSelected = keyForRecord(record) === selectedKey;
                bar.classList.toggle('is-selected', isSelected);
                bar.setAttribute('aria-pressed', String(isSelected));
                bar.setAttribute('aria-label', `${nameForRecord(record)}: ${formatDuration(seconds)} reviewed. Select month.`);
                bar.addEventListener('click', () => onSelectRecord(record));
            }
            const value = document.createElement('span');
            value.className = 'module-chart-value';
            value.textContent = formatDuration(seconds);
            const plot = document.createElement('div');
            plot.className = 'module-chart-plot';
            scale.ticks.slice(1, -1).forEach((_, tickIndex) => {
                const gridline = document.createElement('span');
                gridline.className = 'module-chart-gridline';
                gridline.style.top = `${((tickIndex + 1) / (scale.ticks.length - 1)) * 100}%`;
                plot.appendChild(gridline);
            });
            const fill = document.createElement('div');
            fill.className = 'module-chart-fill';
            fill.style.height = `${Math.max(0, (seconds / scale.unitSeconds / scale.scaleMaximum) * 100)}%`;
            fill.style.transitionDelay = `${index * 40}ms`;
            fill.title = `${nameForRecord(record)}: ${formatDuration(seconds)}`;
            fill.setAttribute('aria-label', fill.title);
            plot.appendChild(fill);
            const label = document.createElement('span');
            label.className = 'module-chart-label module-tabular';
            label.textContent = nameForRecord(record);
            bar.append(value, plot, label);
            container.appendChild(bar);
            requestAnimationFrame(() => requestAnimationFrame(() => fill.classList.add('is-animated')));
        });
        if (records.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'small text-secondary m-auto';
            empty.textContent = 'No verified study time recorded yet.';
            container.appendChild(empty);
        }
        return scale;
    }

    function renderCalendar(data, selectedDateValue, onSelectDate) {
        activityCalendar.replaceChildren();
        ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach((weekday) => {
            const label = document.createElement('span');
            label.className = 'module-chart-label fw-bold mt-0';
            label.textContent = weekday;
            activityCalendar.appendChild(label);
        });
        for (let index = 0; index < data.first_weekday; index += 1) {
            const spacer = document.createElement('span');
            spacer.className = 'module-calendar-cell module-calendar-cell--empty';
            spacer.setAttribute('aria-hidden', 'true');
            activityCalendar.appendChild(spacer);
        }
        data.days.forEach((day) => {
            const cell = document.createElement('button');
            const activeSeconds = Number(day.active_seconds) || 0;
            const level = activeSeconds === 0 ? 0 : activeSeconds < 600 ? 1 : activeSeconds < 1800 ? 2 : activeSeconds < 3600 ? 3 : 4;
            cell.type = 'button';
            cell.className = `module-calendar-cell module-tabular${day.date === selectedDateValue ? ' module-calendar-cell--selected' : ''}`;
            cell.dataset.level = String(level);
            cell.textContent = String(day.day);
            cell.title = `${day.date}: ${formatDuration(activeSeconds)} reviewed`;
            cell.setAttribute('aria-label', `${day.date}: ${formatDuration(activeSeconds)} reviewed. View topic breakdown.`);
            cell.setAttribute('aria-pressed', String(day.date === selectedDateValue));
            cell.addEventListener('click', () => onSelectDate(day.date));
            activityCalendar.appendChild(cell);
        });
        calendarLabel.textContent = data.label;
    }

    function selectActivityMonth(month) {
        selectedMonth = month;
        selectedDate = null;
        activityChartScales.topics = null;
        activityCalendar.replaceChildren();
        const [year, monthNumber] = month.split('-').map(Number);
        calendarLabel.textContent = new Date(year, monthNumber - 1, 1)
            .toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        topicsChart.replaceChildren();
        setActivityStage('days');
        loadActivity(month);
    }

    function selectActivityDate(date) {
        selectedDate = date;
        if (selectedActivityTopicId === 'all') {
            activityChartScales.topics = null;
            topicsChart.replaceChildren();
            setActivityStage('topics');
        } else {
            setActivityStage('days');
        }
        loadActivity(selectedMonth, date);
    }

    function renderActivity(data, month, date) {
        activityChartScales.months = renderBars(
            monthlyChart,
            data.months,
            (record) => record.label,
            (record) => record.active_seconds,
            (record) => selectActivityMonth(record.month),
            month === 'all' ? null : month,
            (record) => record.month,
        );
        if (month === 'all') {
            activityChartScales.topics = null;
            setActivityStage('months');
            return;
        }
        renderCalendar(data.calendar, date, selectActivityDate);
        if (date && selectedActivityTopicId === 'all') {
            activityChartScales.topics = renderBars(
                topicsChart,
                data.topics,
                (topic) => topic.title,
                (topic) => topic.active_seconds,
            );
            setActivityStage('topics');
            return;
        }
        activityChartScales.topics = null;
        topicsChart.replaceChildren();
        setActivityStage('days');
    }

    async function loadOverview() {
        if (!activeStudentRow) {
            return;
        }
        const requestId = ++overviewRequestId;
        const url = new URL(activeStudentRow.dataset.overviewUrl, window.location.origin);
        if (activityTopicFilter.value !== 'all') {
            url.searchParams.set('subtopic_id', activityTopicFilter.value);
        }
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error('Student module progress could not be loaded.');
            }
            const data = await response.json();
            if (requestId === overviewRequestId && activeStudentRow) {
                renderOverview(data);
            }
        } catch (error) {
            if (requestId === overviewRequestId) {
                activityError.textContent = error.message;
                activityError.hidden = false;
            }
        }
    }

    async function loadActivity(month = 'all', date = null) {
        if (!activeStudentRow) {
            return;
        }
        const requestId = ++activityRequestId;
        activityError.hidden = true;
        if (month === 'all') {
            monthlyChart.replaceChildren();
            activityCalendar.replaceChildren();
            topicsChart.replaceChildren();
            activityChartScales.months = null;
            activityChartScales.topics = null;
            selectedMonth = null;
            selectedDate = null;
            setActivityStage('months');
        }
        const url = new URL(activeStudentRow.dataset.activityUrl, window.location.origin);
        if (month !== 'all') {
            url.searchParams.set('month', month);
        }
        if (date) {
            url.searchParams.set('date', date);
        }
        if (selectedActivityTopicId !== 'all') {
            url.searchParams.set('subtopic_id', selectedActivityTopicId);
        }
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error('Student study activity could not be loaded.');
            }
            const data = await response.json();
            if (requestId !== activityRequestId || !activeStudentRow) {
                return;
            }
            selectedMonth = month === 'all' ? null : month;
            selectedDate = date;
            renderActivity(data, month, date);
        } catch (error) {
            if (requestId === activityRequestId) {
                activityError.textContent = error.message;
                activityError.hidden = false;
            }
        }
    }

    async function openStudentActivity(row, topicId) {
        activeStudentRow = row;
        activityTopicFilter.value = topicId;
        selectedActivityTopicId = topicId;
        selectedMonth = null;
        selectedDate = null;
        activityError.hidden = true;
        topicRows.innerHTML = '<tr><td class="py-4 text-center text-secondary" colspan="5">Loading topic progress…</td></tr>';
        monthlyChart.replaceChildren();
        activityCalendar.replaceChildren();
        topicsChart.replaceChildren();
        activityChartScales.months = null;
        activityChartScales.topics = null;
        setActivityStage('months');
        topicBreakdown.hidden = topicId !== 'all';
        attemptHistory.hidden = topicId === 'all';
        activityModal.show();
        loadOverview();
        loadActivity('all');
    }

    studentRowsContainer.addEventListener('click', (event) => {
        const row = event.target.closest('[data-review-student]');
        if (!row) {
            return;
        }
        if (event.target.closest('[data-student-notice]')) {
            event.stopPropagation();
            openNotice(row, studentTopicFilter.value);
            return;
        }
        openStudentActivity(row, studentTopicFilter.value);
    });
    studentRowsContainer.addEventListener('keydown', (event) => {
        const row = event.target.closest('[data-review-student]');
        if (row && event.target === row && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            openStudentActivity(row, studentTopicFilter.value);
        }
    });
    document.getElementById('moduleActivitySendNotice').addEventListener('click', () => {
        if (!activeStudentRow) {
            return;
        }
        const row = activeStudentRow;
        const topicId = activityTopicFilter.value;
        activityModalElement.addEventListener('hidden.bs.modal', function openNoticeAfterActivity() {
            activityModalElement.removeEventListener('hidden.bs.modal', openNoticeAfterActivity);
            openNotice(row, topicId);
        });
        activityModal.hide();
    });
    noticeModalElement.querySelectorAll('[data-notice-preset]').forEach((preset) => {
        preset.addEventListener('click', () => {
            const textarea = document.getElementById('moduleNoticeMessage');
            textarea.value = preset.dataset.noticePreset;
            textarea.focus();
        });
    });
    activityModalElement.addEventListener('hidden.bs.modal', () => {
        activeStudentRow = null;
        overviewRequestId += 1;
        activityRequestId += 1;
        selectedMonth = null;
        selectedDate = null;
        selectedActivityTopicId = 'all';
        monthlyChart.replaceChildren();
        activityCalendar.replaceChildren();
        topicsChart.replaceChildren();
        activityChartScales.months = null;
        activityChartScales.topics = null;
        topicBreakdown.hidden = false;
        attemptHistory.hidden = true;
        setActivityStage('months');
    });

    updateLeaderboard();
    updateStudents();
});
</script>
@endsection
