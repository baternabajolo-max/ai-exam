@extends('layouts.app')

@section('content')

@php
    // Filter out students who have 0 points and re-index the collection
    $rankedLeaders = collect($leaders)->filter(function($leader) {
        return $leader->total_points > 0;
    })->values();

    // Find the currently authenticated user's index in the filtered leaderboard
    $currentUserIndex = $rankedLeaders->search(function($leader) {
        return $leader->id === auth()->id();
    });
    $currentUser = $currentUserIndex !== false ? $rankedLeaders[$currentUserIndex] : null;
@endphp

<style>
    :root {
        --slate-900: #0F172A;
        --slate-800: #1E293B;
        --slate-700: #334155;
        --slate-500: #64748B;
        --slate-400: #94A3B8;
        --slate-300: #CBD5E1;
        --slate-200: #E2E8F0;
        --slate-100: #F1F5F9;
        --slate-50:  #F8FAFC;
        
        --indigo-600: #4F46E5;
        --indigo-700: #4338CA;
        --indigo-50:  #EEF2FF;
        
        --gold-500:   #F59E0B;
        --gold-600:   #D97706;
        --gold-100:   #FEF3C7;
        
        --silver-400: #94A3B8;
        --silver-500: #64748B;
        
        --bronze-600: #D97706;
        --bronze-700: #B45309;
    }

    body {
        background-color: var(--slate-50);
        color: var(--slate-800);
        font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif;
    }

    /* Typography Utilities */
    .font-display {
        font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", sans-serif;
        letter-spacing: -0.035em;
    }
    .tabular-nums {
        font-variant-numeric: tabular-nums;
    }

    /* 3D Header Icon Badge */
    .header-icon-badge {
        position: relative;
        width: 68px;
        height: 68px;
        background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
        border: 2px solid #C7D2FE;
        box-shadow: 0 12px 28px -6px rgba(79, 70, 229, 0.3), inset 0 2px 0 #FFFFFF;
        animation: floatBadge 3.5s ease-in-out infinite;
    }

    @keyframes floatBadge {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-6px); }
    }

    /* 3D Podium Mechanics */
    .podium-container {
        perspective: 1000px;
    }

    .podium-3d-card {
        transition: transform 250ms cubic-bezier(0.16, 1, 0.3, 1), filter 250ms ease;
        transform-style: preserve-3d;
    }

    .podium-3d-card:hover {
        transform: translateY(-6px) translateZ(8px);
    }

    /* 3D Extruded Pedestals */
    .pedestal-3d {
        position: relative;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.6), 0 12px 20px -4px rgba(15, 23, 42, 0.12);
    }

    .pedestal-gold {
        background: linear-gradient(180deg, #FEF08A 0%, #FACC15 60%, #EAB308 100%);
        border-top: 4px solid #FEF08A;
        box-shadow: 0 10px 0 #CA8A04, 0 20px 25px -5px rgba(234, 179, 8, 0.3);
    }

    .pedestal-silver {
        background: linear-gradient(180deg, #F1F5F9 0%, #E2E8F0 60%, #CBD5E1 100%);
        border-top: 4px solid #FFFFFF;
        box-shadow: 0 8px 0 #94A3B8, 0 16px 20px -4px rgba(148, 163, 184, 0.25);
    }

    .pedestal-bronze {
        background: linear-gradient(180deg, #FFEDD5 0%, #FED7AA 60%, #FDBA74 100%);
        border-top: 4px solid #FFEDD5;
        box-shadow: 0 8px 0 #EA580C, 0 16px 20px -4px rgba(234, 88, 12, 0.25);
    }

    /* 3D Floating Avatar Spheres */
    .avatar-3d-wrapper {
        position: relative;
        display: inline-block;
    }

    .avatar-glow-1 {
        box-shadow: 0 0 0 5px #FFFFFF, 0 12px 28px -4px rgba(245, 158, 11, 0.45);
    }
    .avatar-glow-2 {
        box-shadow: 0 0 0 4px #FFFFFF, 0 10px 22px -4px rgba(148, 163, 184, 0.35);
    }
    .avatar-glow-3 {
        box-shadow: 0 0 0 4px #FFFFFF, 0 10px 22px -4px rgba(217, 119, 6, 0.35);
    }

    /* Table Hover States */
    .leaderboard-table tr {
        transition: background-color 140ms ease;
    }
    .leaderboard-table tbody tr:hover {
        background-color: var(--slate-50);
    }

    .user-highlight-row {
        background-color: var(--indigo-50) !important;
        border-left: 4px solid var(--indigo-600) !important;
    }

    /* Fixed Contrast Sticky Bottom HUD */
    .user-rank-sticky-bar {
        position: sticky;
        bottom: 1.5rem;
        z-index: 1000;
        backdrop-filter: blur(12px);
        background-color: #0F172A !important;
        color: #FFFFFF !important;
        box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
    }

    .border-dashed-subtle {
        border: 2px dashed var(--slate-200) !important;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10B981;
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulseAnimation 2s infinite;
    }

    @keyframes pulseAnimation {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
</style>

<div class="container-xl pb-5">
    
    <!-- Header Title with Upgraded 3D Trophy Icon -->
    <header class="text-center mb-5">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle header-icon-badge mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="var(--indigo-600)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                <path d="M4 22h16"/>
                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
            </svg>
        </div>
        <h1 class="display-6 fw-extrabold font-display mb-2" style="color: var(--slate-900); font-weight: 800;">Department Leaderboard</h1>
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white border shadow-xs" style="border-color: var(--slate-200) !important;">
            <span class="pulse-dot"></span>
            <span class="text-muted small fw-semibold" style="font-size: 0.8rem; color: var(--slate-600);">Top Academic Performers & Points Ranking</span>
        </div>
    </header>

    <!-- Top 3 3D Podium Section -->
    <section class="row justify-content-center align-items-end mb-5 px-2 px-md-3 mx-auto podium-container" style="max-width: 660px;" aria-label="Top 3 Performers">
        
        <!-- 2nd Place Slot -->
        <div class="col-4 px-1 px-md-2 text-center podium-3d-card">
            @if(isset($rankedLeaders[1]))
                <div class="d-flex flex-column align-items-center">
                    <div class="avatar-3d-wrapper mb-2">
                        <img src="{{ $rankedLeaders[1]->profile_picture_path ? asset('storage/'.$rankedLeaders[1]->profile_picture_path) : 'https://ui-avatars.com/api/?name='.urlencode($rankedLeaders[1]->name).'&background=E2E8F0&color=475569&bold=true' }}" class="rounded-circle avatar-glow-2" width="74" height="74" style="object-fit: cover;" alt="{{ $rankedLeaders[1]->name }} Avatar">
                        <span class="position-absolute bottom-0 start-50 translate-middle-x badge rounded-pill border border-2 border-white shadow-sm px-2 py-0.5" style="background-color: var(--silver-500); font-size: 0.68rem; font-weight: 800; letter-spacing: 0.05em;">2ND</span>
                    </div>
                    <h2 class="h6 fw-bold text-truncate w-100 mb-0 px-1 mt-2" style="color: var(--slate-900);" title="{{ $rankedLeaders[1]->name }}">{{ $rankedLeaders[1]->name }}</h2>
                    <span class="fw-extrabold small mb-3 tabular-nums" style="color: var(--indigo-600);">{{ number_format($rankedLeaders[1]->total_points) }} pts</span>
                    
                    <div class="w-100 pedestal-3d pedestal-silver d-flex justify-content-center pt-3 position-relative overflow-hidden" style="height: 115px;">
                        <span class="display-5 font-display fw-extrabold text-secondary opacity-30 user-select-none">2</span>
                    </div>
                </div>
            @else
                <!-- Ghost 2nd Place -->
                <div class="d-flex flex-column align-items-center opacity-50">
                    <div class="rounded-circle bg-white border-dashed-subtle mb-2 d-flex align-items-center justify-content-center" style="width: 74px; height: 74px;">
                        <span class="text-muted fw-bold">2</span>
                    </div>
                    <span class="small fw-semibold text-muted mt-2">TBD</span>
                    <span class="small text-muted mb-3">0 pts</span>
                    <div class="w-100 rounded-top-4 bg-light border-top border-3" style="height: 115px; border-color: var(--slate-200) !important;"></div>
                </div>
            @endif
        </div>

        <!-- 1st Place Slot -->
        <div class="col-4 px-1 px-md-2 text-center podium-3d-card" style="z-index: 10;">
            @if(isset($rankedLeaders[0]))
                <div class="d-flex flex-column align-items-center">
                    <div class="mb-1 text-warning" style="filter: drop-shadow(0px 6px 8px rgba(245, 158, 11, 0.5)); transform: translateZ(15px);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 3h14b2 2 0 0 1 2 2v2a5 5 0 0 1-5 5h-.09a6 6 0 0 1-5.91 5.91V20h4a1 1 0 0 1 0 2H9a1 1 0 0 1 0-2h4v-2.09A6 6 0 0 1 7.09 12H7a5 5 0 0 1-5-5V5a2 2 0 0 1 2-2zm0 4v.09A3 3 0 0 0 7 10h.09a5.98 5.98 0 0 1 .91-2.91V5H5v2zm14 0V5h-3v2.09c.38.87.69 1.83.91 2.91H17a3 3 0 0 0 3-3z"/>
                        </svg>
                    </div>
                    <div class="avatar-3d-wrapper mb-2">
                        <img src="{{ $rankedLeaders[0]->profile_picture_path ? asset('storage/'.$rankedLeaders[0]->profile_picture_path) : 'https://ui-avatars.com/api/?name='.urlencode($rankedLeaders[0]->name).'&background=FEF3C7&color=B45309&bold=true' }}" class="rounded-circle avatar-glow-1" width="96" height="96" style="object-fit: cover;" alt="{{ $rankedLeaders[0]->name }} Avatar">
                        <span class="position-absolute bottom-0 start-50 translate-middle-x badge rounded-pill border border-2 border-white shadow-sm px-2.5 py-1 text-dark" style="background-color: var(--gold-500); font-size: 0.72rem; font-weight: 800; letter-spacing: 0.05em;">1ST</span>
                    </div>
                    <h2 class="h5 fw-extrabold font-display text-truncate w-100 mb-0 px-1 mt-2" style="color: var(--slate-900);" title="{{ $rankedLeaders[0]->name }}">{{ $rankedLeaders[0]->name }}</h2>
                    <span class="fw-extrabold small mb-3 tabular-nums" style="color: var(--indigo-600);">{{ number_format($rankedLeaders[0]->total_points) }} pts</span>
                    
                    <div class="w-100 pedestal-3d pedestal-gold d-flex justify-content-center pt-3 position-relative overflow-hidden" style="height: 155px;">
                        <span class="display-4 font-display fw-extrabold text-dark opacity-30 user-select-none">1</span>
                    </div>
                </div>
            @else
                <!-- Ghost 1st Place -->
                <div class="d-flex flex-column align-items-center opacity-50">
                    <div class="rounded-circle bg-white border-dashed-subtle mb-2 d-flex align-items-center justify-content-center" style="width: 96px; height: 96px;">
                        <span class="text-muted fw-bold fs-4">1</span>
                    </div>
                    <span class="small fw-semibold text-muted mt-2">TBD</span>
                    <span class="small text-muted mb-3">0 pts</span>
                    <div class="w-100 rounded-top-4 bg-light border-top border-3" style="height: 155px; border-color: var(--slate-200) !important;"></div>
                </div>
            @endif
        </div>

        <!-- 3rd Place Slot -->
        <div class="col-4 px-1 px-md-2 text-center podium-3d-card">
            @if(isset($rankedLeaders[2]))
                <div class="d-flex flex-column align-items-center">
                    <div class="avatar-3d-wrapper mb-2">
                        <img src="{{ $rankedLeaders[2]->profile_picture_path ? asset('storage/'.$rankedLeaders[2]->profile_picture_path) : 'https://ui-avatars.com/api/?name='.urlencode($rankedLeaders[2]->name).'&background=FFEDD5&color=C2410C&bold=true' }}" class="rounded-circle avatar-glow-3" width="74" height="74" style="object-fit: cover;" alt="{{ $rankedLeaders[2]->name }} Avatar">
                        <span class="position-absolute bottom-0 start-50 translate-middle-x badge rounded-pill text-white border border-2 border-white shadow-sm px-2 py-0.5" style="background-color: var(--bronze-600); font-size: 0.68rem; font-weight: 800; letter-spacing: 0.05em;">3RD</span>
                    </div>
                    <h2 class="h6 fw-bold text-truncate w-100 mb-0 px-1 mt-2" style="color: var(--slate-900);" title="{{ $rankedLeaders[2]->name }}">{{ $rankedLeaders[2]->name }}</h2>
                    <span class="fw-extrabold small mb-3 tabular-nums" style="color: var(--indigo-600);">{{ number_format($rankedLeaders[2]->total_points) }} pts</span>
                    
                    <div class="w-100 pedestal-3d pedestal-bronze d-flex justify-content-center pt-3 position-relative overflow-hidden" style="height: 95px;">
                        <span class="display-6 font-display fw-extrabold text-white opacity-40 user-select-none">3</span>
                    </div>
                </div>
            @else
                <!-- Ghost 3rd Place -->
                <div class="d-flex flex-column align-items-center opacity-50">
                    <div class="rounded-circle bg-white border-dashed-subtle mb-2 d-flex align-items-center justify-content-center" style="width: 74px; height: 74px;">
                        <span class="text-muted fw-bold">3</span>
                    </div>
                    <span class="small fw-semibold text-muted mt-2">TBD</span>
                    <span class="small text-muted mb-3">0 pts</span>
                    <div class="w-100 rounded-top-4 bg-light border-top border-3" style="height: 95px; border-color: var(--slate-200) !important;"></div>
                </div>
            @endif
        </div>

    </section>

    <!-- Full Leaderboard Table Container -->
    <main class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="border: 1px solid var(--slate-200) !important;">
        <div class="table-responsive">
            <table class="table leaderboard-table mb-0 align-middle text-center">
                <thead class="bg-light text-uppercase small border-bottom" style="letter-spacing: 0.06em; font-size: 0.72rem; color: var(--slate-500); font-weight: 700;">
                    <tr>
                        <th class="py-3.5 px-4 border-0 w-20 text-center">Rank</th>
                        <th class="py-3.5 px-4 text-start border-0">Student</th>
                        <th class="py-3.5 px-4 border-0 text-end w-25 pe-4">Mastery Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($rankedLeaders as $index => $leader)
                        @php
                            $isCurrentUser = $leader->id === auth()->id();
                        @endphp
                        {{-- Skip rendering top 3 inside table if present on podium --}}
                        @if($index > 2)
                            <tr class="{{ $isCurrentUser ? 'user-highlight-row fw-bold' : '' }}">
                                <td class="py-3 px-4 text-center">
                                    <span class="fw-bold small tabular-nums {{ $isCurrentUser ? 'text-indigo' : 'text-muted' }}" style="{{ $isCurrentUser ? 'color: var(--indigo-600);' : '' }}">
                                        #{{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-start">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $leader->profile_picture_path ? asset('storage/'.$leader->profile_picture_path) : 'https://ui-avatars.com/api/?name='.urlencode($leader->name).'&background=F1F5F9&color=334155&bold=true' }}" class="rounded-circle shadow-xs me-3 flex-shrink-0" width="40" height="40" style="object-fit: cover;" alt="{{ $leader->name }}">
                                        <div class="text-truncate" style="max-width: 320px;">
                                            <span class="fw-semibold d-block text-truncate" style="color: var(--slate-900);">{{ $leader->name }}</span>
                                            @if($isCurrentUser)
                                                <span class="badge rounded-pill fw-bold text-uppercase px-2" style="background-color: var(--indigo-600); color: #FFFFFF; font-size: 0.62rem; letter-spacing: 0.04em;">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-end pe-4">
                                    <span class="fw-extrabold tabular-nums font-display" style="color: var(--slate-900);">{{ number_format($leader->total_points) }}</span>
                                    <span class="text-muted small fw-medium"> pts</span>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-5">
                                <div class="py-4">
                                    <div class="p-3 rounded-circle d-inline-flex mb-3" style="background-color: var(--slate-100); color: var(--slate-500);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><path d="M8 12h8"/>
                                        </svg>
                                    </div>
                                    <h5 class="fw-bold mb-1" style="color: var(--slate-900);">No Points Awarded Yet</h5>
                                    <p class="text-muted small mb-0">Complete quizzes and assessments to start earning points for the leaderboard.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if(count($rankedLeaders) > 0 && count($rankedLeaders) <= 3)
                        <tr>
                            <td colspan="3" class="text-center py-4" style="color: var(--slate-500);">
                                <span class="small fw-semibold">All ranked students are currently featured on the Top 3 Podium above.</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </main>

    <!-- Fixed Contrast 3D Sticky Bottom HUD -->
    @if($currentUser)
        <aside class="user-rank-sticky-bar p-3.5 rounded-4 container-xl d-flex align-items-center justify-content-between" aria-label="Your Rank Overview">
            <div class="d-flex align-items-center gap-3">
                <span class="badge rounded-pill px-3 py-2 fw-extrabold shadow-sm" style="background-color: var(--indigo-600); color: #FFFFFF; font-size: 0.82rem; letter-spacing: 0.05em;">
                    RANK #{{ $currentUserIndex + 1 }}
                </span>
                @if($currentUser->profile_picture_path)
                    <img src="{{ asset('storage/'.$currentUser->profile_picture_path) }}" class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0" width="42" height="42" style="object-fit: cover;" alt="{{ $currentUser->name }}">
                @else
                    <span class="rounded-circle bg-light text-secondary border border-2 border-white shadow-sm d-inline-flex align-items-center justify-content-center flex-shrink-0 fw-bold" style="width: 42px; height: 42px;">
                        {{ strtoupper(substr($currentUser->name, 0, 2)) }}
                    </span>
                @endif
                <div class="d-none d-sm-block">
                    <span class="fw-bold text-white d-block small" style="letter-spacing: -0.01em;">{{ $currentUser->name }}</span>
                    <span class="d-block small" style="color: var(--slate-300); font-size: 0.78rem;">Your current position in the department</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="fs-4 fw-extrabold text-white tabular-nums font-display">{{ number_format($currentUser->total_points) }}</span>
                <span class="small fw-semibold" style="color: var(--slate-300);">Total Points</span>
            </div>
        </aside>
    @endif

</div>

@endsection