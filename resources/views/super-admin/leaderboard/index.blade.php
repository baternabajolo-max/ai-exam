@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/super-admin/super-admin.css') }}">
@endpush

@section('content')
<div class="super-admin-page super-admin-leaderboard">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <span class="super-admin-eyebrow">Student achievement</span>
                <h1 class="super-admin-page-title">College leaderboard</h1>
                <p class="super-admin-page-subtitle">View the top students across a selected college.</p>
            </div>
            <a href="{{ route('super-admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <section class="leaderboard-card leaderboard-filter-card p-4 mb-4">
            <form method="GET" action="{{ route('super-admin.leaderboard') }}" class="row align-items-end g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="department_id">College</label>
                    <select class="form-select" id="department_id" name="department_id">
                        @forelse($departments as $department)
                            <option value="{{ $department->id }}" @selected($selectedDepartmentId === $department->id)>{{ $department->name }}</option>
                        @empty
                            <option value="">No colleges available</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-danger leaderboard-submit" type="submit"><i class="bi bi-trophy me-1"></i>View Leaderboard</button>
                </div>
            </form>
        </section>

        @if($selectedDepartment)
            @if($leaders->isNotEmpty())
                @php
                    $topThree = $leaders->take(3);
                @endphp
                <section class="leaderboard-podium" aria-label="Top three students">
                    @foreach([1 => 2, 0 => 1, 2 => 3] as $sourceIndex => $place)
                        @if($leader = $topThree->get($sourceIndex))
                            <article class="leaderboard-podium-card podium-place-{{ $place }}">
                                <span class="leaderboard-place-label">{{ match ($place) { 1 => 'Champion', 2 => '2nd Place', default => '3rd Place' } }}</span>
                                @if($leader->profile_picture_path)
                                    <img src="{{ asset('storage/'.$leader->profile_picture_path) }}" class="leaderboard-podium-avatar" alt="{{ $leader->name }}">
                                @else
                                    <span class="leaderboard-podium-avatar leaderboard-profile-fallback">{{ strtoupper(substr($leader->name, 0, 2)) }}</span>
                                @endif
                                <strong>{{ $leader->name }}</strong>
                                <span class="leaderboard-podium-id">{{ $leader->student_id_number ?: 'ID not assigned' }}</span>
                                <span class="leaderboard-podium-score">{{ number_format($leader->total_points) }} pts</span>
                            </article>
                        @endif
                    @endforeach
                </section>
            @endif

            <section class="leaderboard-card">
                <div class="card-body border-bottom">
                    <h2 class="h5 fw-bold mb-1">{{ $selectedDepartment->name }} Top Students</h2>
                    <p class="text-muted small mb-0">Top 10 students ranked by earned points.</p>
                </div>
                @if($leaders->isEmpty())
                    <div class="card-body"><p class="text-muted mb-0">No students have earned leaderboard points in this college yet.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th class="text-end">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaders as $leader)
                                    <tr>
                                        <td><span class="badge rounded-pill text-bg-primary">{{ $loop->iteration }}</span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                @if($leader->profile_picture_path)
                                                    <img src="{{ asset('storage/'.$leader->profile_picture_path) }}" class="rounded-circle leaderboard-profile-avatar" alt="{{ $leader->name }}">
                                                @else
                                                    <span class="rounded-circle leaderboard-profile-avatar leaderboard-profile-fallback" aria-hidden="true">
                                                        {{ strtoupper(substr($leader->name, 0, 2)) }}
                                                    </span>
                                                @endif
                                                <div>
                                                    <strong>{{ $leader->name }}</strong>
                                                    <small class="d-block text-muted">{{ $leader->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $leader->student_id_number ?: '—' }}</td>
                                        <td class="text-end fw-bold text-success">{{ number_format($leader->total_points) }} pts</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
