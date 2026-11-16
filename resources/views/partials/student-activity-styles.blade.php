@push('styles')
<style>
.student-activity-scope {
    --student-theme: {{ $student->department?->color_theme ?: '#2563eb' }};
    --dash-primary: var(--student-theme, #2563eb);
    --dash-primary-hover: color-mix(in srgb, var(--dash-primary) 82%, #000);
    --dash-primary-soft: color-mix(in srgb, var(--dash-primary) 22%, white);
    --dash-border: #e2e8f0;
}
    .student-activity-scope .activity-card {
        --activity-low: #dc2626;
        --activity-green: #16a34a;
        --activity-high: #14532d;
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--dash-primary) 18%, #e2e8f0);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        color: #0f172a;
        scroll-margin-top: 6rem;
    }

    .student-activity-scope .activity-panel {
        background: color-mix(in srgb, var(--dash-primary) 3%, white);
        border: 1px solid color-mix(in srgb, var(--dash-primary) 14%, #e2e8f0);
        border-radius: .85rem;
        padding: 1rem;
    }

    .student-activity-scope .activity-muted {
        color: #64748b;
    }

    .student-activity-scope .activity-total {
        background: var(--dash-primary-soft);
        border: 1px solid color-mix(in srgb, var(--dash-primary) 24%, white);
        color: var(--dash-primary-hover);
    }

    .student-activity-scope .activity-breadcrumbs {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }

    .student-activity-scope .activity-crumb {
        background: transparent;
        border: 0;
        color: var(--dash-primary-hover);
        font-size: .85rem;
        padding: .25rem;
    }

    .student-activity-scope .activity-crumb[aria-current="page"] {
        color: #0f172a;
        font-weight: 700;
    }

    .student-activity-scope .activity-chart-scroll {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .student-activity-scope .activity-month-chart {
        display: flex;
        min-height: 19rem;
        min-width: max-content;
        padding: .75rem .75rem .5rem .25rem;
    }

    .student-activity-scope .activity-month-axis {
        background: #f8fafc;
        border-right: 1px solid #dbe3ec;
        flex: 0 0 4.25rem;
        height: 19rem;
        position: sticky;
        left: 0;
        z-index: 3;
    }

    .student-activity-scope .activity-month-axis-title {
        color: #64748b;
        font-size: .65rem;
        font-weight: 800;
        left: .35rem;
        letter-spacing: .05em;
        position: absolute;
        text-transform: uppercase;
        top: -.15rem;
    }

    .student-activity-scope .activity-month-y-ticks {
        bottom: 2.9rem;
        left: 1.45rem;
        position: absolute;
        right: .35rem;
        top: 1.35rem;
    }

    .student-activity-scope .activity-month-y-tick {
        color: #64748b;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .68rem;
        position: absolute;
        right: 0;
        transform: translateY(-50%);
        white-space: nowrap;
    }

    .student-activity-scope .activity-month-plot {
        height: 19rem;
        position: relative;
    }

    .student-activity-scope .activity-month-gridlines {
        height: 14.75rem;
        left: 0;
        pointer-events: none;
        position: absolute;
        right: 0;
        top: 1.35rem;
    }

    .student-activity-scope .activity-month-gridline {
        border-top: 1px dashed #dbe3ec;
        left: 0;
        position: absolute;
        right: 0;
    }

    .student-activity-scope .activity-month-gridline.is-baseline {
        border-top: 2px solid #94a3b8;
    }

    .student-activity-scope .activity-month-columns {
        display: flex;
        height: 14.75rem;
        margin-top: 1.35rem;
        position: relative;
        z-index: 1;
    }

    .student-activity-scope .activity-month-button {
        align-items: center;
        background: transparent;
        border: 1px solid transparent;
        border-radius: .65rem;
        color: #334155;
        display: flex;
        flex: 0 0 5.25rem;
        height: 100%;
        justify-content: center;
        padding: 0 .35rem;
        position: relative;
        transition: background .2s ease, border-color .2s ease;
    }

    .student-activity-scope .activity-month-button:hover,
    .student-activity-scope .activity-month-button:focus-visible {
        background: var(--dash-primary-soft);
        border-color: color-mix(in srgb, var(--dash-primary) 40%, white);
        color: var(--dash-primary-hover);
        outline: none;
    }

    .student-activity-scope .activity-month-seconds {
        color: #334155;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .72rem;
        font-weight: 800;
        left: 50%;
        position: absolute;
        transform: translateX(-50%);
        white-space: nowrap;
        z-index: 2;
    }

    .student-activity-scope .activity-month-column {
        background: linear-gradient(180deg, color-mix(in srgb, var(--dash-primary) 88%, white), var(--dash-primary));
        border-radius: .55rem .55rem .2rem .2rem;
        bottom: 0;
        box-shadow: 0 5px 12px color-mix(in srgb, var(--dash-primary) 20%, transparent);
        left: 50%;
        min-height: 3px;
        position: absolute;
        transform: translateX(-50%);
        transition: filter .2s ease, height .35s cubic-bezier(.16, 1, .3, 1);
        width: 2.15rem;
    }

    .student-activity-scope .activity-month-button:hover .activity-month-column,
    .student-activity-scope .activity-month-button:focus-visible .activity-month-column {
        filter: brightness(1.12);
    }

    .student-activity-scope .activity-month-button.is-highest .activity-month-column {
        background: linear-gradient(180deg, #10b981, #047857);
        box-shadow: 0 5px 14px rgba(5, 150, 105, .24);
    }

    .student-activity-scope .activity-month-label {
        bottom: -2rem;
        color: #475569;
        font-size: .8rem;
        font-weight: 800;
        left: 50%;
        position: absolute;
        transform: translateX(-50%);
        white-space: nowrap;
    }

    .student-activity-scope .activity-calendar {
        display: grid;
        gap: clamp(.35rem, 1.5vw, .7rem);
        grid-template-columns: repeat(7, minmax(2.4rem, 1fr));
        margin-inline: auto;
        max-width: 36rem;
        padding: .75rem;
    }

    .student-activity-scope .activity-weekday {
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        padding: .35rem 0;
        text-align: center;
    }

    .student-activity-scope .activity-day {
        aspect-ratio: 1.12;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: .7rem;
        color: #64748b;
        font-weight: 700;
        min-width: 0;
        transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
    }

    .student-activity-scope .activity-day:hover,
    .student-activity-scope .activity-day:focus-visible {
        outline: 2px solid var(--dash-primary);
        outline-offset: 2px;
        transform: translateY(-1px);
    }

    .student-activity-scope .activity-day.is-level-0 {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #64748b;
    }

    .student-activity-scope .activity-day.is-level-1 {
        background: color-mix(in srgb, var(--activity-low) 14%, white);
        border-color: color-mix(in srgb, var(--activity-low) 32%, white);
        color: #991b1b;
    }

    .student-activity-scope .activity-day.is-level-2 {
        background: color-mix(in srgb, var(--activity-green) 18%, white);
        border-color: color-mix(in srgb, var(--activity-green) 32%, white);
        color: #166534;
    }

    .student-activity-scope .activity-day.is-level-3 {
        background: var(--activity-high);
        border-color: var(--activity-high);
        color: #fff;
    }

    .student-activity-scope .activity-day-empty {
        aspect-ratio: 1;
    }

    .student-activity-scope .activity-heatmap-legend {
        align-items: center;
        color: #475569;
        display: flex;
        flex-wrap: wrap;
        font-size: .72rem;
        gap: .55rem 1rem;
        justify-content: center;
        margin: 0 auto .75rem;
        max-width: 36rem;
    }

    .student-activity-scope .activity-heatmap-key {
        align-items: center;
        display: inline-flex;
        gap: .35rem;
    }

    .student-activity-scope .activity-heatmap-swatch {
        border: 1px solid color-mix(in srgb, var(--dash-primary) 24%, white);
        border-radius: .25rem;
        height: .8rem;
        width: .8rem;
    }

    .student-activity-scope .activity-heatmap-swatch.is-level-0 { background: #f1f5f9; border-color: #e2e8f0; }
    .student-activity-scope .activity-heatmap-swatch.is-level-1 { background: color-mix(in srgb, var(--activity-low) 14%, white); border-color: color-mix(in srgb, var(--activity-low) 32%, white); }
    .student-activity-scope .activity-heatmap-swatch.is-level-2 { background: color-mix(in srgb, var(--activity-green) 18%, white); border-color: color-mix(in srgb, var(--activity-green) 32%, white); }
    .student-activity-scope .activity-heatmap-swatch.is-level-3 { background: var(--activity-high); border-color: var(--activity-high); }

    .student-activity-scope .activity-bar-list {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(5, minmax(6.5rem, 1fr));
        overflow-x: auto;
        padding: .25rem .1rem .75rem;
        width: 100%;
    }

    .student-activity-scope .activity-bar-row,
    .student-activity-scope button.activity-bar-row {
        appearance: none;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .8rem;
        color: #0f172a;
        min-width: 0;
        padding: .8rem 1rem;
        text-align: left;
        transition: border-color .2s ease, background .2s ease, transform .2s ease;
        width: 100%;
    }

    .student-activity-scope .activity-bar-row[hidden] {
        display: none;
    }

    .student-activity-scope button.activity-bar-row {
        cursor: pointer;
    }

    .student-activity-scope .activity-bar-row:hover,
    .student-activity-scope .activity-bar-row:focus-visible {
        background: var(--dash-primary-soft);
        border-color: var(--dash-primary);
        outline: none;
        transform: translateY(-1px);
    }

    .student-activity-scope .activity-column-bar,
    .student-activity-scope button.activity-column-bar {
        align-items: stretch;
        display: flex;
        flex-direction: column;
        gap: .45rem;
        justify-content: flex-end;
        min-height: 13.5rem;
        padding: .7rem .55rem .6rem;
        text-align: center;
    }

    .student-activity-scope .activity-column-value {
        color: var(--dash-primary-hover);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .72rem;
        font-weight: 800;
        min-height: 1rem;
    }

    .student-activity-scope .activity-column-plot {
        align-items: flex-end;
        display: flex;
        height: 8rem;
        justify-content: center;
        position: relative;
    }

    .student-activity-scope .activity-column-fill {
        background: linear-gradient(180deg, color-mix(in srgb, var(--dash-primary) 72%, white), var(--dash-primary));
        border-radius: .55rem .55rem .2rem .2rem;
        box-shadow: 0 5px 12px color-mix(in srgb, var(--dash-primary) 18%, transparent);
        display: block;
        max-width: 2.5rem;
        min-height: 0;
        transition: height .35s cubic-bezier(.16, 1, .3, 1), filter .2s ease;
        width: 42%;
    }

    .student-activity-scope .activity-column-fill.is-highest {
        background: linear-gradient(180deg, #10b981, #047857);
        box-shadow: 0 5px 12px rgba(5, 150, 105, .2);
    }

    .student-activity-scope .activity-bar-row:hover .activity-column-fill,
    .student-activity-scope .activity-bar-row:focus-visible .activity-column-fill {
        filter: brightness(1.1);
    }

    .student-activity-scope .activity-column-title {
        color: #1e293b;
        display: -webkit-box;
        font-weight: 700;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        overflow-wrap: anywhere;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        min-height: 2.5em;
    }

    .student-activity-scope .activity-column-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        justify-content: center;
        min-height: 1.5rem;
    }

    .student-activity-scope .activity-column-meta .activity-status {
        font-size: .65rem;
        padding: .2rem .4rem;
    }

    .student-activity-scope .activity-column-meta .activity-topic-meta {
        font-size: .68rem;
    }

    .student-activity-scope .activity-column-bar[hidden] {
        display: none;
    }

    .student-activity-scope .activity-show-more {
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--dash-primary) 28%, #e2e8f0);
        border-radius: 999px;
        color: var(--dash-primary-hover);
        display: block;
        font-size: .85rem;
        font-weight: 700;
        margin: .85rem auto 0;
        padding: .55rem 1rem;
        transition: background .2s ease, border-color .2s ease;
    }

    .student-activity-scope .activity-show-more:hover,
    .student-activity-scope .activity-show-more:focus-visible {
        background: var(--dash-primary-soft);
        border-color: var(--dash-primary);
        outline: none;
    }

    .student-activity-scope .activity-quiz-history-item {
        align-items: center;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .8rem;
        display: flex;
        flex-wrap: wrap;
        gap: .7rem;
        justify-content: space-between;
        padding: .85rem;
    }

    .student-activity-scope .activity-topic-meta {
        color: #64748b;
        font-size: .8rem;
    }

    .student-activity-scope .activity-status {
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        color: #475569;
        font-size: .72rem;
        font-weight: 700;
        padding: .25rem .6rem;
    }

    .student-activity-scope .activity-status.is-passed {
        border-color: rgba(5, 150, 105, .35);
        color: #047857;
    }

    .student-activity-scope .activity-status.is-review {
        border-color: rgba(185, 28, 28, .3);
        color: #b91c1c;
    }

    @media (prefers-reduced-motion: reduce) {
        .student-activity-scope *,
        .student-activity-scope *::before,
        .student-activity-scope *::after {
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
        }
    }

@media (max-width: 575.98px) {
    .student-activity-scope .activity-calendar {
        grid-template-columns: repeat(7, minmax(0, 1fr));
        padding: .25rem;
    }

    .student-activity-scope .activity-day {
        min-height: 2.35rem;
    }
}
</style>
@endpush