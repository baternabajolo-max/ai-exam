@include('partials.student-readiness-styles')

<div class="student-readiness-scope">
<section id="student-board-readiness" class="readiness-card p-3 p-md-4 mb-4" aria-labelledby="board-readiness-title" data-score="{{ $readinessScore ?? '' }}" data-question-count="{{ $readinessAssessedQuestionCount }}" data-audience="{{ $readinessAudience }}">
            <div class="readiness-header mb-4">
                <div>
                    <h2 id="board-readiness-title" class="h4 fw-bold mb-1">Board Exam Readiness</h2>
                    <p class="readiness-subtitle small mb-0">Latest general-test score and module breakdown for {{ $readinessAudience }}.</p>
                    <div class="readiness-legend" aria-label="Readiness score colors">
                        <span class="readiness-legend-item"><span class="readiness-legend-swatch is-low" aria-hidden="true"></span>Low · under 75%</span>
                        <span class="readiness-legend-item"><span class="readiness-legend-swatch is-passing" aria-hidden="true"></span>Passing · 75–89%</span>
                        <span class="readiness-legend-item"><span class="readiness-legend-swatch is-high" aria-hidden="true"></span>High · 90%+</span>
                    </div>
                </div>
                <span id="readiness-status" @class([
                    'readiness-status',
                    'is-high' => $readinessScore !== null && $readinessScore >= 90,
                    'is-passing' => $readinessScore !== null && $readinessScore >= 75 && $readinessScore < 90,
                ])>
                    {{ $readinessScore === null ? 'AWAITING DATA' : ($readinessScore >= 90 ? 'HIGH MASTERY' : ($readinessScore >= 75 ? 'ON TRACK' : 'NEEDS FOCUS')) }}
                </span>
                @if($weakestModuleRecord !== null && $weakestModule !== null)
                    <a href="{{ $readinessFocusUrl }}" @class([
                        'btn btn-sm btn-outline-primary dashboard-action readiness-focus-link rounded-pill px-3',
                        'is-high' => $weakestModule['score'] >= 90,
                        'is-passing' => $weakestModule['score'] >= 75 && $weakestModule['score'] < 90,
                    ])>
                        <i class="bi bi-lightning-charge me-1" aria-hidden="true"></i>
                        Focus: {{ $weakestModule['module'] }} · {{ $weakestModule['score'] }}%
                    </a>
                @endif
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="readiness-panel d-flex flex-column justify-content-center">
                        <h3 class="readiness-panel-title text-center mb-0">Latest general-test score</h3>
                        <svg class="readiness-gauge" viewBox="0 0 200 125" role="img" aria-labelledby="readiness-gauge-title readiness-gauge-description">
                            <title id="readiness-gauge-title">Latest general-test score</title>
                            <desc id="readiness-gauge-description">
                                {{ $readinessScore === null ? 'No general-test score recorded yet.' : "The latest general-test score is {$readinessScore} percent." }}
                            </desc>
                            <path class="readiness-gauge-track" d="M 18 110 A 82 82 0 0 1 182 110"></path>
                            <path id="readiness-gauge-progress" @class([
                                'readiness-gauge-progress',
                                'is-high' => $readinessScore !== null && $readinessScore >= 90,
                                'is-passing' => $readinessScore !== null && $readinessScore >= 75 && $readinessScore < 90,
                            ]) d="M 18 110 A 82 82 0 0 1 182 110"></path>
                            <text id="readiness-gauge-value" @class([
                                'readiness-gauge-empty' => $readinessScore === null,
                                'readiness-gauge-value' => $readinessScore !== null,
                                'is-high' => $readinessScore !== null && $readinessScore >= 90,
                                'is-passing' => $readinessScore !== null && $readinessScore >= 75 && $readinessScore < 90,
                            ]) x="100" y="105">
                                {{ $readinessScore === null ? 'No score' : $readinessScore.'%' }}
                            </text>
                        </svg>
                        <p class="readiness-target-note mb-1">Passing target: <strong>75%</strong></p>
                        <p id="readiness-summary" class="readiness-caption small text-center mb-0">
                            @if($readinessScore === null)
                                No general-test score recorded yet. Adjust a module slider to preview a simulated result.
                            @else
                                Latest general-test result: {{ $readinessScore }}% across {{ $readinessAssessedQuestionCount }} {{ \Illuminate\Support\Str::plural('question', $readinessAssessedQuestionCount) }}.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="readiness-panel">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                            <div>
                                <h3 class="readiness-panel-title mb-1">Module breakdown</h3>
                                <p class="readiness-caption small mb-0">Accuracy by module from the latest general test. The dashed line marks 75%.</p>
                            </div>
                            <span class="readiness-caption small">{{ $readinessAssessedModuleCount }} of {{ count($readinessModules) }} assessed</span>
                        </div>

                        <div class="readiness-subject-list" id="readiness-subject-list">
                            @foreach($readinessModules as $index => $module)
                                <div class="readiness-subject" data-module-index="{{ $index }}">
                                    <div class="readiness-subject-heading">
                                        <span class="readiness-subject-name" title="{{ $module['module'] }}">{{ $module['module'] }}</span>
                                        @if($module['score'] !== null)
                                            <span @class([
                                                'readiness-subject-score',
                                                'is-high' => $module['score'] >= 90,
                                                'is-passing' => $module['score'] >= 75 && $module['score'] < 90,
                                            ]) data-module-score>{{ $module['score'] }}%</span>
                                        @else
                                            <span class="readiness-caption small" data-module-score>No data</span>
                                        @endif
                                    </div>
                                    <div class="readiness-bar-track" role="img" aria-label="{{ $module['module'] }}: {{ $module['score'] !== null ? $module['score'].' percent' : 'no recorded score' }}">
                                        <span class="readiness-target-label">75% target</span>
                                        <span class="readiness-target-line" aria-hidden="true"></span>
                                        <span @class([
                                            'readiness-bar-fill',
                                            'is-high' => $module['score'] !== null && $module['score'] >= 90,
                                            'is-passing' => $module['score'] !== null && $module['score'] >= 75 && $module['score'] < 90,
                                        ]) data-module-bar style="width: {{ $module['score'] ?? 0 }}%;"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="readiness-simulator" aria-labelledby="readiness-simulator-title">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h3 id="readiness-simulator-title" class="readiness-panel-title mb-1">Score simulator</h3>
                        <p class="readiness-caption small mb-0">Simulate module scores to see how they could affect {{ $readinessAudience }} weighted readiness. Unassessed modules start at 50% until adjusted. Changes are not saved.</p>
                    </div>
                    <button id="readiness-reset" class="btn btn-sm btn-outline-secondary readiness-reset rounded-pill px-3" type="button">
                        Reset to actual
                    </button>
                </div>

                <div class="readiness-slider-list" id="readiness-slider-list">
                    @foreach($readinessModules as $index => $module)
                        <label class="readiness-slider-item" for="readiness-slider-{{ $index }}">
                            <span class="readiness-subject-heading mb-2">
                                <span class="readiness-subject-name">{{ $module['module'] }}</span>
                                <span @class([
                                    'readiness-caption small' => $module['score'] === null,
                                    'readiness-slider-value' => $module['score'] !== null,
                                    'is-high' => $module['score'] !== null && $module['score'] >= 90,
                                    'is-passing' => $module['score'] !== null && $module['score'] >= 75 && $module['score'] < 90,
                                ]) data-slider-value>
                                    {{ $module['score'] === null ? 'No data' : $module['score'].'%' }}
                                </span>
                            </span>
                            <input
                                id="readiness-slider-{{ $index }}"
                                @class([
                                    'readiness-slider',
                                    'is-high' => $module['score'] !== null && $module['score'] >= 90,
                                    'is-passing' => $module['score'] !== null && $module['score'] >= 75 && $module['score'] < 90,
                                ])
                                type="range"
                                min="0"
                                max="100"
                                step="1"
                                value="{{ $module['score'] ?? 50 }}"
                                data-actual-score="{{ $module['score'] ?? '' }}"
                                data-baseline-score="{{ $module['score'] ?? 50 }}"
                                data-question-count="{{ $module['question_count'] }}"
                                data-assessed="{{ $module['score'] !== null ? 'true' : 'false' }}"
                                data-readiness-slider
                                aria-label="Simulated {{ $module['module'] }} score"
                                aria-valuetext="{{ $module['score'] !== null ? $module['score'].' percent' : 'No recorded score; simulation starts at 50 percent' }}"
                            >
                        </label>
                    @endforeach
                </div>
            </div>
        </section>
</div>
