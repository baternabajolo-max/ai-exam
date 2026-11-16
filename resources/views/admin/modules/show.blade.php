@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/super-admin/modules.css') }}">
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

    .animate-entrance {
        animation: luxuryFadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Immersive Luxury Hero Banner (Flushed UP to remove gap) */
    .module-hero-banner {
        background: linear-gradient(135deg, #2c070e 0%, #4a0e17 50%, #0f172a 100%);
        color: #ffffff;
        padding: 3rem 0 2.5rem;
        margin-top: -1.5rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid rgba(234, 179, 8, 0.2);
        box-shadow: 0 15px 30px -10px rgba(74, 14, 23, 0.3);
    }

    /* 3D Floating Subtopic Cards */
    .subtopic-item {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.2) !important;
        border-radius: 1.25rem !important;
        padding: 1.25rem 1.5rem !important;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 8px 20px -4px rgba(74, 14, 23, 0.05);
        transform-style: preserve-3d;
    }

    .subtopic-item:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 15px 30px -6px rgba(74, 14, 23, 0.12), 0 0 15px rgba(234, 179, 8, 0.2);
        border-color: rgba(234, 179, 8, 0.6) !important;
    }

    .subtopic-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(234, 179, 8, 0.12);
        color: #ca8a04;
        font-size: 1.35rem;
        flex-shrink: 0;
        border: 1px solid rgba(234, 179, 8, 0.25);
    }

    /* Sidebar Panel */
    .sidebar-panel {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.2);
        border-radius: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(74, 14, 23, 0.06);
    }

    /* Burgundy & Gold Buttons */
    .btn-burgundy-gold {
        background: linear-gradient(135deg, #4a0e17 0%, #2c070e 100%);
        color: #fef08a;
        border: 1px solid rgba(234, 179, 8, 0.4);
        box-shadow: 0 4px 15px rgba(74, 14, 23, 0.25);
        transition: all 0.25s ease;
    }

    .btn-burgundy-gold:hover {
        background: linear-gradient(135deg, #5c121d, #380912);
        color: #ffffff;
        border-color: #eab308;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(234, 179, 8, 0.35);
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

{{-- Immersive Dark Burgundy Hero Banner --}}
<div class="module-hero-banner animate-entrance">
    <div class="container">
        <!-- Back Link -->
        <a href="{{ route('admin.modules.index') }}" class="text-decoration-none text-warning-subtle small fw-bold mb-3 d-inline-flex align-items-center gap-1.5 hover-text-white transition-hover">
            <i class="bi bi-arrow-left"></i> Back to all modules
        </a>

        <!-- Header Banner Grid -->
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h1 class="fw-extrabold display-6 mb-2 tracking-tight text-white">{{ $module->title }}</h1>
                <p class="text-light opacity-75 lead mb-0" style="font-size: 1.05rem; line-height: 1.6;">
                    {{ $module->description ?? 'No description provided for this module.' }}
                </p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                @if($module->logo_path)
                    <img src="{{ asset('storage/' . $module->logo_path) }}" alt="{{ $module->title }}" class="img-fluid rounded-4 shadow-lg border border-warning border-opacity-25" style="max-height: 160px; object-fit: cover;">
                @else
                    <div class="p-4 bg-black bg-opacity-25 rounded-4 text-warning d-inline-flex flex-column align-items-center justify-content-center border border-warning border-opacity-25" style="width: 140px; height: 140px;">
                        <i class="bi bi-shield-lock display-5"></i>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="container pb-5 animate-entrance">
    <div class="row g-4">
        <!-- Left Side: Subtopics List -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-slate-900 h5">List of Topics</h4>
                    <span class="text-secondary small">Organized curriculum materials and lab guides</span>
                </div>
                <button class="btn btn-burgundy-gold fw-bold btn-sm px-4 py-2 rounded-pill shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#addSubtopicModal">
                    <i class="bi bi-plus-lg text-warning"></i> Add Subtopic
                </button>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($module->subtopics as $subtopic)
                    <div class="subtopic-item d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="subtopic-icon-box">
                                <i class="bi bi-file-earmark-richtext"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-slate-900">{{ $subtopic->title }}</h6>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5 rounded-2 fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-paperclip me-1"></i> Uploaded Document
                                </span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center gap-1.5">
                            <a href="{{ asset('storage/' . $subtopic->file_path) }}" target="_blank" class="btn btn-sm btn-outline-dark px-3 rounded-pill fw-semibold d-inline-flex align-items-center gap-1 shadow-2xs">
                                <i class="bi bi-eye"></i> View File
                            </a>
                            
                            <button type="button" class="btn btn-sm btn-light border text-secondary rounded-circle p-2 d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 34px; height: 34px;" data-bs-toggle="modal" data-bs-target="#editSubtopicModal{{ $subtopic->id }}" title="Edit Subtopic">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <form
                                action="{{ route('admin.subtopics.destroy', $subtopic) }}"
                                method="POST"
                                class="d-inline delete-subtopic-form"
                                data-confirm-title="Delete this subtopic?"
                                data-confirm-text="The subtopic and its uploaded material will be permanently removed."
                                data-confirm-button="Delete subtopic"
                            >
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger rounded-circle p-2 d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 34px; height: 34px;" title="Delete Subtopic">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                @empty
                    <div class="card border-0 text-center py-5 bg-white shadow-sm rounded-4" style="border-left: 6px solid var(--lemu-burgundy) !important;">
                        <div class="card-body py-4">
                            <div class="display-6 text-warning mb-3 opacity-75">
                                <i class="bi bi-journal-x"></i>
                            </div>
                            <h5 class="fw-bold text-slate-900 mb-1">No Subtopics Added Yet</h5>
                            <p class="text-secondary small mb-0">Click the "Add Subtopic" button above to upload course materials and files.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Side: Need to Know Sidebar -->
        <div class="col-lg-4">
            <div class="sidebar-panel p-4">
                <h5 class="fw-bold text-slate-900 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-lightbulb text-warning"></i> Need to know
                </h5>
                <p class="text-secondary small mb-4" style="line-height: 1.6;">
                    Upload course documents, lab sheets, or reference files as subtopics under this module so students can access them directly from their dashboard.
                </p>
                <div class="p-3 bg-light rounded-3 border-start border-warning border-4">
                    <span class="fw-bold small text-slate-900 d-block mb-1 d-flex align-items-center gap-1.5">
                        <i class="bi bi-info-circle-fill text-dark"></i> Supported File Formats
                    </span>
                    <span class="text-secondary small">PDF, DOC, DOCX, PPT, PPTX, ZIP (Max 75 MB)</span>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($module->subtopics as $subtopic)
    <div class="modal fade" id="editSubtopicModal{{ $subtopic->id }}" tabindex="-1" aria-labelledby="editSubtopicModalLabel{{ $subtopic->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-white border-bottom-0 px-4 pt-4" style="background: var(--lemu-burgundy);">
                    <div>
                        <h5 class="modal-title fw-bold text-white" id="editSubtopicModalLabel{{ $subtopic->id }}">Edit Subtopic</h5>
                        <small class="text-warning-subtle">Modify title or replace documentation file.</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form action="{{ route('admin.subtopics.update', $subtopic) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-slate-700">Title</label>
                            <input type="text" name="title" class="form-control bg-light border-0 py-2.5 rounded-3" value="{{ $subtopic->title }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-slate-700">Replace Document</label>
                            <input type="file" name="file" class="form-control bg-light border-0 py-2.5 rounded-3">
                            <div class="form-text text-muted small mt-1">Leave empty to keep current file. Maximum file size: 75 MB.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-burgundy-gold rounded-pill px-4 fw-semibold shadow-sm">Update Subtopic</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Add Subtopic Modal -->
<div class="modal fade" id="addSubtopicModal" tabindex="-1" aria-labelledby="addSubtopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-bottom-0 px-4 pt-4" style="background: var(--lemu-burgundy);">
                <div>
                    <h5 class="modal-title fw-bold text-white" id="addSubtopicModalLabel">Add Subtopic Material</h5>
                    <small class="text-warning-subtle">Upload a new learning resource file.</small>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <form action="{{ route('admin.modules.subtopics.store', $module) }}" method="POST" enctype="multipart/form-data"
                      data-reviewer-upload>
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-slate-700">Title</label>
                        <input type="text" name="title" class="form-control bg-light border-0 py-2.5 rounded-3" placeholder="e.g., The Wide-Open Security Group" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-slate-700">Upload Document / File</label>
                        <input type="file" name="file" class="form-control bg-light border-0 py-2.5 rounded-3" required>
                        <div class="form-text text-muted small mt-1">Maximum file size: 75 MB.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-burgundy-gold rounded-pill px-4 fw-semibold shadow-sm">Upload Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="reviewer-upload-progress" class="reviewer-upload-progress" role="dialog" aria-modal="true"
     aria-labelledby="reviewer-upload-progress-title" aria-describedby="reviewer-upload-progress-message" hidden>
    <div class="reviewer-upload-progress-card">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Uploading subtopic material...</span>
        </div>
        <h2 class="h5 fw-bold mb-2" id="reviewer-upload-progress-title">Uploading subtopic material</h2>
        <p class="text-secondary small mb-0" id="reviewer-upload-progress-message">Please wait while your reviewer file is uploaded.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadForm = document.querySelector('[data-reviewer-upload]');
        const uploadProgress = document.getElementById('reviewer-upload-progress');

        if (uploadForm && uploadProgress) {
            uploadForm.addEventListener('submit', function (event) {
                if (uploadForm.dataset.uploading === 'true') {
                    event.preventDefault();

                    return;
                }

                uploadForm.dataset.uploading = 'true';
                uploadForm.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    button.disabled = true;
                });
                uploadProgress.hidden = false;
            });
        }
    });
</script>

<style>
    .reviewer-upload-progress {
        align-items: center;
        background: rgba(15, 23, 42, .55);
        display: flex;
        inset: 0;
        justify-content: center;
        padding: 1rem;
        position: fixed;
        z-index: 2000;
    }

    .reviewer-upload-progress[hidden] {
        display: none;
    }

    .reviewer-upload-progress-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, .25);
        max-width: 24rem;
        padding: 2rem;
        text-align: center;
        width: 100%;
    }
</style>
@endsection