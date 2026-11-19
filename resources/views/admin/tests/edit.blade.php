@extends('layouts.app')

@section('title', 'Edit Examination | LEMU')

@section('content')
@php
    $testRoutePrefix = auth()->user()->role === 'admin' ? 'admin' : 'super-admin';
    $selectedQuestionIds = (array) old(
        'question_bank_item_ids',
        $test->questions->pluck('question_bank_item_id')->filter()->all(),
    );
    $selectedQuestionIds = array_map('intval', $selectedQuestionIds);
    $maximumQuestionCount = min(100, $questionBankItems->count());
@endphp

<main class="container py-4 py-lg-5">
    <header class="mb-4">
        <a href="{{ route($testRoutePrefix.'.tests.index') }}" class="small text-decoration-none">&larr; Examinations</a>
        <h1 class="h2 fw-bold mt-2 mb-1">Edit examination</h1>
        <p class="text-muted mb-0">{{ $test->title }} · {{ $test->course?->name ?? 'Unknown program' }}</p>
    </header>

    @if (auth()->user()->role === 'admin' && $test->approval_status === \App\Models\Test::APPROVAL_STATUS_APPROVED)
        <div class="alert alert-warning" role="status">
            Saving changes will return this examination to Admin review and temporarily remove it from student access.
        </div>
    @endif

    <form action="{{ route($testRoutePrefix.'.tests.update', $test) }}" method="POST" class="card border-0 shadow-sm rounded-4">
        @csrf
        @method('PUT')
        <div class="card-body p-4 p-lg-5">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="exam-title">Examination name</label>
                    <input id="exam-title" name="title" class="form-control" maxlength="255" value="{{ old('title', $test->title) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="exam-topic">Subject or topic</label>
                    <input id="exam-topic" name="topic" class="form-control" maxlength="255" value="{{ old('topic', $test->topic) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="exam-description">Instructions <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="exam-description" name="description" class="form-control" rows="3" maxlength="5000">{{ old('description', $test->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="exam-program">Program</label>
                    <input id="exam-program" class="form-control" value="{{ $test->course?->name ?? 'Unknown program' }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="time-limit">Time limit (minutes)</label>
                    <input id="time-limit" name="time_limit" type="number" class="form-control" min="1" max="300" value="{{ old('time_limit', $test->time_limit ?? $test->duration_minutes) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="available-from">Start date and time</label>
                    <input
                        id="available-from"
                        name="available_from"
                        type="datetime-local"
                        class="form-control"
                        min="{{ now()->format('Y-m-d\TH:i') }}"
                        value="{{ old('available_from', $test->available_from?->format('Y-m-d\TH:i')) }}"
                        required
                    >
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="available-until">Closing date and time <span class="text-muted fw-normal">(optional)</span></label>
                    <input
                        id="available-until"
                        name="available_until"
                        type="datetime-local"
                        class="form-control"
                        min="{{ old('available_from', $test->available_from?->format('Y-m-d\TH:i')) }}"
                        value="{{ old('available_until', $test->available_until?->format('Y-m-d\TH:i')) }}"
                    >
                </div>
            </div>

            <section aria-labelledby="edit-exam-questions-heading">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1" id="edit-exam-questions-heading">Examination questions</h2>
                        <p class="small text-muted mb-0">Select exactly the number of questions entered below.</p>
                    </div>
                    <div>
                        <label class="form-label fw-semibold" for="question-count">Question count</label>
                        <input
                            id="question-count"
                            name="question_count"
                            type="number"
                            class="form-control"
                            min="1"
                            max="{{ max(1, $maximumQuestionCount) }}"
                            value="{{ old('question_count', count($selectedQuestionIds)) }}"
                            required
                        >
                    </div>
                </div>
                @if ($questionBankItems->isEmpty())
                    <div class="alert alert-warning mb-0" role="status">There are no question-bank items for this program, so this examination cannot be edited.</div>
                @else
                    <div class="row g-3">
                        @foreach ($questionBankItems as $item)
                            <div class="col-12 col-lg-6">
                                <label class="d-flex gap-3 h-100 rounded-3 border p-3">
                                    <input
                                        class="form-check-input mt-1"
                                        type="checkbox"
                                        name="question_bank_item_ids[]"
                                        value="{{ $item->id }}"
                                        @checked(in_array($item->id, $selectedQuestionIds, true))
                                    >
                                    <span>
                                        <span class="badge text-bg-light mb-2">{{ $item->topic ?: 'General' }}</span>
                                        @if (! $item->is_active)
                                            <span class="badge text-bg-secondary mb-2">Disabled · already on this exam</span>
                                        @endif
                                        <span class="badge rounded-pill {{ $item->difficulty === 'hard' ? 'text-bg-danger' : ($item->difficulty === 'easy' ? 'text-bg-success' : 'text-bg-warning') }} mb-2">{{ ucfirst($item->difficulty ?? 'moderate') }}</span>
                                        <span class="d-block fw-semibold">{{ $item->question }}</span>
                                        @if ($item->image_path)
                                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="Question illustration" class="img-fluid rounded border mt-2" style="max-height: 7rem; object-fit: contain;">
                                        @endif
                                        <span class="small text-muted">{{ count($item->options ?? []) }} choices · {{ $item->author?->name ?? 'Former Faculty' }}'s bank</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
        <div class="card-footer bg-transparent border-0 px-4 px-lg-5 pb-4 d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route($testRoutePrefix.'.tests.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-pill px-4" @disabled($questionBankItems->isEmpty())>Save examination</button>
        </div>
    </form>
</main>
@endsection
