@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* ==========================================================
       LUXURY BURGUNDY & GOLD THEME (MATCHING LEMU LOGO)
    ========================================================== */
    :root {
        --lemu-burgundy: #4a0e17;
        --lemu-burgundy-dark: #2c070e;
        --lemu-gold: #eab308;
        --lemu-gold-light: #fef08a;
    }

    @keyframes luxuryFadeUp {
        0% { opacity: 0; transform: translateY(15px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes cardFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }

    .animate-entrance {
        animation: luxuryFadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* 3D Perspective & Floating Podium Cards */
    .podium-scene {
        perspective: 1400px;
    }

    .podium-card {
        border-radius: 1.5rem !important;
        transform-style: preserve-3d;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.4s ease;
        position: relative;
        overflow: hidden;
        animation: cardFloat 5s ease-in-out infinite;
    }

    .row.g-3 > div:nth-child(2) .podium-card { animation-delay: 0s; } /* 1st place */
    .row.g-3 > div:nth-child(1) .podium-card { animation-delay: 1.8s; } /* 2nd place */
    .row.g-3 > div:nth-child(3) .podium-card { animation-delay: 3.2s; } /* 3rd place */

    .podium-card:hover {
        transform: translateY(-8px) rotateX(3deg) scale(1.02);
        animation-play-state: paused;
    }

    .podium-1 {
        background: linear-gradient(135deg, rgba(254, 243, 199, 0.95) 0%, rgba(253, 224, 71, 0.25) 100%);
        border: 2px solid rgba(234, 179, 8, 0.6) !important;
        box-shadow: 0 20px 40px -10px rgba(74, 14, 23, 0.15), 0 0 20px rgba(234, 179, 8, 0.2) !important;
    }
    .podium-2 {
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.95) 0%, rgba(226, 232, 240, 0.8) 100%);
        border: 2px solid rgba(148, 163, 184, 0.5) !important;
        box-shadow: 0 15px 30px -10px rgba(74, 14, 23, 0.08) !important;
    }
    .podium-3 {
        background: linear-gradient(135deg, rgba(255, 247, 237, 0.95) 0%, rgba(254, 215, 170, 0.7) 100%);
        border: 2px solid rgba(249, 115, 22, 0.4) !important;
        box-shadow: 0 15px 30px -10px rgba(74, 14, 23, 0.08) !important;
    }

    .crown-icon {
        font-size: 1.85rem;
    }
    .crown-1 { color: #ca8a04; filter: drop-shadow(0 2px 4px rgba(234, 179, 8, 0.4)); }
    .crown-2 { color: #64748b; }
    .crown-3 { color: #c2410c; }

    /* Rank Pill Indicators */
    .rank-pill {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 800;
        font-size: 0.875rem;
        box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6);
    }
    .rank-1 { background: linear-gradient(135deg, #fef08a, #eab308); color: #422006; border: 1px solid rgba(234, 179, 8, 0.5); }
    .rank-2 { background: linear-gradient(135deg, #f1f5f9, #cbd5e1); color: #0f172a; border: 1px solid #94a3b8; }
    .rank-3 { background: linear-gradient(135deg, #ffedd5, #fb923c); color: #431407; border: 1px solid #f97316; }
    .rank-default { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

    /* Student Avatars */
    .avatar-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.95rem;
    }

    .avatar-circle.avatar-photo {
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, .95);
        box-shadow: 0 6px 18px rgba(15, 23, 42, .16);
    }

    .admin-dashboard .podium-card,
    .admin-dashboard .admin-card {
        box-shadow: 0 18px 36px -18px rgba(74, 14, 23, .16) !important;
    }

    .admin-dashboard tbody tr {
        transition: background-color .18s ease, transform .18s ease;
    }

    .admin-dashboard tbody tr:hover {
        background: #fffaf0;
        transform: scale(1.002);
    }

    /* Tracking typography */
    .tracking-widest-caps {
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 800;
    }
</style>
@endpush

@section('content')
<div class="container pt-2 pb-5 admin-dashboard animate-entrance">
    
    {{-- Header Section (Flushed UP to remove gap) --}}
    <div class="mb-4 pb-3 border-bottom">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-1 fw-bold tracking-widest-caps shadow-2xs">
                <i class="bi bi-trophy-fill me-1 text-warning"></i> Department Standings
            </span>
        </div>
        <h1 class="h2 fw-extrabold text-slate-900 mb-1 tracking-tight">Department Leaderboard</h1>
        <p class="text-secondary small mb-0 font-medium">Top performing students ranked by total gamification points earned.</p>
    </div>

    @if($leaders->isEmpty())
        {{-- Empty State Card --}}
        <div class="card border-0 shadow-sm rounded-4 text-center py-6 my-4 bg-white" style="border-left: 6px solid var(--lemu-burgundy) !important;">
            <div class="card-body py-5">
                <div class="avatar-circle bg-warning-subtle text-warning mx-auto mb-3 shadow-sm" style="width: 68px; height: 68px; font-size: 1.85rem; border: 1px solid rgba(234, 179, 8, 0.3);">
                    <i class="bi bi-trophy"></i>
                </div>
                <h3 class="h5 fw-bold text-slate-900 mb-2">No Leaderboard Activity Yet</h3>
                <p class="text-secondary small mb-0 mx-auto" style="max-width: 420px;">
                    Students in your department haven't accumulated any points. Points will automatically tally as students complete quizzes and modules.
                </p>
            </div>
        </div>
    @else

        {{-- TOP 3 PODIUM SPOTLIGHT (3D FLOATING CARDS) --}}
        @php
            $topThree = $leaders->take(3);
        @endphp

        <div class="row g-3 mb-5 align-items-end podium-scene">
            {{-- 2nd Place --}}
            @if($second = $topThree->get(1))
            <div class="col-md-4 order-2 order-md-1">
                <div class="card podium-card podium-2 p-3.5 text-center">
                    <div class="mb-2">
                        <i class="bi bi-award-fill crown-icon crown-2"></i>
                    </div>
                    @if($second->profile_picture_path)
                        <img src="{{ asset('storage/'.$second->profile_picture_path) }}" class="avatar-circle avatar-photo mx-auto mb-2" style="width: 56px; height: 56px;" alt="{{ $second->name }}">
                    @else
                        <div class="avatar-circle bg-slate-800 text-white mx-auto mb-2 fw-bold shadow-sm" style="width: 56px; height: 56px; font-size: 1.1rem;">
                            {{ strtoupper(substr($second->name, 0, 2)) }}
                        </div>
                    @endif
                    <span class="badge bg-white text-secondary border px-2.5 py-0.5 rounded-pill tracking-widest-caps mb-2 shadow-2xs d-inline-block">2nd Place</span>
                    <h2 class="h6 fw-bold mb-0 text-slate-900 text-truncate">
                        <a href="{{ route('admin.students.show', $second) }}" class="text-decoration-none text-slate-900 stretched-link">
                            {{ $second->name }}
                        </a>
                    </h2>
                    <small class="text-muted d-block font-monospace mb-2.5" style="font-size: 0.75rem;">{{ $second->student_id_number ?: 'ID Unassigned' }}</small>
                    <div>
                        <span class="badge bg-white text-slate-900 border px-3 py-1.5 rounded-pill fw-bold shadow-sm tabular-nums">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>{{ number_format($second->total_points) }} pts
                        </span>
                    </div>
                </div>
            </div>
            @endif

            {{-- 1st Place --}}
            @if($first = $topThree->get(0))
            <div class="col-md-4 order-1 order-md-2">
                <div class="card podium-card podium-1 p-4 text-center">
                    <div class="mb-2">
                        <i class="bi bi-trophy-fill crown-icon crown-1"></i>
                    </div>
                    @if($first->profile_picture_path)
                        <img src="{{ asset('storage/'.$first->profile_picture_path) }}" class="avatar-circle avatar-photo mx-auto mb-2" style="width: 68px; height: 68px;" alt="{{ $first->name }}">
                    @else
                        <div class="avatar-circle bg-warning text-dark mx-auto mb-2 fw-bold shadow-lg" style="width: 68px; height: 68px; font-size: 1.3rem; border: 3px solid rgba(255,255,255,0.9);">
                            {{ strtoupper(substr($first->name, 0, 2)) }}
                        </div>
                    @endif
                    <span class="badge bg-dark text-warning border border-warning-subtle rounded-pill px-3 py-1 tracking-widest-caps mb-2 shadow-sm d-inline-block">👑 Champion</span>
                    <h2 class="h5 fw-extrabold mb-0 text-slate-900 text-truncate">
                        <a href="{{ route('admin.students.show', $first) }}" class="text-decoration-none text-slate-900 stretched-link">
                            {{ $first->name }}
                        </a>
                    </h2>
                    <small class="text-slate-600 d-block font-monospace mb-3" style="font-size: 0.78rem;">{{ $first->student_id_number ?: 'ID Unassigned' }}</small>
                    <div>
                        <span class="badge bg-dark text-white px-4 py-2 rounded-pill fw-extrabold shadow-lg tabular-nums fs-6">
                            <i class="bi bi-star-fill text-warning me-1.5"></i>{{ number_format($first->total_points) }} <span class="text-warning-subtle fw-medium">pts</span>
                        </span>
                    </div>
                </div>
            </div>
            @endif

            {{-- 3rd Place --}}
            @if($third = $topThree->get(2))
            <div class="col-md-4 order-3 order-md-3">
                <div class="card podium-card podium-3 p-3.5 text-center">
                    <div class="mb-2">
                        <i class="bi bi-award-fill crown-icon crown-3"></i>
                    </div>
                    @if($third->profile_picture_path)
                        <img src="{{ asset('storage/'.$third->profile_picture_path) }}" class="avatar-circle avatar-photo mx-auto mb-2" style="width: 56px; height: 56px;" alt="{{ $third->name }}">
                    @else
                        <div class="avatar-circle bg-orange-600 text-white mx-auto mb-2 fw-bold shadow-sm" style="width: 56px; height: 56px; font-size: 1.1rem;">
                            {{ strtoupper(substr($third->name, 0, 2)) }}
                        </div>
                    @endif
                    <span class="badge bg-white text-orange-700 border px-2.5 py-0.5 rounded-pill tracking-widest-caps mb-2 shadow-2xs d-inline-block">3rd Place</span>
                    <h2 class="h6 fw-bold mb-0 text-slate-900 text-truncate">
                        <a href="{{ route('admin.students.show', $third) }}" class="text-decoration-none text-slate-900 stretched-link">
                            {{ $third->name }}
                        </a>
                    </h2>
                    <small class="text-muted d-block font-monospace mb-2.5" style="font-size: 0.75rem;">{{ $third->student_id_number ?: 'ID Unassigned' }}</small>
                    <div>
                        <span class="badge bg-white text-slate-900 border px-3 py-1.5 rounded-pill fw-bold shadow-sm tabular-nums">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>{{ number_format($third->total_points) }} pts
                        </span>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- MAIN LEADERBOARD TABLE --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden admin-card">
            <div class="card-header bg-white border-bottom py-3.5 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 fw-bold mb-0 text-slate-900 tracking-tight">All Student Rankings</h2>
                    <small class="text-secondary">Fully synchronized performance metrics</small>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold small">
                    Showing {{ $leaders->count() }} students
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle border-bottom">
                        <tr class="text-uppercase text-secondary tracking-widest-caps">
                            <th class="ps-4 py-3.5 fw-bold" style="width: 90px;">Rank</th>
                            <th class="py-3.5 fw-bold">Student Profile</th>
                            <th class="py-3.5 fw-bold">Student ID</th>
                            <th class="text-end pe-4 py-3.5 fw-bold">Total Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($leaders as $leader)
                            @php
                                $rank = $loop->iteration;
                                $rankClass = match($rank) {
                                    1 => 'rank-1',
                                    2 => 'rank-2',
                                    3 => 'rank-3',
                                    default => 'rank-default'
                                };
                            @endphp
                            <tr>
                                {{-- Rank --}}
                                <td class="ps-4 py-3">
                                    <span class="rank-pill {{ $rankClass }}">
                                        {{ $rank }}
                                    </span>
                                </td>

                                {{-- Student Info --}}
                                <td class="py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        @if($leader->profile_picture_path)
                                            <img src="{{ asset('storage/'.$leader->profile_picture_path) }}" class="avatar-circle avatar-photo" alt="{{ $leader->name }}">
                                        @else
                                            <div class="avatar-circle bg-primary-subtle text-primary fw-bold shadow-2xs">
                                                {{ strtoupper(substr($leader->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <a class="fw-bold text-slate-900 text-decoration-none d-block lh-sm hover-primary" href="{{ route('admin.students.show', $leader) }}">
                                                {{ $leader->name }}
                                            </a>
                                            <small class="text-secondary" style="font-size: 0.8rem;">{{ $leader->email }}</small>
                                        </div>
                                    </div>
                                </td>

                                {{-- Student ID --}}
                                <td class="py-3">
                                    @if($leader->student_id_number)
                                        <span class="badge bg-white text-slate-700 border font-monospace px-2.5 py-1.5 rounded-2 fw-semibold shadow-2xs">
                                            {{ $leader->student_id_number }}
                                        </span>
                                    @else
                                        <span class="text-muted small fst-italic">Unassigned</span>
                                    @endif
                                </td>

                                {{-- Points --}}
                                <td class="text-end pe-4 py-3">
                                    <span class="fw-bold text-success fs-6 tabular-nums">
                                        <i class="bi bi-star-fill text-warning me-1 small"></i>{{ number_format($leader->total_points) }}
                                    </span>
                                    <small class="text-secondary ms-1 fw-medium">pts</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/admin.js') }}"></script>
@endpush