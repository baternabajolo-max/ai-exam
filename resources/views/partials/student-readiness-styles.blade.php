@push('styles')
<style>
.student-readiness-scope {
    --student-theme: {{ $student->department?->color_theme ?: '#2563eb' }};
    --dash-primary: var(--student-theme, #2563eb);
    --dash-primary-hover: color-mix(in srgb, var(--dash-primary) 82%, #000);
    --dash-primary-soft: color-mix(in srgb, var(--dash-primary) 22%, white);
    --dash-border: #e2e8f0;
}

    .student-readiness-scope .readiness-card {
        --readiness-red: #dc2626;
        --readiness-green: #16a34a;
        --readiness-high: #14532d;
        --readiness-panel: color-mix(in srgb, var(--dash-primary) 13%, white);
        --readiness-border: color-mix(in srgb, var(--dash-primary) 16%, #e2e8f0);
        --readiness-muted: #64748b;
        background: linear-gradient(120deg, var(--dash-primary-soft), #fff 46%);
        border: 1px solid var(--readiness-border);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        color: #0f172a;
        overflow: hidden;
    }

    .student-readiness-scope .readiness-header {
        align-items: flex-start;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
    }

    .student-readiness-scope .readiness-subtitle,
    .student-readiness-scope .readiness-caption {
        color: var(--readiness-muted);
    }

    .student-readiness-scope .readiness-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem .85rem;
        margin-top: .65rem;
    }

    .student-readiness-scope .readiness-legend-item {
        align-items: center;
        color: var(--readiness-muted);
        display: inline-flex;
        font-size: .68rem;
        gap: .35rem;
    }

    .student-readiness-scope .readiness-legend-swatch {
        border-radius: 999px;
        height: .55rem;
        width: .55rem;
    }

    .student-readiness-scope .readiness-legend-swatch.is-low {
        background: var(--readiness-red);
    }

    .student-readiness-scope .readiness-legend-swatch.is-passing {
        background: var(--readiness-green);
    }

    .student-readiness-scope .readiness-legend-swatch.is-high {
        background: var(--readiness-high);
    }

    .student-readiness-scope .readiness-status {
        background: color-mix(in srgb, var(--readiness-red) 8%, white);
        border: 1px solid color-mix(in srgb, var(--readiness-red) 22%, white);
        border-radius: 999px;
        color: var(--readiness-red);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .1em;
        padding: .5rem .8rem;
        white-space: nowrap;
    }

    .student-readiness-scope .readiness-status.is-passing {
        background: color-mix(in srgb, var(--readiness-green) 8%, white);
        border-color: color-mix(in srgb, var(--readiness-green) 22%, white);
        color: var(--readiness-green);
    }

    .student-readiness-scope .readiness-status.is-high {
        background: color-mix(in srgb, var(--readiness-high) 9%, white);
        border-color: color-mix(in srgb, var(--readiness-high) 24%, white);
        color: var(--readiness-high);
    }

    .student-readiness-scope .readiness-focus-link {
        border-color: color-mix(in srgb, var(--readiness-red) 45%, white);
        color: var(--readiness-red);
    }

    .student-readiness-scope .readiness-focus-link.is-passing {
        border-color: color-mix(in srgb, var(--readiness-green) 45%, white);
        color: var(--readiness-green);
    }

    .student-readiness-scope .readiness-focus-link.is-high {
        border-color: color-mix(in srgb, var(--readiness-high) 45%, white);
        color: var(--readiness-high);
    }

    .student-readiness-scope .readiness-focus-link:hover {
        background: color-mix(in srgb, var(--readiness-red) 8%, white);
        border-color: var(--readiness-red);
        color: #991b1b;
    }

    .student-readiness-scope .readiness-focus-link.is-passing:hover {
        background: color-mix(in srgb, var(--readiness-green) 8%, white);
        border-color: var(--readiness-green);
        color: #166534;
    }

    .student-readiness-scope .readiness-focus-link.is-high:hover {
        background: color-mix(in srgb, var(--readiness-high) 8%, white);
        border-color: var(--readiness-high);
        color: var(--readiness-high);
    }

    .student-readiness-scope .readiness-panel {
        background: var(--readiness-panel);
        border: 1px solid var(--readiness-border);
        border-radius: .85rem;
        height: 100%;
        padding: 1.25rem;
    }

    .student-readiness-scope .readiness-panel-title {
        color: #334155;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .student-readiness-scope .readiness-gauge {
        display: block;
        margin: .4rem auto 0;
        max-width: 19rem;
        overflow: visible;
        width: 100%;
    }

    .student-readiness-scope .readiness-gauge-track,
    .student-readiness-scope .readiness-gauge-progress {
        fill: none;
        stroke-linecap: round;
        stroke-width: 12;
    }

    .student-readiness-scope .readiness-gauge-track {
        stroke: #e2e8f0;
    }

    .student-readiness-scope .readiness-gauge-progress {
        stroke: var(--readiness-red);
        transition: stroke-dashoffset .4s ease, stroke .25s ease;
    }

    .student-readiness-scope .readiness-gauge-progress.is-passing {
        stroke: var(--readiness-green);
    }

    .student-readiness-scope .readiness-gauge-progress.is-high {
        stroke: var(--readiness-high);
    }

    .student-readiness-scope .readiness-gauge-value {
        fill: #0f172a;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 2.6rem;
        font-weight: 800;
        text-anchor: middle;
    }

    .student-readiness-scope .readiness-gauge-value.is-passing {
        fill: var(--readiness-green);
    }

    .student-readiness-scope .readiness-gauge-value.is-high {
        fill: var(--readiness-high);
    }

    .student-readiness-scope .readiness-gauge-empty {
        fill: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
        text-anchor: middle;
    }

    .student-readiness-scope .readiness-target-note {
        color: var(--readiness-muted);
        font-size: .82rem;
        text-align: center;
    }

    .student-readiness-scope .readiness-target-note strong {
        color: var(--readiness-green);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .student-readiness-scope .readiness-subject-list {
        display: grid;
        gap: 1.25rem;
    }

    .student-readiness-scope .readiness-subject-heading {
        align-items: baseline;
        display: flex;
        gap: .75rem;
        justify-content: space-between;
    }

    .student-readiness-scope .readiness-subject-name {
        color: #334155;
        font-size: .9rem;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .student-readiness-scope .readiness-subject-score,
    .student-readiness-scope .readiness-slider-value {
        color: var(--readiness-red);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .85rem;
        font-weight: 800;
    }

    .student-readiness-scope .readiness-subject-score.is-passing,
    .student-readiness-scope .readiness-slider-value.is-passing {
        color: var(--readiness-green);
    }

    .student-readiness-scope .readiness-subject-score.is-high,
    .student-readiness-scope .readiness-slider-value.is-high {
        color: var(--readiness-high);
    }

    .student-readiness-scope .readiness-bar-track {
        background: #e2e8f0;
        border-radius: 999px;
        height: .65rem;
        margin-top: .55rem;
        position: relative;
    }

    .student-readiness-scope .readiness-target-line {
        border-left: 1px dashed color-mix(in srgb, var(--readiness-green) 80%, white);
        bottom: -.35rem;
        left: 75%;
        pointer-events: none;
        position: absolute;
        top: -.35rem;
        z-index: 2;
    }

    .student-readiness-scope .readiness-bar-fill {
        background: var(--readiness-red);
        border-radius: inherit;
        display: block;
        height: 100%;
        position: relative;
        transition: width .25s ease, background .25s ease;
        z-index: 1;
    }

    .student-readiness-scope .readiness-bar-fill.is-passing {
        background: var(--readiness-green);
    }

    .student-readiness-scope .readiness-bar-fill.is-high {
        background: var(--readiness-high);
    }

    .student-readiness-scope .readiness-target-label {
        color: var(--readiness-green);
        font-size: .62rem;
        font-weight: 700;
        left: 75%;
        position: absolute;
        top: -.15rem;
        transform: translate(-50%, -100%);
        white-space: nowrap;
    }

    .student-readiness-scope .readiness-simulator {
        background: color-mix(in srgb, var(--dash-primary) 5%, white);
        border: 1px solid var(--readiness-border);
        border-radius: .85rem;
        margin-top: 1rem;
        padding: 1.25rem;
    }

    .student-readiness-scope .readiness-slider-list {
        display: grid;
        gap: 1rem 1.5rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .student-readiness-scope .readiness-slider {
        accent-color: var(--readiness-red);
        display: block;
        width: 100%;
    }

    .student-readiness-scope .readiness-slider.is-passing {
        accent-color: var(--readiness-green);
    }

    .student-readiness-scope .readiness-slider.is-high {
        accent-color: var(--readiness-high);
    }

    .student-readiness-scope .readiness-slider:focus-visible {
        outline: 2px solid var(--readiness-green);
        outline-offset: 4px;
    }

    .student-readiness-scope .readiness-empty-state {
        border: 1px dashed color-mix(in srgb, var(--dash-primary) 30%, #cbd5e1);
        border-radius: .75rem;
        color: var(--readiness-muted);
        padding: 1.5rem;
        text-align: center;
    }

    .student-readiness-scope .readiness-reset {
        border-color: color-mix(in srgb, var(--dash-primary) 40%, #cbd5e1);
        color: var(--dash-primary-hover);
    }

    .student-readiness-scope .readiness-reset:hover {
        background: var(--dash-primary-soft);
        border-color: var(--readiness-green);
        color: var(--dash-primary-hover);
    }

@media (max-width: 575.98px) {
    .student-readiness-scope .readiness-panel,
    .student-readiness-scope .readiness-simulator {
        padding: 1rem;
    }

    .student-readiness-scope .readiness-slider-list {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
@endpush
