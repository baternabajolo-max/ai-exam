@extends('layouts.app')

@section('content')

<style>
    :root {
        --slate-900: #0F172A;
        --slate-800: #1E293B;
        --slate-700: #334155;
        --slate-500: #64748B;
        --slate-400: #94A3B8;
        --slate-200: #E2E8F0;
        --slate-100: #F1F5F9;
        --slate-50:  #F8FAFC;
        
        --indigo-600: #4F46E5;
        --indigo-700: #4338CA;
        --indigo-50:  #EEF2FF;
        
        --emerald-600: #059669;
        --emerald-500: #10B981;
        --emerald-50:  #ECFDF5;
        
        --amber-500:   #F59E0B;
        --amber-600:   #D97706;
        --amber-50:    #FFFBEB;
    }

    body {
        background-color: #F4F7FB;
        color: var(--slate-800);
        font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif;
    }

    .achievement-page,
    .achievement-page h1,
    .achievement-page h2,
    .achievement-page h3 {
        font-family: "Inter", "Segoe UI", sans-serif;
    }

    .achievement-page {
        perspective: 1200px;
    }

    .achievement-hero {
        animation: heroReveal 600ms cubic-bezier(.2,.8,.2,1) both;
    }

    @keyframes heroReveal {
        from { opacity: 0; transform: translateY(18px) rotateX(-2deg); }
        to { opacity: 1; transform: translateY(0) rotateX(0); }
    }

    /* Typography Utilities */
    .font-display {
        letter-spacing: -0.03em;
    }
    .tabular-nums {
        font-variant-numeric: tabular-nums;
    }

    .achievement-page {
        max-width: 1180px;
    }

    .achievement-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(79, 70, 229, 0.08);
        border-radius: 24px;
        background:
            radial-gradient(circle at 92% 15%, rgba(129, 140, 248, 0.20), transparent 25%),
            linear-gradient(135deg, #FFFFFF 0%, #F7F8FF 100%);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
    }

    .achievement-hero::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: -85px;
        bottom: -115px;
        border-radius: 50%;
        background: rgba(79, 70, 229, 0.07);
    }

    .header-icon-badge {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        background: linear-gradient(135deg, #EEF2FF, #E0E7FF);
        color: var(--indigo-600);
    }

    .points-hud {
        border-radius: 18px;
        background: #FFFFFF;
        border: 1px solid var(--slate-200);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }

    /* Achievement Cards & Micro-interactions */
    .achievement-card {
        border-radius: 22px !important;
        border: 1px solid var(--slate-200) !important;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.055) !important;
        transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
        animation: cardReveal 500ms cubic-bezier(.2,.8,.2,1) both;
        cursor: pointer;
    }
    .achievement-card:nth-child(1) { animation-delay: 80ms; }
    .achievement-card:nth-child(2) { animation-delay: 140ms; }
    .achievement-card:nth-child(3) { animation-delay: 200ms; }
    .achievement-card:nth-child(4) { animation-delay: 260ms; }
    .achievement-card:nth-child(5) { animation-delay: 320ms; }

    @keyframes cardReveal {
        from { opacity: 0; transform: translateY(24px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .achievement-card:hover {
        transform: translateY(-7px) rotateX(1deg);
        border-color: #C7D2FE !important;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.09) !important;
    }

    /* 3D Badge Icon Spheres */
    .badge-sphere {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: transform 200ms ease;
        transform-style: preserve-3d;
        animation: badgeFloat 3.6s ease-in-out infinite;
    }

    .achievement-card:nth-child(2n) .badge-sphere { animation-delay: .45s; }

    @keyframes badgeFloat {
        0%, 100% { transform: translateY(0) rotate(-1deg); }
        50% { transform: translateY(-5px) rotate(1deg); }
    }

    .badge-details-trigger:hover .badge-sphere,
    .badge-details-trigger:focus-visible .badge-sphere {
        transform: translateY(-5px) scale(1.05) rotateY(8deg);
        filter: drop-shadow(0 16px 14px rgba(15, 23, 42, .16));
    }

    .badge-sphere-image {
        width: 104px;
        height: 104px;
        object-fit: contain;
        border-radius: 50%;
        filter: drop-shadow(0 8px 8px rgba(15, 23, 42, .12));
    }

    .badge-locked .badge-sphere-image {
        filter: grayscale(1);
        opacity: 0.72;
    }

    .badge-claimed {
        background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        border: 3px solid #6EE7B7;
        color: var(--emerald-600);
        box-shadow: 0 10px 20px -4px rgba(16, 185, 129, 0.25), inset 0 2px 0 #FFFFFF;
    }

    .badge-unlocked {
        background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
        border: 3px solid #FCD34D;
        color: var(--amber-600);
        box-shadow: 0 12px 24px -4px rgba(245, 158, 11, 0.35), inset 0 2px 0 #FFFFFF;
        animation: pulseUnlocked 2.5s infinite;
    }

    @keyframes pulseUnlocked {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.04); }
    }

    .badge-locked {
        background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
        border: 2px dashed var(--slate-300);
        color: var(--slate-400);
    }

    .achievement-card-status {
        min-height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .achievement-card-locked .badge-sphere-image {
        filter: grayscale(1);
        opacity: 0.68;
    }

    .achievement-action-button {
        min-height: 40px;
        border-radius: 12px !important;
    }

    .badge-details-modal .modal-content {
        border-radius: 24px !important;
    }

    .badge-modal-visual {
        min-height: 184px;
        border-radius: 18px;
        background: linear-gradient(135deg, #F8FAFC, #EEF2FF);
    }

    /* Action Buttons */
    .btn-claim-3d {
        background-color: var(--indigo-600);
        border-color: var(--indigo-600);
        color: #FFFFFF;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        transition: all 160ms ease;
    }
    .btn-claim-3d:hover {
        background-color: var(--indigo-700);
        border-color: var(--indigo-700);
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
    }

    .badge-details-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        color: inherit;
    }

    .badge-details-trigger:focus-visible {
        outline: 3px solid rgba(79, 70, 229, 0.35);
        outline-offset: 5px;
        border-radius: 999px;
    }

    /* Progress Track */
    .progress-track-custom {
        height: 8px;
        background-color: var(--slate-100);
        border: 1px solid var(--slate-200);
        border-radius: 9999px;
        overflow: hidden;
    }
</style>

<div class="container-xl achievement-page py-4 py-md-5">
    
    <!-- Header Section & Points HUD -->
    <header class="achievement-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 p-4 p-md-5 mb-4">
        <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 1;">
            <div class="header-icon-badge d-flex align-items-center justify-content-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--amber-600)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                    <path d="M4 22h16"/>
                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                </svg>
            </div>
            <div>
                <h1 class="h3 fw-extrabold font-display mb-1" style="color: var(--slate-900); font-weight: 800;">My Achievements</h1>
                <p class="text-muted mb-0 fw-medium">Complete learning milestones and build your collection.</p>
            </div>
        </div>

        <!-- Total Points Pill HUD -->
        <div class="points-hud px-4 py-3 d-inline-flex align-items-center gap-2 flex-shrink-0 position-relative" style="z-index: 1;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#10B981" stroke="#10B981" stroke-width="1">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Points:</span>
            <span class="fw-extrabold fs-5 tabular-nums" style="color: var(--emerald-600);">{{ number_format($user->total_points) }} <span class="fs-6 text-muted font-normal">pts</span></span>
        </div>
    </header>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Badge Collection</h2>
            <p class="text-muted small mb-0">{{ count($achievements) }} badges available</p>
        </div>
        <div class="small text-muted fw-semibold">
            {{ count(array_intersect($claimedIds, $achievements->pluck('id')->all())) }} claimed
        </div>
    </div>

    <!-- Achievements Grid -->
    <main class="row g-4">
        @forelse($achievements as $achievement)
            @php 
                $isUnlocked = in_array($achievement->id, $unlockedIds); 
                $isClaimed = in_array($achievement->id, $claimedIds); 
            @endphp
            
            <div class="col-md-6 col-lg-4">
                <article
                    class="card border-0 h-100 overflow-hidden achievement-card achievement-card-{{ $isClaimed ? 'claimed' : ($isUnlocked ? 'unlocked' : 'locked') }} bg-white"
                    role="button"
                    tabindex="0"
                    data-bs-toggle="modal"
                    data-bs-target="#achievement-modal-{{ $achievement->id }}"
                    aria-label="View {{ $achievement->title }} badge details"
                         style="border: 1px solid {{ $isClaimed ? '#A7F3D0' : ($isUnlocked ? '#FCD34D' : 'var(--slate-200)') }} !important;">
                    
                    <div class="card-body text-center p-4 d-flex flex-column">
                        
                        <div>
                            <!-- Badge Icon Container -->
                            <div class="mb-3.5">
                                <button
                                    type="button"
                                    class="badge-details-trigger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#achievement-modal-{{ $achievement->id }}"
                                    aria-label="View requirements for {{ $achievement->title }}"
                                >
                                <span class="badge-sphere {{ $isClaimed ? 'badge-claimed' : ($isUnlocked ? 'badge-unlocked' : 'badge-locked') }}">
                                    @if($achievement->badge_image)
                                        <img
                                            class="badge-sphere-image"
                                            src="{{ asset('storage/'.$achievement->badge_image) }}"
                                            alt="{{ $achievement->title }} badge"
                                            loading="lazy"
                                        >
                                    @else
                                        <i class="bi {{ $achievement->icon }} display-5"></i>
                                    @endif
                                </span>
                                </button>
                            </div>

                            <button
                                type="button"
                                class="badge-details-trigger d-block mx-auto"
                                data-bs-toggle="modal"
                                data-bs-target="#achievement-modal-{{ $achievement->id }}"
                                aria-label="View requirements for {{ $achievement->title }}"
                            >
                                <h2 class="h5 fw-bold mb-1.5" style="color: var(--slate-900);">{{ $achievement->title }}</h2>
                            </button>
                            <div class="achievement-card-status mb-3">
                                @if($isClaimed)
                                    <span class="badge rounded-pill px-3 py-2" style="background-color: var(--emerald-50); color: var(--emerald-600); border: 1px solid #A7F3D0;">Claimed</span>
                                @elseif($isUnlocked)
                                    <span class="badge rounded-pill px-3 py-2" style="background-color: var(--amber-50); color: var(--amber-600); border: 1px solid #FDE68A;">Ready to claim</span>
                                @else
                                    <span class="badge rounded-pill px-3 py-2 bg-light text-secondary border"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                                @endif
                            </div>
                        </div>

                        <!-- State Action / Progress -->
                        <div class="mt-auto">
                            @if($isClaimed)
                            @elseif($isUnlocked)
                                <form method="POST" action="{{ route('student.achievements.claim', $achievement) }}">
                                    @csrf
                                    <button class="btn btn-claim-3d achievement-action-button px-4 py-2 small w-100 d-inline-flex align-items-center justify-content-center gap-1.5 achievement-claim-button" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                                        </svg>
                                        <span>Claim Badge</span>
                                    </button>
                                </form>
                            @else
                            @endif
                        </div>

                    </div>
                </article>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="py-5">
                    <div class="p-3 rounded-circle d-inline-flex mb-3" style="background-color: var(--slate-100); color: var(--slate-400);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-1" style="color: var(--slate-900);">No Achievements Available</h5>
                    <p class="text-muted small mb-0">Check back later as new achievement milestones are added to the department platform.</p>
                </div>
            </div>
        @endforelse
    </main>
</div>

@foreach($achievements as $achievement)
    @php
        $isUnlocked = in_array($achievement->id, $unlockedIds);
        $isClaimed = in_array($achievement->id, $claimedIds);
    @endphp
    <div class="modal fade badge-details-modal" id="achievement-modal-{{ $achievement->id }}" tabindex="-1" aria-labelledby="achievement-modal-title-{{ $achievement->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-bottom-0 px-4 pt-4 pb-0">
                    <h2 class="modal-title h5 fw-bold" id="achievement-modal-title-{{ $achievement->id }}">{{ $achievement->title }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close badge details"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    @if($achievement->badge_image)
                        <div class="badge-modal-visual d-flex align-items-center justify-content-center mb-3 mx-auto">
                            <img src="{{ asset('storage/'.$achievement->badge_image) }}" alt="{{ $achievement->title }} badge" width="140" height="140">
                        </div>
                    @endif
                    <div>
                        <span class="badge rounded-pill mb-3 d-inline-block {{ $isClaimed ? 'bg-success-subtle text-success' : ($isUnlocked ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary') }}">
                            {{ $isClaimed ? 'Claimed' : ($isUnlocked ? 'Ready to claim' : 'Locked') }}
                        </span>
                    </div>
                    <h3 class="h6 fw-bold mb-2">How to earn this badge</h3>
                    <p class="text-muted mb-0 lh-lg">{{ $achievement->description }}</p>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.querySelectorAll('.achievement-claim-button').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    document.querySelectorAll('.achievement-card[role="button"]').forEach(function (card) {
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                bootstrap.Modal.getOrCreateInstance(document.querySelector(card.dataset.bsTarget)).show();
            }
        });
    });
</script>

@endsection