@include('partials.student-activity-styles')

<div class="student-activity-scope">
    <section class="activity-card p-3 p-md-4 mb-4" id="student-activity-analytics"
             data-analytics-url="{{ $analyticsUrl }}"
             data-topic-quizzes-url="{{ $topicQuizzesUrl }}"
             aria-labelledby="activity-analytics-title">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3">
                <div>
                    <h2 id="activity-analytics-title" class="h4 fw-bold mb-1">Study Activity</h2>
                    <p class="activity-muted small mb-0">Active reading time, from the student’s registration month through this month.</p>
                </div>
                <span id="activity-total" class="badge activity-total rounded-pill px-3 py-2">Loading activity…</span>
            </div>

            <nav id="activity-breadcrumbs" class="activity-breadcrumbs mb-3" aria-label="Activity analytics navigation"></nav>

            <div class="activity-panel">
                <h3 id="activity-view-title" class="h6 fw-bold mb-3">Monthly active study time</h3>
                <div id="activity-view" aria-live="polite">
                    <p class="activity-muted small mb-0">Loading study activity…</p>
                </div>
                <p id="activity-error" class="text-danger small mt-3 mb-0" role="alert" hidden></p>
            </div>

    </section>
</div>

<div class="modal fade student-activity-scope" id="activity-topic-quizzes-modal" tabindex="-1" aria-labelledby="activity-topic-quizzes-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="activity-topic-quizzes-title">Recent topic quizzes</h2>
                    <p class="activity-muted small mb-0">The five most recent quiz attempts for this topic.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="activity-topic-quizzes-list" class="d-grid gap-2" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>
