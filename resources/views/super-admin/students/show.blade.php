@extends('layouts.app')

@section('content')
<main class="container py-4">
    <a href="{{ route('super-admin.dashboard', ['college_id' => request('college_id'), 'student_search' => request('student_search')]) }}"
       class="btn btn-sm btn-outline-secondary rounded-pill mb-4">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to student search
    </a>

    <header class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <span class="badge text-bg-primary mb-2">Student record · Read only</span>
            <h1 class="h3 mb-1">{{ $student->name }}</h1>
            <p class="text-secondary mb-2">{{ $student->email }}</p>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge text-bg-light">{{ $student->student_id_number ?: 'No student ID' }}</span>
                <span class="badge text-bg-light">{{ $student->department?->name ?? 'No college' }}</span>
                <span class="badge text-bg-light">{{ $student->course?->name ?? 'No program' }}</span>
            </div>
        </div>
    </header>

    <section class="card shadow-sm border-0" aria-labelledby="student-general-test-history-title">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <h2 class="h5 mb-1" id="student-general-test-history-title">General-test history</h2>
                <p class="small text-secondary mb-0">The student's 10 most recent general-test attempts.</p>
            </div>
            <span class="badge rounded-pill bg-light text-secondary border">{{ $generalTestHistory->count() }} recent attempts</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse($generalTestHistory as $result)
                    @php
                        $scorePercentage = $result->display_percentage;
                        $scoreTone = $scorePercentage >= 75 ? 'text-success' : 'text-danger';
                    @endphp
                    <div class="col-12 col-lg-6">
                        <article class="border rounded-3 p-3 h-100 d-flex align-items-center gap-3">
                            <span class="badge rounded-circle bg-warning-subtle text-dark border border-warning-subtle d-flex align-items-center justify-content-center"
                                  style="width: 2rem; height: 2rem;">
                                {{ $loop->iteration }}
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <h3 class="h6 text-truncate mb-1" title="{{ $result->test?->title ?? 'General Test' }}">
                                    {{ $result->test?->title ?? 'General Test' }}
                                </h3>
                                <time class="small text-secondary" datetime="{{ $result->created_at->toIso8601String() }}">
                                    {{ $result->created_at->format('M d, Y · h:i A') }}
                                </time>
                            </div>
                            <div class="text-end">
                                <strong class="{{ $scoreTone }}">{{ $scorePercentage }}%</strong>
                                <span class="small text-secondary d-block">{{ $result->score }}/{{ $result->display_total_questions }}</span>
                            </div>
                        </article>
                    </div>
                @empty
                    <p class="text-secondary small mb-0 py-3 text-center">No general-test attempts yet.</p>
                @endforelse
            </div>
        </div>
    </section>
</main>
@endsection
