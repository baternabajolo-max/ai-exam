@extends('layouts.app')

@section('content')
<style>
    .module-review-page { --module-navy:#102a56; --module-blue:#2948a3; --module-indigo:#5143a8; }
    .module-review-hero {
        background: radial-gradient(circle at 90% 15%, rgb(255 255 255 / 13%) 0 5rem, transparent 5.1rem),
            linear-gradient(125deg, var(--module-navy) 0%, var(--module-blue) 58%, var(--module-indigo) 100%);
        border-radius: 1.25rem; color:#fff; padding:clamp(1.25rem,3vw,2rem);
        box-shadow:0 1rem 2rem rgb(35 54 115 / 16%);
    }
    .module-review-filter { min-width:16rem; width:16rem; }
    .module-review-card {
        border:1px solid #e2e8f0; border-radius:1rem; background:#fff;
        box-shadow:0 .75rem 1.75rem rgb(15 23 42 / 7%);
        transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .module-review-card:hover { transform:translateY(-3px); border-color:#c7d2fe; box-shadow:0 1rem 2.25rem rgb(15 23 42 / 12%); }
    .module-review-card__image { height:9rem; background:#eef2ff; overflow:hidden; border-radius:1rem 1rem 0 0; }
    .module-review-card__image img { width:100%; height:100%; object-fit:cover; }
    .module-review-kpi { border:1px solid #e2e8f0; border-radius:1rem; background:#fff; padding:1rem 1.25rem; }
    .module-review-kpi strong { font-size:1.5rem; font-variant-numeric:tabular-nums; }
    html[data-bs-theme="dark"] .module-review-card, html[data-bs-theme="dark"] .module-review-kpi { background:#1e293b; border-color:#334155; }
</style>

<main class="container module-review-page pb-5">
    <header class="module-review-hero d-flex flex-wrap justify-content-between align-items-end gap-4 mb-4">
        <div>
            <div class="text-uppercase small fw-bold text-info-emphasis mb-2">Learning content review</div>
            <h1 class="h2 fw-bold mb-2">Faculty Modules</h1>
            <p class="text-white-50 mb-0">Review uploaded modules and topics across colleges and programs.</p>
        </div>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="module-review-kpi">
                <span class="text-secondary small fw-bold text-uppercase">Modules</span>
                <strong class="d-block mt-1">{{ $modules->count() }}</strong>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="module-review-kpi">
                <span class="text-secondary small fw-bold text-uppercase">Topics in program</span>
                <strong class="d-block mt-1">{{ $topicCount }}</strong>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="module-review-kpi">
                <span class="text-secondary small fw-bold text-uppercase">Need revision</span>
                <strong class="d-block mt-1 text-warning">{{ $flaggedTopicCount }}</strong>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('super-admin.modules.index') }}" class="d-flex flex-wrap gap-2 mb-4">
        <label class="visually-hidden" for="module-college-filter">College</label>
        <select id="module-college-filter" name="department_id" class="form-select module-review-filter"
            onchange="this.form.elements.course_id.value=''; this.form.requestSubmit()">
            <option value="">All colleges</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) $selectedDepartmentId === (string) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        <label class="visually-hidden" for="module-program-filter">Program</label>
        <select id="module-program-filter" name="course_id" class="form-select module-review-filter"
            @disabled($selectedDepartmentId === null) onchange="this.form.requestSubmit()">
            @if($selectedDepartmentId === null)
                <option value="">Choose a college first</option>
            @else
                <option value="">All programs</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>
                        {{ $course->name }}
                    </option>
                @endforeach
            @endif
        </select>
        <noscript><button class="btn btn-primary" type="submit">Apply filters</button></noscript>
    </form>

    <div class="row g-4">
        @forelse($modules as $module)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('super-admin.modules.show', $module) }}" class="text-decoration-none text-reset">
                    <article class="module-review-card h-100">
                        <div class="module-review-card__image">
                            @if($module->logo_path)
                                <img src="{{ asset('storage/'.$module->logo_path) }}" alt="{{ $module->title }}">
                            @else
                                <div class="h-100 d-flex align-items-center justify-content-center text-primary">
                                    <i class="bi bi-journal-bookmark fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h2 class="h5 fw-bold mb-2">{{ $module->title }}</h2>
                            <p class="text-secondary small mb-3">{{ $module->description ?: 'No description provided.' }}</p>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge text-bg-light border">{{ $module->department->name ?? 'Unknown college' }}</span>
                                <span class="badge text-bg-primary">{{ $module->course->name ?? 'Unknown program' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                <span class="text-secondary small">{{ $module->subtopics_count }} topics</span>
                                <span class="fw-semibold small">View module <i class="bi bi-arrow-right ms-1"></i></span>
                            </div>
                        </div>
                    </article>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="module-review-card text-center p-5">
                    <i class="bi bi-journal-x fs-1 text-secondary"></i>
                    <h2 class="h5 fw-bold mt-3 mb-1">No modules found</h2>
                    <p class="text-secondary mb-0">Choose a college and program or broaden the filters.</p>
                </div>
            </div>
        @endforelse
    </div>
</main>
@endsection
