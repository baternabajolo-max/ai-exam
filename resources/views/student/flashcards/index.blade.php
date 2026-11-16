@extends('layouts.app')

@push('styles')
<style>
    /* ==========================================================
       SURFACE & BASE TOKENS
    ========================================================== */
    .flashcard-workspace {
        background-color: #f8fafc;
        min-height: calc(100vh - 72px);
        color: #0f172a;
    }

    /* ==========================================================
       3D FLIP ARCHITECTURE
    ========================================================== */
    .fc-scene {
        height: 270px;
        width: 100%;
        perspective: 1200px;
        position: relative;
    }

    .fc-card {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 1.25rem;
        transform-style: preserve-3d;
        -webkit-transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
        cursor: pointer;
        user-select: none;
        outline: none;
    }

    .fc-card:focus-visible {
        box-shadow: 0 0 0 3px #2563eb, 0 10px 25px -5px rgba(15, 23, 42, 0.15) !important;
    }

    /* Flip triggering via hover or active class for touch/click */
    .fc-scene:hover .fc-card,
    .fc-card.is-flipped {
        transform: rotateY(180deg);
        box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.12), 0 10px 15px -3px rgba(15, 23, 42, 0.05);
    }

    /* Card Faces */
    .fc-face {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        padding: 1.75rem 1.5rem 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        border-radius: 1.25rem;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .fc-front {
        transform: rotateY(0deg);
        -webkit-transform: rotateY(0deg);
    }

    .fc-back {
        transform: rotateY(180deg);
        -webkit-transform: rotateY(180deg);
    }

    .fc-content-body {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        overflow-y: auto;
        padding: 0.5rem 0;
        scrollbar-width: thin;
    }

    /* ==========================================================
       THEMED PALETTES
    ========================================================== */
    .fc-theme-blue .fc-face {
        background: radial-gradient(circle at 10% 20%, #eff6ff 0%, #dbeafe 100%);
        border-color: #bfdbfe;
    }
    .fc-theme-blue .fc-badge { background: #dbeafe; color: #1e40af; }

    .fc-theme-green .fc-face {
        background: radial-gradient(circle at 10% 20%, #f0fdf4 0%, #dcfce7 100%);
        border-color: #bbf7d0;
    }
    .fc-theme-green .fc-badge { background: #dcfce7; color: #166534; }

    .fc-theme-red .fc-face {
        background: radial-gradient(circle at 10% 20%, #fef2f2 0%, #fee2e2 100%);
        border-color: #fecaca;
    }
    .fc-theme-red .fc-badge { background: #fee2e2; color: #991b1b; }

    .fc-theme-yellow .fc-face {
        background: radial-gradient(circle at 10% 20%, #fefce8 0%, #fef08a 100%);
        border-color: #fde047;
    }
    .fc-theme-yellow .fc-badge { background: #fef08a; color: #854d0e; }

    .fc-theme-pink .fc-face {
        background: radial-gradient(circle at 10% 20%, #fdf2f8 0%, #fce7f3 100%);
        border-color: #fbcfe8;
    }
    .fc-theme-pink .fc-badge { background: #fce7f3; color: #9d174d; }

    /* Floating Quick Actions */
    .fc-actions-overlay {
        position: absolute;
        top: 0.85rem;
        right: 0.85rem;
        z-index: 10;
        display: flex;
        gap: 0.35rem;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        padding: 0.3rem;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04);
        opacity: 0.85;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .fc-scene:hover .fc-actions-overlay,
    .fc-actions-overlay:focus-within {
        opacity: 1;
        transform: translateY(-1px);
    }

    .fc-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: #ffffff;
        color: #475569;
        font-size: 0.82rem;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .fc-action-btn:hover {
        background: #f8fafc;
        color: #0f172a;
        transform: scale(1.05);
    }

    .fc-action-btn.delete-btn:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    /* Modal Form Swatch Selectors */
    .color-swatch-radio {
        display: none;
    }

    .color-swatch-label {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid transparent;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }

    .color-swatch-label:hover {
        transform: scale(1.1);
    }

    .color-swatch-radio:checked + .color-swatch-label {
        border-color: #0f172a;
        transform: scale(1.1);
    }

    .color-swatch-radio:checked + .color-swatch-label::after {
        content: '\F272';
        font-family: 'bootstrap-icons';
        font-size: 0.85rem;
        color: #0f172a;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="flashcard-workspace py-4 py-md-5">
    <div class="container">

        {{-- Top Header --}}
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h3 fw-bold mb-0 text-slate-900 tracking-tight">My Flashcards</h1>
                    <span class="badge bg-white border text-secondary rounded-pill px-2.5 py-1 small fw-semibold">
                        {{ method_exists($flashcards, 'total') ? $flashcards->total() : $flashcards->count() }} Cards
                    </span>
                </div>
                <p class="text-secondary small mb-0">Hover or click any card to flip and test your recall.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-secondary bg-white rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2"
                   href="{{ route('student.flashcards.print') }}"
                   target="_blank"
                   title="Print current flashcard set">
                    <i class="bi bi-printer text-slate-600"></i>
                    <span>Print Set</span>
                </a>

                <button class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2"
                        type="button"
                        id="open-create-modal"
                        data-bs-toggle="modal"
                        data-bs-target="#flashcardModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Create Flashcard</span>
                </button>
            </div>
        </header>

        {{-- Search & Filtering Controls --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-5">
                <form method="GET" action="{{ route('student.flashcards.index') }}">
                    <div class="position-relative">
                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="pointer-events: none;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="search"
                            class="form-control rounded-pill border shadow-sm ps-5 pe-4 bg-white"
                            style="padding-top: 0.65rem; padding-bottom: 0.65rem;"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search terms, answers, or notes..."
                            aria-label="Search flashcards">
                        @if(!empty($search))
                            <a href="{{ route('student.flashcards.index') }}" class="position-absolute top-50 end-0 translate-middle-y me-3 text-secondary text-decoration-none small fw-semibold" title="Clear search">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Flashcard Grid --}}
        <div class="row g-4">
            @forelse($flashcards as $flashcard)
                <div class="col-md-6 col-lg-4">
                    <div class="fc-scene">
                        
                        {{-- Floating Quick Action Toolbar --}}
                        <div class="fc-actions-overlay">
                            <button
                                class="fc-action-btn edit-flashcard"
                                type="button"
                                title="Edit flashcard"
                                aria-label="Edit card"
                                data-id="{{ $flashcard->id }}"
                                data-front="{{ e($flashcard->front) }}"
                                data-back="{{ e($flashcard->back) }}"
                                data-description="{{ e($flashcard->description ?? '') }}"
                                data-color="{{ $flashcard->color ?? 'blue' }}"
                                data-action="{{ route('student.flashcards.update', $flashcard) }}">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </button>

                            <form
                                method="POST"
                                action="{{ route('student.flashcards.destroy', $flashcard) }}"
                                class="d-inline delete-form"
                                data-confirm-title="Delete this flashcard?"
                                data-confirm-text="This action cannot be undone."
                                data-confirm-button="Delete flashcard"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="fc-action-btn delete-btn" type="submit" title="Delete flashcard" aria-label="Delete card">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </form>
                        </div>

                        {{-- 3D Interactive Card --}}
                        <div class="fc-card fc-theme-{{ $flashcard->color ?? 'blue' }}" tabindex="0" role="button" aria-label="Flashcard: {{ $flashcard->front }}. Press Enter or Space to flip.">
                            
                            {{-- FRONT FACE --}}
                            <div class="fc-face fc-front">
                                <div class="w-100 d-flex justify-content-between align-items-center">
                                    <span class="badge fc-badge rounded-pill px-2.5 py-1 text-uppercase tracking-wider fw-bold" style="font-size: 0.65rem;">
                                        Front · Question
                                    </span>
                                </div>

                                <div class="fc-content-body">
                                    <h2 class="h5 fw-bold mb-0 text-slate-900 lh-sm">{{ $flashcard->front }}</h2>
                                </div>

                                <div class="w-100 pt-2 border-top border-black-subtle border-opacity-10 d-flex align-items-center justify-content-between text-secondary" style="font-size: 0.72rem;">
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-arrow-repeat text-primary"></i>
                                        <span>Click or hover to flip</span>
                                    </span>
                                    @if(!empty($flashcard->description))
                                        <span title="Contains notes"><i class="bi bi-card-text"></i></span>
                                    @endif
                                </div>
                            </div>

                            {{-- BACK FACE --}}
                            <div class="fc-face fc-back">
                                <div class="w-100 d-flex justify-content-between align-items-center">
                                    <span class="badge fc-badge rounded-pill px-2.5 py-1 text-uppercase tracking-wider fw-bold" style="font-size: 0.65rem;">
                                        Back · Answer
                                    </span>
                                </div>

                                <div class="fc-content-body flex-column">
                                    <p class="mb-0 fs-6 fw-semibold text-slate-900 lh-sm">
                                        {{ $flashcard->back ?: 'No definition provided.' }}
                                    </p>
                                    @if(!empty($flashcard->description))
                                        <p class="mt-2 text-secondary mb-0 small fst-italic">
                                            {{ $flashcard->description }}
                                        </p>
                                    @endif
                                </div>

                                <div class="w-100 pt-2 border-top border-black-subtle border-opacity-10 d-flex align-items-center justify-content-center text-secondary" style="font-size: 0.72rem;">
                                    <span>Reverse side</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 py-5 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center p-4 rounded-4 bg-white shadow-sm border mb-3">
                        <i class="bi bi-card-checklist text-primary" style="font-size: 2.75rem;"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-800 mb-1">No flashcards found</h3>
                    <p class="text-secondary small mb-3 mx-auto" style="max-width: 380px;">
                        @if(!empty($search))
                            No flashcards matched your query "{{ $search }}". Try clearing your search term.
                        @else
                            Start building your deck to retain terms, formulas, and department notes efficiently.
                        @endif
                    </p>
                    @if(!empty($search))
                        <a href="{{ route('student.flashcards.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear Search</a>
                    @else
                        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#flashcardModal">
                            <i class="bi bi-plus-lg me-1"></i> Create Your First Card
                        </button>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if(method_exists($flashcards, 'links'))
            <div class="mt-5 d-flex justify-content-center">
                {{ $flashcards->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL: CREATE / EDIT FLASHCARD WITH LIVE PREVIEW --}}
<div class="modal fade" id="flashcardModal" tabindex="-1" aria-labelledby="flashcard-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="flashcard-form" method="POST" action="{{ route('student.flashcards.store') }}">
                @csrf
                <div id="flashcard-method"></div>

                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <div>
                        <h2 class="h5 fw-bold text-slate-900 mb-0" id="flashcard-modal-title">Create Flashcard</h2>
                        <small class="text-secondary">Capture a key term and verify its appearance in real time.</small>
                    </div>
                    <button class="btn-close shadow-none" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="row g-4 align-items-center">
                        
                        {{-- LEFT COLUMN: INPUT CONTROLS --}}
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-slate-700" for="card-front">
                                        Front: Term or Question <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="card-front" class="form-control rounded-3 border bg-light focus-ring" name="front" rows="3" placeholder="e.g. What is Polymorphism?" required></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold small text-slate-700" for="card-back">
                                        Back: Definition or Explanation
                                    </label>
                                    <textarea id="card-back" class="form-control rounded-3 border bg-light focus-ring" name="back" rows="3" placeholder="e.g. The ability of different classes to respond to the same message in different ways..."></textarea>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label fw-bold small text-slate-700" for="card-desc">
                                        Additional Context / Mnemonics (Optional)
                                    </label>
                                    <input type="text" id="card-desc" class="form-control rounded-3 border bg-light" name="description" placeholder="e.g. Remember OOP pillars: A-P-I-E">
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label fw-bold small text-slate-700 d-block">Deck Color Theme</label>
                                    <div class="d-flex align-items-center gap-2 pt-1">
                                        <label>
                                            <input type="radio" name="color" value="blue" class="color-swatch-radio" checked>
                                            <span class="color-swatch-label shadow-sm" style="background: #bfdbfe;" title="Blue"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="green" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #bbf7d0;" title="Green"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="yellow" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #fef08a;" title="Yellow"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="red" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #fecaca;" title="Red"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="pink" class="color-swatch-radio">
                                            <span class="color-swatch-label shadow-sm" style="background: #fbcfe8;" title="Pink"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN: LIVE 3D PREVIEW CARD --}}
                        <div class="col-lg-5">
                            <div class="p-3 bg-light rounded-4 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.68rem; letter-spacing: 0.05em;">Live Preview</span>
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-primary p-0 small fw-semibold" id="toggle-preview-flip">
                                        <i class="bi bi-arrow-repeat me-1"></i> Flip Card
                                    </button>
                                </div>

                                <div class="fc-scene" style="height: 250px;">
                                    <div class="fc-card fc-theme-blue" id="preview-card" tabindex="0" role="button" aria-label="Interactive live preview card">
                                        
                                        {{-- PREVIEW FRONT --}}
                                        <div class="fc-face fc-front">
                                            <div class="w-100 d-flex justify-content-between align-items-center">
                                                <span class="badge fc-badge rounded-pill px-2.5 py-1 text-uppercase tracking-wider fw-bold" style="font-size: 0.65rem;">
                                                    Front · Question
                                                </span>
                                            </div>

                                            <div class="fc-content-body">
                                                <h2 class="h5 fw-bold mb-0 text-slate-900 lh-sm" id="preview-front-text">Your question will appear here...</h2>
                                            </div>

                                            <div class="w-100 pt-2 border-top border-black-subtle border-opacity-10 d-flex align-items-center justify-content-between text-secondary" style="font-size: 0.72rem;">
                                                <span class="d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-hand-index-thumb text-primary"></i>
                                                    <span>Click or hover to flip</span>
                                                </span>
                                                <span id="preview-has-notes" class="d-none" title="Contains notes"><i class="bi bi-card-text"></i></span>
                                            </div>
                                        </div>

                                        {{-- PREVIEW BACK --}}
                                        <div class="fc-face fc-back">
                                            <div class="w-100 d-flex justify-content-between align-items-center">
                                                <span class="badge fc-badge rounded-pill px-2.5 py-1 text-uppercase tracking-wider fw-bold" style="font-size: 0.65rem;">
                                                    Back · Answer
                                                </span>
                                            </div>

                                            <div class="fc-content-body flex-column">
                                                <p class="mb-0 fs-6 fw-semibold text-slate-900 lh-sm" id="preview-back-text">
                                                    Your answer or definition will appear here...
                                                </p>
                                                <p class="mt-2 text-secondary mb-0 small fst-italic d-none" id="preview-desc-text"></p>
                                            </div>

                                            <div class="w-100 pt-2 border-top border-black-subtle border-opacity-10 d-flex align-items-center justify-content-center text-secondary" style="font-size: 0.72rem;">
                                                <span>Reverse side</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button class="btn btn-outline-secondary rounded-pill px-4 fw-medium bg-white" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" type="submit">Save Flashcard</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /*
         * Flip regular cards on Click & Keyboard
         */
        document.querySelectorAll('.fc-card:not(#preview-card)').forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.fc-actions-overlay')) return;
                this.classList.toggle('is-flipped');
            });

            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.classList.toggle('is-flipped');
                }
            });
        });

        /*
         * Isolate Action Clicks
         */
        document.querySelectorAll('.fc-actions-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        /*
         * Live Preview Elements & Logic
         */
        const previewCard = document.getElementById('preview-card');
        const previewFront = document.getElementById('preview-front-text');
        const previewBack = document.getElementById('preview-back-text');
        const previewDesc = document.getElementById('preview-desc-text');
        const previewHasNotes = document.getElementById('preview-has-notes');
        const toggleFlipBtn = document.getElementById('toggle-preview-flip');

        const inputFront = document.getElementById('card-front');
        const inputBack = document.getElementById('card-back');
        const inputDesc = document.getElementById('card-desc');
        const colorRadios = document.querySelectorAll('input[name="color"]');

        function updateLivePreview() {
            if (!previewCard) return;

            // Front
            const frontVal = inputFront.value.trim();
            previewFront.textContent = frontVal || 'Your question will appear here...';

            // Back
            const backVal = inputBack.value.trim();
            previewBack.textContent = backVal || 'Your answer or definition will appear here...';

            // Notes / Description
            const descVal = inputDesc.value.trim();
            if (descVal) {
                previewDesc.textContent = descVal;
                previewDesc.classList.remove('d-none');
                previewHasNotes.classList.remove('d-none');
            } else {
                previewDesc.textContent = '';
                previewDesc.classList.add('d-none');
                previewHasNotes.classList.add('d-none');
            }

            // Color Theme
            const checkedRadio = document.querySelector('input[name="color"]:checked');
            const checkedColor = checkedRadio ? checkedRadio.value : 'blue';
            const isFlipped = previewCard.classList.contains('is-flipped');
            previewCard.className = 'fc-card fc-theme-' + checkedColor + (isFlipped ? ' is-flipped' : '');
        }

        // Attach real-time input listeners
        if (inputFront) inputFront.addEventListener('input', updateLivePreview);
        if (inputBack) inputBack.addEventListener('input', updateLivePreview);
        if (inputDesc) inputDesc.addEventListener('input', updateLivePreview);
        colorRadios.forEach(function(radio) {
            radio.addEventListener('change', updateLivePreview);
        });

        // Flip preview on click or button click
        if (previewCard) {
            previewCard.addEventListener('click', function() {
                this.classList.toggle('is-flipped');
            });
            previewCard.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.classList.toggle('is-flipped');
                }
            });
        }

        if (toggleFlipBtn && previewCard) {
            toggleFlipBtn.addEventListener('click', function(e) {
                e.preventDefault();
                previewCard.classList.toggle('is-flipped');
            });
        }

        /*
         * Edit Modal Setup (Pre-populates inputs + updates live preview)
         */
        document.querySelectorAll('.edit-flashcard').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const form = document.getElementById('flashcard-form');
                const modal = document.getElementById('flashcardModal');

                form.action = button.dataset.action;
                document.getElementById('flashcard-method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                document.getElementById('flashcard-modal-title').textContent = 'Edit Flashcard';

                inputFront.value = button.dataset.front || '';
                inputBack.value = button.dataset.back || '';
                inputDesc.value = button.dataset.description || '';

                const selectedColor = button.dataset.color || 'blue';
                const colorRadio = document.querySelector('input[name="color"][value="' + selectedColor + '"]');
                if (colorRadio) {
                    colorRadio.checked = true;
                }

                if (previewCard) previewCard.classList.remove('is-flipped');
                updateLivePreview();

                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        });

        /*
         * Create Modal Setup (Resets form + resets live preview)
         */
        const createModalBtn = document.getElementById('open-create-modal');
        if (createModalBtn) {
            createModalBtn.addEventListener('click', function() {
                const form = document.getElementById('flashcard-form');
                form.action = "{{ route('student.flashcards.store') }}";
                document.getElementById('flashcard-method').innerHTML = '';
                document.getElementById('flashcard-modal-title').textContent = 'Create Flashcard';
                form.reset();

                const defaultColor = document.querySelector('input[name="color"][value="blue"]');
                if (defaultColor) {
                    defaultColor.checked = true;
                }

                if (previewCard) previewCard.classList.remove('is-flipped');
                updateLivePreview();
            });
        }
    });
</script>
@endpush