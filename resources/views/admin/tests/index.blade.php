@extends('layouts.app')

@section('content')
<style>
    .exam-list__hero {
        background:
            radial-gradient(circle at 90% 15%, rgb(255 255 255 / 13%) 0 5rem, transparent 5.1rem),
            linear-gradient(125deg, #102a56 0%, #2948a3 58%, #5143a8 100%);
        border: 1px solid rgb(255 255 255 / 18%);
        border-radius: 1.25rem;
        box-shadow: 0 1rem 2rem rgb(35 54 115 / 16%);
        color: #fff;
        padding: clamp(1.25rem, 3vw, 2rem);
    }

    .exam-list__eyebrow {
        color: #c7d2fe;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .15em;
        text-transform: uppercase;
    }

    .exam-list__table-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 .75rem 1.75rem rgb(15 23 42 / 7%);
        overflow: hidden;
    }

    .exam-list__table {
        --bs-table-bg: #fff;
        --bs-table-color: #1e293b;
        min-width: 900px;
    }

    .exam-list__table thead th {
        background: #f8fafc;
        border-bottom-color: #e2e8f0;
        color: #475569;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        padding: .9rem 1rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .exam-list__table tbody td {
        border-color: #eef2f7;
        padding: 1rem;
    }

    .exam-list__availability {
        color: #334155;
        min-width: 12rem;
    }

    .exam-list__status {
        border: 1px solid transparent;
        font-size: .75rem;
        font-weight: 700;
        padding: .45rem .7rem;
    }

    .exam-list__status--active {
        background: #dcfce7;
        border-color: #bbf7d0;
        color: #166534;
    }

    .exam-list__status--pending,
    .exam-list__status--scheduled {
        background: #fef3c7;
        border-color: #fde68a;
        color: #92400e;
    }

    .exam-list__status--closed,
    .exam-list__status--rejected,
    .exam-list__status--draft {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #475569;
    }

    .exam-list__actions {
        min-width: 9.5rem;
    }

    .exam-list__table .btn {
        --bs-btn-border-radius: .65rem;
        white-space: nowrap;
    }

    .exam-list__empty {
        background: linear-gradient(145deg, #fff, #f8faff);
        border: 1px dashed #cbd5e1;
        border-radius: 1rem;
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .exam-list__empty-icon {
        align-items: center;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 1rem;
        color: #4338ca;
        display: inline-flex;
        font-size: 1.35rem;
        height: 3.25rem;
        justify-content: center;
        margin-bottom: 1rem;
        width: 3.25rem;
    }

    .exam-review__question {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .9rem;
        padding: 1rem;
    }

    @media (max-width: 767.98px) {
        .exam-list__hero {
            align-items: stretch !important;
        }

        .exam-list__hero .btn {
            width: 100%;
        }

        .exam-list__table {
            min-width: 0;
        }

        .exam-list__table thead {
            display: none;
        }

        .exam-list__table tbody tr:not(:has(> td[colspan])) {
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: .1rem;
            padding: .5rem;
        }

        .exam-list__table tbody tr:last-child {
            border-bottom: 0;
        }

        .exam-list__table tbody td {
            align-items: start;
            border: 0;
            display: grid;
            gap: .75rem;
            grid-template-columns: 7rem minmax(0, 1fr);
            padding: .55rem .65rem;
            text-align: right;
        }

        .exam-list__table tbody td::before {
            color: #64748b;
            content: attr(data-label);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-align: left;
            text-transform: uppercase;
        }

        .exam-list__table tbody td:last-child {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            min-width: 0;
        }

        .exam-list__table tbody td:last-child::before {
            margin-right: auto;
        }

        .exam-list__availability {
            min-width: 0;
        }

        .exam-list__actions {
            min-width: 0;
        }
    }

    html[data-bs-theme="dark"] .exam-list__table-wrap,
    html[data-bs-theme="dark"] .exam-list__empty,
    html[data-bs-theme="dark"] .exam-review__question {
        background: #1e293b;
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .exam-list__table thead th {
        background: #172033;
        border-color: #334155;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .exam-list__table {
        --bs-table-bg: #1e293b;
        --bs-table-color: #e2e8f0;
        --bs-table-hover-bg: #273449;
        --bs-table-hover-color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .exam-list__table tbody td {
        border-color: #334155;
    }

    html[data-bs-theme="dark"] .exam-list__availability {
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .exam-list__status--active {
        background: #123b2b;
        border-color: #166534;
        color: #bbf7d0;
    }

    html[data-bs-theme="dark"] .exam-list__status--pending,
    html[data-bs-theme="dark"] .exam-list__status--scheduled {
        background: #422006;
        border-color: #854d0e;
        color: #fde68a;
    }

    html[data-bs-theme="dark"] .exam-list__status--closed,
    html[data-bs-theme="dark"] .exam-list__status--rejected,
    html[data-bs-theme="dark"] .exam-list__status--draft {
        background: #273449;
        border-color: #475569;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .exam-list__empty-icon {
        background: #262f57;
        border-color: #4855a3;
        color: #c7d2fe;
    }

    @media (prefers-reduced-motion: reduce) {
        .exam-list__table tbody tr {
            transition: none;
        }
    }
</style>

<main class="container exam-list">
    <header class="exam-list__hero d-flex flex-wrap justify-content-between align-items-center gap-4 mb-4">
        <div>
            <div class="exam-list__eyebrow mb-2">Assessment management</div>
            <h1 class="h2 fw-bold mb-2">{{ auth()->user()->role === 'super-admin' ? 'Examinations & Analysis' : 'Examinations' }}</h1>
            <p class="text-white-50 mb-0">
                @if (auth()->user()->role === 'super-admin')
                    Review Faculty requests, publish assessments, and explore exam results.
                @else
                    Create examination requests and keep track of their approval and schedule.
                @endif
            </p>
        </div>
        @if (auth()->user()->role === 'super-admin')
            <a href="{{ route('super-admin.tests.create') }}" class="btn btn-light btn-lg rounded-3 px-4 fw-semibold">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Create Examination
            </a>
        @else
            <a href="{{ route('admin.tests.create') }}" class="btn btn-light btn-lg rounded-3 px-4 fw-semibold">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Create Examination
            </a>
        @endif
    </header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="exam-list__table-wrap" aria-label="Examination list">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 px-md-4 py-3 border-bottom">
            <div>
                <h2 class="h5 fw-bold mb-1">Examination schedule</h2>
                <p class="small text-muted mb-0">Review status, availability, and participation at a glance.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $tests->total() }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 exam-list__table">
                <thead class="table-light">
                    <tr><th scope="col">Examination</th><th scope="col">Program</th><th scope="col">Status</th><th scope="col">Availability</th><th scope="col">Questions</th><th scope="col">Results</th><th scope="col">Created by</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($tests as $test)
                        @php($status = $test->scheduleStatus())
                        <tr>
                            <td data-label="Examination"><div class="fw-semibold">{{ $test->title }}</div><div class="small text-muted">{{ $test->topic }}</div></td>
                            <td data-label="Program">{{ $test->course->name ?? 'Unknown program' }}</td>
                            <td data-label="Status"><span class="badge rounded-pill exam-list__status exam-list__status--{{ strtolower($status) }}">{{ $status }}</span></td>
                            <td data-label="Availability" class="exam-list__availability">
                                @if ($test->is_published)
                                    <div class="small fw-semibold"><i class="bi bi-calendar-event me-1 text-primary" aria-hidden="true"></i>Opens {{ $test->available_from?->format('M j, Y g:i A') ?? 'immediately' }}</div>
                                    @if ($test->available_until)<div class="small text-muted mt-1"><i class="bi bi-calendar-x me-1" aria-hidden="true"></i>Closes {{ $test->available_until->format('M j, Y g:i A') }}</div>@endif
                                @else
                                    <span class="small text-muted"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Unavailable until approved</span>
                                @endif
                            </td>
                            <td data-label="Questions"><span class="fw-semibold">{{ $test->questions_count }}</span></td>
                            <td data-label="Results"><span class="fw-semibold">{{ $test->results_count }}</span></td>
                            <td data-label="Created by">{{ $test->creator->name ?? 'Former Faculty' }}</td>
                            <td data-label="Actions" class="text-end exam-list__actions">
                                @if (auth()->user()->role === 'super-admin')
                                    @if ($test->approval_status === 'pending')
                                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#reviewExamModal{{ $test->id }}"><i class="bi bi-clipboard-check me-1" aria-hidden="true"></i>Review request</button>
                                    @elseif ($test->is_published)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.item-analysis.index', ['course_id' => $test->course_id, 'test_id' => $test->id]) }}"><i class="bi bi-graph-up-arrow me-1" aria-hidden="true"></i>Analyze</a>
                                    @endif
                                @elseif (auth()->user()->role === 'admin' && $test->is_published && $test->approval_status === 'approved')
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.item-analysis.index', ['test_id' => $test->id]) }}"><i class="bi bi-graph-up-arrow me-1" aria-hidden="true"></i>Analyze</a>
                                @endif
                                @if (auth()->user()->role === 'super-admin' && $test->results_count === 0)
                                    <form action="{{ route('super-admin.tests.destroy', $test) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this examination?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-3">
                            <div class="exam-list__empty">
                                <span class="exam-list__empty-icon" aria-hidden="true"><i class="bi bi-journal-x"></i></span>
                                <h3 class="h5 fw-bold">No examinations yet</h3>
                                <p class="text-muted mb-0">Create an examination to see its schedule and status here.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if (auth()->user()->role === 'super-admin')
        @foreach ($tests as $test)
            @if ($test->approval_status === 'pending')
                <div class="modal fade" id="reviewExamModal{{ $test->id }}" tabindex="-1" aria-labelledby="reviewExamModalLabel{{ $test->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title h5 mb-1" id="reviewExamModalLabel{{ $test->id }}">{{ $test->title }}</h2>
                                    <p class="small text-muted mb-0">{{ $test->course->name ?? 'Unknown program' }} · Submitted by {{ $test->creator->name ?? 'Former Faculty' }}</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body bg-body-tertiary">
                            <div class="exam-review__question mb-3">
                                <div class="small text-uppercase text-primary fw-bold mb-1">Topic</div>
                                <p class="mb-0 fw-semibold">{{ $test->topic }}</p>
                            </div>
                            @if ($test->description)<p class="text-muted">{{ $test->description }}</p>@endif
                            <p class="small text-muted">Scheduled {{ $test->available_from?->format('M j, Y g:i A') ?? 'immediately' }}{{ $test->available_until ? ' until '.$test->available_until->format('M j, Y g:i A') : '' }} · {{ $test->time_limit }} minute limit</p>
                            <hr>
                            @foreach ($test->questions as $question)
                                <section class="exam-review__question mb-3">
                                    <h3 class="h6 fw-bold"><span class="text-primary me-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $question->question }}</h3>
                                        <ol type="A" class="mb-1">
                                            @foreach ($question->options ?? [] as $option)
                                                <li class="{{ $option === $question->correct_answer ? 'text-success fw-semibold' : '' }}">
                                                    {{ $option }}
                                                    @if ($option === $question->correct_answer)<span class="badge text-bg-success">Correct answer</span>@endif
                                                </li>
                                            @endforeach
                                        </ol>
                                        <p class="small text-muted mb-0">Question from {{ $question->questionBankItem->author->name ?? 'Former Faculty' }}'s bank</p>
                                    </section>
                                @endforeach
                            </div>
                            <div class="modal-footer justify-content-between">
                                <form action="{{ route('super-admin.tests.reject', $test) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-outline-danger" type="submit">Reject request</button>
                                </form>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                                    <form action="{{ route('super-admin.tests.approve', $test) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-success" type="submit">Approve and publish</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
    <div class="mt-3">{{ $tests->links() }}</div>
</div>
@endsection
