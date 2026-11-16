<section class="{{ $performanceCardClass }}" aria-labelledby="general-test-performance-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3">
        <div>
            <h3 id="general-test-performance-title" class="h5 fw-bold text-slate-900 mb-1">{{ $performanceTitle }}</h3>
            <p class="text-secondary small mb-0">{{ $performanceAudience }} general-test scores for a selected date range of up to 7 days. Review quizzes are excluded.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap"
                data-bs-toggle="modal" data-bs-target="#generalTestDateRangeModal"
                aria-haspopup="dialog" aria-controls="generalTestDateRangeModal" aria-label="Change analytics date range">
            <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
            {{ \Carbon\CarbonImmutable::parse($periodStartDate)->format('M j') }} – {{ \Carbon\CarbonImmutable::parse($periodEndDate)->format('M j') }}
            <span class="ms-1">Change</span>
        </button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-4">
            <article class="analytics-summary-card p-3 h-100">
                <div class="small fw-bold text-secondary text-uppercase">Attempts scoring 75%+</div>
                <div class="analytics-summary-value">{{ $studentAnalytics['passing_attempts'] }}</div>
                <div class="small text-secondary">Selected date range</div>
            </article>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h4 class="h6 fw-bold mb-0">Score by test day</h4>
        <span class="badge rounded-pill bg-light text-secondary border px-3 py-2">75% passing target</span>
    </div>

    @php
        $scoreTrendDays = $studentPerformance->values();
        $performanceChartWidth = max(720, $scoreTrendDays->count() * 112 + 120);
        $performanceChartLeft = 58;
        $performanceChartRight = $performanceChartWidth - 24;
        $performanceChartTop = 22;
        $performanceChartBottom = 260;
        $performanceChartSlotWidth = ($performanceChartRight - $performanceChartLeft) / max(1, $scoreTrendDays->count());
        $performanceBarWidth = min(60, $performanceChartSlotWidth * .5);
    @endphp
    <div class="general-test-chart-scroll">
        <svg class="general-test-chart analytics-performance-chart" viewBox="0 0 {{ $performanceChartWidth }} 330" role="img" aria-label="Latest general-test scores by day from {{ $periodStart->format('M j') }} to {{ $periodEnd->format('M j, Y') }}">
            @foreach([100, 75, 50, 25, 0] as $scale)
                @php $y = $performanceChartBottom - (($scale / 100) * ($performanceChartBottom - $performanceChartTop)); @endphp
                <line class="{{ $scale === 75 ? 'analytics-chart-target analytics-target' : 'analytics-chart-grid analytics-grid' }}" x1="{{ $performanceChartLeft }}" x2="{{ $performanceChartRight }}" y1="{{ $y }}" y2="{{ $y }}"></line>
                <text x="{{ $performanceChartLeft - 10 }}" y="{{ $y + 5 }}" text-anchor="end" fill="#64748b" font-size="13">{{ $scale }}%</text>
            @endforeach
            @foreach($scoreTrendDays as $day)
                @php
                    $x = $performanceChartLeft + ($loop->index * $performanceChartSlotWidth) + (($performanceChartSlotWidth - $performanceBarWidth) / 2);
                    $dateLabel = \Carbon\CarbonImmutable::parse($day['date'])->format('M j');
                @endphp
                @if($day['score'] !== null)
                    @php
                        $barHeight = ($day['score'] / 100) * ($performanceChartBottom - $performanceChartTop);
                        $y = $performanceChartBottom - $barHeight;
                        $barColor = $day['score'] >= 75 ? '#059669' : $performanceChartPrimaryColor;
                    @endphp
                    <rect class="analytics-chart-bar" x="{{ $x }}" y="{{ $y }}" width="{{ $performanceBarWidth }}" height="{{ max(2, $barHeight) }}" fill="{{ $barColor }}">
                        <title>{{ $dateLabel }}: {{ $day['score'] }}% latest score from {{ $day['attempts'] }} attempt{{ $day['attempts'] === 1 ? '' : 's' }}</title>
                    </rect>
                    <text x="{{ $x + ($performanceBarWidth / 2) }}" y="{{ max(18, $y - 9) }}" text-anchor="middle" fill="#334155" font-size="13" font-weight="700">{{ $day['score'] }}%</text>
                @endif
                <text x="{{ $x + ($performanceBarWidth / 2) }}" y="292" text-anchor="middle" fill="#64748b" font-size="13">{{ $dateLabel }}</text>
            @endforeach
            <text x="{{ ($performanceChartLeft + $performanceChartRight) / 2 }}" y="322" text-anchor="middle" fill="#475569" font-size="13" font-weight="700">Test date</text>
            <text x="14" y="170" transform="rotate(-90 14 170)" text-anchor="middle" fill="#475569" font-size="13" font-weight="700">Score (%)</text>
        </svg>
    </div>
    <div class="d-flex flex-wrap gap-3 mt-2 small text-secondary">
        <span><span class="d-inline-block rounded-top me-1" style="width: .65rem; height: .75rem; vertical-align: middle; background: {{ $performanceChartPrimaryColor }};"></span>Below 75%</span>
        <span><span class="d-inline-block rounded-top me-1" style="width: .65rem; height: .75rem; vertical-align: middle; background: #059669;"></span>At or above 75%</span>
        <span><span class="d-inline-block me-1 border-top border-2 border-primary border-dashed" style="width: 1.25rem; vertical-align: middle;"></span>75% passing target</span>
    </div>
    <p class="small text-secondary mb-0 mt-3">Each chart column shows the latest test score for that day; the dashed line marks the 75% target.</p>
</section>

<div class="modal fade" id="generalTestDateRangeModal" tabindex="-1"
     aria-labelledby="generalTestDateRangeTitle" aria-describedby="generalTestDateRangeDescription" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="generalTestDateRangeTitle">Choose date range</h2>
                    <p class="text-secondary small mb-0" id="generalTestDateRangeDescription">Select up to 7 days, ending today or earlier.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="{{ $performanceRoute }}" data-performance-date-range>
                <input type="hidden" name="period" value="custom">
                <div class="modal-body d-grid gap-3">
                    <div>
                        <label for="performance-start-date" class="form-label small fw-semibold text-secondary">Start date</label>
                        <input id="performance-start-date" type="date" name="start_date" value="{{ $periodStartDate }}"
                               max="{{ now()->toDateString() }}" class="form-control" required>
                    </div>
                    <div>
                        <label for="performance-end-date" class="form-label small fw-semibold text-secondary">End date</label>
                        <input id="performance-end-date" type="date" name="end_date" value="{{ $periodEndDate }}"
                               min="{{ $periodStartDate }}" max="{{ now()->toDateString() }}"
                               class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-3">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-performance-date-range]').forEach(function (form) {
                    const startDate = form.querySelector('[name="start_date"]');
                    const endDate = form.querySelector('[name="end_date"]');
                    const today = endDate.max;

                    function constrainDateRange() {
                        if (!startDate.value) {
                            return;
                        }

                        const [year, month, day] = startDate.value.split('-').map(Number);
                        const latestEndDate = new Date(Date.UTC(year, month - 1, day + 6))
                            .toISOString()
                            .slice(0, 10);
                        endDate.min = startDate.value;
                        endDate.max = latestEndDate < today ? latestEndDate : today;

                        if (endDate.value < endDate.min) {
                            endDate.value = endDate.min;
                        } else if (endDate.value > endDate.max) {
                            endDate.value = endDate.max;
                        }
                    }

                    startDate.addEventListener('change', constrainDateRange);
                    constrainDateRange();
                });
            });
        </script>
    @endpush
@endonce
