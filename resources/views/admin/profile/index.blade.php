@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
<style>
    /* ==========================================================
       LUXURY BURGUNDY & GOLD ADMIN PROFILE THEME
    ========================================================== */
    :root {
        --lemu-burgundy: #4a0e17;
        --lemu-burgundy-dark: #2c070e;
        --lemu-gold: #eab308;
        --lemu-gold-light: #fef08a;
    }

    @keyframes luxuryFadeUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    .animate-entrance {
        animation: luxuryFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .admin-dashboard {
        background: #f8fafc;
        min-height: calc(100vh - 72px);
    }

    .admin-card {
        background: #ffffff;
        border: 1px solid rgba(234, 179, 8, 0.25) !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 15px 35px -10px rgba(74, 14, 23, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03) !important;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease, border-color 0.3s ease;
    }

    /* Cover & Avatar Layout */
    .admin-profile-cover {
        background: linear-gradient(135deg, var(--lemu-burgundy-dark) 0%, var(--lemu-burgundy) 60%, #0f172a 100%);
        min-height: 200px;
        position: relative;
        border-bottom: 2px solid rgba(234, 179, 8, 0.3);
    }

    .admin-profile-summary {
        background: #ffffff;
        padding: 2.5rem 2rem 2rem 2rem !important;
        position: relative;
    }

    .admin-profile-avatar, .admin-profile-avatar-fallback {
        position: absolute !important;
        left: 2.5rem;
        bottom: 1.5rem;
        width: 110px !important;
        height: 110px !important;
        border-radius: 50%;
        border: 4px solid #ffffff !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        z-index: 5;
        object-fit: cover;
    }

    .admin-profile-avatar-fallback {
        background: var(--lemu-burgundy) !important;
        color: #fef08a !important;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
    }

    .profile-header-content {
        margin-left: 135px;
    }

    @media (max-width: 576px) {
        .profile-header-content {
            margin-left: 0;
            margin-top: 50px;
        }
        .admin-profile-avatar, .admin-profile-avatar-fallback {
            left: 50%;
            transform: translateX(-50%);
            bottom: 4rem;
        }
    }

    /* Hidden by default, shown ONLY when is-editing is active */
    .edit-trigger {
        display: none !important;
    }

    .is-editing .edit-trigger {
        display: inline-flex !important;
    }

    /* Background Camera Icon Positioned Centered in Background Cover */
    .admin-cover-edit-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(74, 14, 23, 0.85);
        color: #fef08a;
        border: 2px solid rgba(234, 179, 8, 0.6);
        width: 48px;
        height: 48px;
        border-radius: 50%;
        font-size: 1.2rem;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        transition: all 0.25s ease;
        z-index: 10;
        cursor: pointer;
    }

    .admin-cover-edit-btn:hover {
        background: var(--lemu-burgundy);
        color: #ffffff;
        border-color: var(--lemu-gold);
        transform: translate(-50%, -50%) scale(1.12);
    }

    /* Profile Avatar Edit Camera Button Positioning */
    .admin-avatar-edit-btn {
        position: absolute;
        left: calc(2.5rem + 75px);
        bottom: 1.5rem;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--lemu-burgundy);
        color: #fef08a;
        border: 2px solid #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        align-items: center;
        justify-content: center;
        z-index: 10;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .admin-avatar-edit-btn:hover {
        background: var(--lemu-burgundy-dark);
        color: #ffffff;
        transform: scale(1.1);
        border-color: var(--lemu-gold);
    }

    /* Single Inline Editing Inputs & Pencil Buttons */
    .inline-input-group {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .inline-edit-input {
        max-width: 260px;
        border-radius: 0.6rem;
        border: 1px solid #ca8a04;
        padding: 0.25rem 0.75rem;
        font-size: 0.9rem;
    }

    .inline-edit-btn {
        background: none;
        border: none;
        color: #ca8a04;
        font-size: 1.05rem;
        padding: 0.2rem 0.4rem;
        margin-left: 0.35rem;
        transition: transform 0.2s ease;
        cursor: pointer;
        align-items: center;
        justify-content: center;
    }

    .inline-edit-btn:hover {
        transform: scale(1.15);
        color: var(--lemu-burgundy);
    }

    .inline-confirm-btn {
        background: #22c55e;
        color: #ffffff;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        margin-left: 0.35rem;
        transition: transform 0.2s ease;
    }

    .inline-confirm-btn:hover {
        transform: scale(1.1);
        background: #16a34a;
    }

    /* Security Panels */
    .admin-security-panel {
        background: #fffbf0;
        border: 1px solid rgba(234, 179, 8, 0.25);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        transition: all 0.25s ease;
    }

    .admin-security-panel:hover {
        border-color: #eab308;
        box-shadow: 0 8px 20px -4px rgba(74, 14, 23, 0.08);
        transform: translateY(-2px);
    }

    .admin-security-panel i {
        font-size: 1.5rem;
        color: var(--lemu-burgundy);
        background: rgba(234, 179, 8, 0.2);
        padding: 0.65rem;
        border-radius: 0.85rem;
        border: 1px solid rgba(234, 179, 8, 0.35);
    }

    /* Luxury Buttons */
    .btn-primary {
        background: linear-gradient(135deg, #4a0e17 0%, #2c070e 100%) !important;
        color: #fef08a !important;
        border: 1px solid rgba(234, 179, 8, 0.4) !important;
        box-shadow: 0 4px 15px rgba(74, 14, 23, 0.25);
        transition: all 0.25s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #5c121d, #380912) !important;
        color: #ffffff !important;
        border-color: #eab308 !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(234, 179, 8, 0.35);
    }

    .btn-outline-primary {
        color: var(--lemu-burgundy) !important;
        border-color: rgba(74, 14, 23, 0.3) !important;
        background: #fff;
        transition: all 0.25s ease;
    }

    .btn-outline-primary:hover {
        background: var(--lemu-burgundy) !important;
        color: #fef08a !important;
        border-color: var(--lemu-burgundy) !important;
    }

    /* Modal Styling Refinements */
    .modal-content {
        border-radius: 1.5rem !important;
        overflow: hidden;
        border: 1px solid rgba(234, 179, 8, 0.35) !important;
        box-shadow: 0 20px 40px rgba(74, 14, 23, 0.2) !important;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--lemu-burgundy-dark), var(--lemu-burgundy));
        color: #fff;
        border-bottom: 1px solid rgba(234, 179, 8, 0.3);
        padding: 1.25rem 1.5rem;
    }

    .modal-header .modal-title {
        color: #fff !important;
        font-weight: 700;
    }

    .modal-header .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem 1.5rem 1.5rem;
        border-top: 0;
        gap: 0.75rem;
    }

    .tracking-widest-caps {
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 800;
    }
</style>
@endpush

@section('content')
@php
    $profileUser ??= auth()->user();
    $profileRoute ??= 'admin.profile.update';
    $passwordRoute ??= 'admin.profile.password.update';
    $roleLabel = $profileUser->role_label;
    $isInstitutionAdmin = $profileUser->role === 'super-admin';
@endphp
<div class="container admin-dashboard py-4 animate-entrance" id="profileMainContainer">
    <!-- Profile Hero Card -->
    <div class="card admin-card overflow-hidden mb-4">
        <div class="admin-profile-cover"
            @if($profileUser->background_picture_path)
                style="background-image: url('{{ asset('storage/' . $profileUser->background_picture_path) }}')"
            @endif>
            
            <!-- Background Camera Icon Button (Centered on Cover, visible in Edit Mode) -->
            <button type="button" class="admin-cover-edit-btn edit-trigger" data-bs-toggle="modal" data-bs-target="#editBackgroundModal" aria-label="Change Background" title="Change Background Image">
                <i class="bi bi-camera-fill"></i>
            </button>
        </div>

        <div class="admin-profile-summary">
            <!-- Profile Avatar -->
            @if($profileUser->profile_picture_path)
                <img class="admin-profile-avatar" src="{{ asset('storage/' . $profileUser->profile_picture_path) }}" alt="{{ $profileUser->name }}">
            @else
                <div class="admin-profile-avatar admin-profile-avatar-fallback">{{ strtoupper(substr($profileUser->name, 0, 1)) }}</div>
            @endif
            
            <!-- Avatar Camera Button (Visible in Edit Mode) -->
            <button type="button" class="admin-avatar-edit-btn edit-trigger" data-bs-toggle="modal" data-bs-target="#editProfilePictureModal" aria-label="Edit profile picture" title="Change Profile Picture">
                <i class="bi bi-camera-fill" aria-hidden="true"></i>
            </button>

            <div class="profile-header-content">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-3">
                    <div>
                        <!-- Name Row -->
                        <div class="d-flex align-items-center mb-1">
                            <h2 class="h4 fw-extrabold text-slate-900 mb-0 me-2" id="nameTextDisplay">{{ $profileUser->name }}</h2>
                            <button type="button" id="editNameIconBtn" class="inline-edit-btn edit-trigger ms-1" title="Edit Name">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <div id="nameInputWrapper" class="inline-input-group d-none ms-2">
                                <input type="text" id="inlineFirstNameInput" class="form-control form-control-sm inline-edit-input" value="{{ $profileUser->first_name }}" aria-label="First name">
                                <input type="text" id="inlineLastNameInput" class="form-control form-control-sm inline-edit-input" value="{{ $profileUser->last_name }}" aria-label="Last name (optional)" placeholder="Last name (optional)">
                                <button type="button" id="confirmNameBtn" class="inline-confirm-btn ms-1" title="Confirm Name locally">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Email Row -->
                        <div class="d-flex align-items-center">
                            <p class="text-secondary small mb-0 font-monospace">{{ $profileUser->email }} <span class="ms-1">· Immutable</span></p>
                        </div>
                    </div>

                    <!-- Header Actions -->
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="cancelEditBtn" class="btn btn-light btn-sm rounded-pill px-3 border fw-semibold d-none">
                            Cancel
                        </button>
                        <button type="button" id="toggleEditProfileBtn" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-pencil-square me-2 text-warning" aria-hidden="true"></i>Edit Profile
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge px-3 py-1.5 rounded-pill fw-bold tracking-widest-caps" style="background: linear-gradient(135deg, #4a0e17, #2c070e); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.4);">{{ $roleLabel }}</span>
                    @if($profileUser->department)
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1.5 rounded-pill fw-semibold">{{ $profileUser->department->name }}</span>
                    @endif
                    @if($isInstitutionAdmin)
                        <a href="{{ route('super-admin.dashboard') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Admin dashboard
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Role Overview -->
        <div class="col-lg-5">
            <div class="card admin-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h2 class="h5 fw-bold text-slate-900 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-warning me-2"></i> {{ $isInstitutionAdmin ? 'Admin Overview' : 'Faculty Overview' }}
                    </h2>
                </div>
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 rounded-4 bg-light border-start border-warning border-4">
                        <span class="text-secondary small d-block mb-1 fw-medium">System Authorization</span>
                        <strong class="text-slate-900 fs-6">{{ $isInstitutionAdmin ? 'Institution-wide admin access' : 'College-scoped faculty access' }}</strong>
                    </div>
                    <div class="p-3 rounded-4 bg-light">
                        <span class="text-secondary small d-block mb-1 fw-medium">{{ $isInstitutionAdmin ? 'Access scope' : 'Assigned College' }}</span>
                        <strong class="text-slate-900">{{ $isInstitutionAdmin ? 'All colleges and platform settings' : ($profileUser->department?->name ?? 'No college assigned') }}</strong>
                    </div>
                    <div class="p-3 rounded-4 bg-light">
                        <span class="text-secondary small d-block mb-1 fw-medium">Profile Security Status</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-bold small">
                            <i class="bi bi-check-circle-fill me-1.5"></i> Secure & Synchronized
                        </span>
                    </div>
                    @if($isInstitutionAdmin)
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('super-admin.users.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">Manage faculty</a>
                            <a href="{{ route('super-admin.departments.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">Manage colleges</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Security Settings Card -->
        <div class="col-lg-7">
            <div class="card admin-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <div>
                        <h2 class="h5 fw-bold text-slate-900 mb-1">Settings</h2>
                        <p class="text-secondary small mb-0">Manage sensitive account changes securely.</p>
                    </div>
                </div>
                <div class="d-flex flex-column gap-3">
                    <div class="admin-security-panel">
                        <i class="bi bi-envelope-at-fill" aria-hidden="true"></i>
                        <div>
                            <strong class="text-slate-900 d-block mb-0.5">Email address</strong>
                            <span class="d-block text-secondary small font-monospace">Current: {{ $profileUser->email }}</span>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary ms-auto">Read only</span>
                    </div>
                    <div class="admin-security-panel">
                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                        <div>
                            <strong class="text-slate-900 d-block mb-0.5">Password</strong>
                            <span class="d-block text-secondary small">Update your password securely.</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 ms-auto fw-semibold" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Edit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Password Confirmation Before Saving Profile Changes -->
<div class="modal fade" id="confirmPasswordModal" tabindex="-1" aria-labelledby="confirmPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header px-4 pt-4 pb-3">
                <h2 class="modal-title h5 fw-bold text-white d-flex align-items-center gap-2" id="confirmPasswordModalLabel">
                    <i class="bi bi-shield-lock text-warning me-1"></i> Confirm Password
                </h2>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route($profileRoute) }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="first_name" id="modalHiddenFirstName" value="{{ $profileUser->first_name }}">
                <input type="hidden" name="last_name" id="modalHiddenLastName" value="{{ $profileUser->last_name }}">
                <div class="modal-body p-4 bg-white">
                    <div class="mb-0">
                        <label class="form-label fw-semibold small text-slate-700 mb-2">Enter Current Password</label>
                        <input class="form-control bg-light border-0 py-2.5 rounded-3" type="password" name="current_password" required placeholder="Required to save changes">
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4 border-0 bg-white d-flex justify-content-between gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold flex-grow-1">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals for Background & Avatar Image Uploads -->
<div class="modal fade" id="editBackgroundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header px-4 pt-4 pb-3">
                <h2 class="modal-title h5 fw-bold text-white d-flex align-items-center gap-2">
                    <i class="bi bi-camera text-warning me-1"></i> Change Background
                </h2>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route($profileRoute) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-body p-4 bg-white">
                    <label class="form-label fw-semibold small text-slate-700 mb-2">Choose Image File</label>
                    <input class="form-control bg-light border-0 py-2.5 rounded-3" type="file" name="background_picture" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <div class="modal-footer px-4 pb-4 border-0 bg-white d-flex justify-content-between gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold flex-grow-1">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProfilePictureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header px-4 pt-4 pb-3">
                <h2 class="modal-title h5 fw-bold text-white d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge text-warning me-1"></i> Change Profile Picture
                </h2>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route($profileRoute) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-body p-4 bg-white">
                    <label class="form-label fw-semibold small text-slate-700 mb-2">Choose Image File</label>
                    <input class="form-control bg-light border-0 py-2.5 rounded-3" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <div class="modal-footer px-4 pb-4 border-0 bg-white d-flex justify-content-between gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold flex-grow-1">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header px-4 pt-4 pb-3">
                <h2 class="modal-title h5 fw-bold text-white d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-warning me-1"></i> Change Password
                </h2>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route($passwordRoute) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4 bg-white">
                    <div class="mb-3"><label class="form-label fw-semibold small text-slate-700 mb-2">Current Password</label><input class="form-control bg-light border-0 py-2.5 rounded-3" type="password" name="current_password" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-slate-700 mb-2">New Password</label><input class="form-control bg-light border-0 py-2.5 rounded-3" type="password" name="password" required></div>
                    <div class="mb-0"><label class="form-label fw-semibold small text-slate-700 mb-2">Confirm New Password</label><input class="form-control bg-light border-0 py-2.5 rounded-3" type="password" name="password_confirmation" required></div>
                </div>
                <div class="modal-footer px-4 pb-4 border-0 bg-white d-flex justify-content-between gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold flex-grow-1">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/admin.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainContainer = document.getElementById('profileMainContainer');
        const toggleEditProfileBtn = document.getElementById('toggleEditProfileBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');

        const editNameIconBtn = document.getElementById('editNameIconBtn');
        const nameTextDisplay = document.getElementById('nameTextDisplay');
        const nameInputWrapper = document.getElementById('nameInputWrapper');
        const inlineFirstNameInput = document.getElementById('inlineFirstNameInput');
        const inlineLastNameInput = document.getElementById('inlineLastNameInput');
        const confirmNameBtn = document.getElementById('confirmNameBtn');

        const confirmPasswordModal = new bootstrap.Modal(document.getElementById('confirmPasswordModal'));
        const modalHiddenFirstName = document.getElementById('modalHiddenFirstName');
        const modalHiddenLastName = document.getElementById('modalHiddenLastName');
        const originalFirstName = @js($profileUser->first_name);
        const originalLastName = @js($profileUser->last_name);

        let isEditingMode = false;

        // Toggle Edit Mode ON/OFF
        toggleEditProfileBtn?.addEventListener('click', function () {
            if (!isEditingMode) {
                // Enter Edit Mode
                isEditingMode = true;
                mainContainer.classList.add('is-editing');
                toggleEditProfileBtn.innerHTML = '<i class="bi bi-check-circle-fill me-2 text-warning" aria-hidden="true"></i>Save';
                cancelEditBtn.classList.remove('d-none');
            } else {
                // Save Clicked -> Pass local values & Trigger Password Confirmation Modal
                modalHiddenFirstName.value = inlineFirstNameInput.value;
                modalHiddenLastName.value = inlineLastNameInput.value;
                confirmPasswordModal.show();
            }
        });

        // Cancel Edit Mode
        cancelEditBtn?.addEventListener('click', function () {
            isEditingMode = false;
            mainContainer.classList.remove('is-editing');
            toggleEditProfileBtn.innerHTML = '<i class="bi bi-pencil-square me-2 text-warning" aria-hidden="true"></i>Edit Profile';
            cancelEditBtn.classList.add('d-none');

            // Reset inputs & text display
            nameTextDisplay.classList.remove('d-none');
            nameInputWrapper.classList.add('d-none');
            nameTextDisplay.innerText = `${originalFirstName} ${originalLastName}`.trim();
            inlineFirstNameInput.value = originalFirstName;
            inlineLastNameInput.value = originalLastName;
        });

        // Name Pencil Click -> Show Single Line Input
        editNameIconBtn?.addEventListener('click', function() {
            nameTextDisplay.classList.add('d-none');
            nameInputWrapper.classList.remove('d-none');
            inlineFirstNameInput.focus();
        });

        // Name Green Check -> Confirms locally without triggering password modal
        confirmNameBtn?.addEventListener('click', function() {
            nameTextDisplay.innerText = `${inlineFirstNameInput.value} ${inlineLastNameInput.value}`.trim();
            nameTextDisplay.classList.remove('d-none');
            nameInputWrapper.classList.add('d-none');
        });
    });
</script>
@endpush