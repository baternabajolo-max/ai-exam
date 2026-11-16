@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/super-admin/super-admin.css') }}">
@endpush

@section('content')
<main class="container super-admin-dashboard super-admin-page py-4 py-lg-5">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="super-admin-eyebrow">System administration</span>
            <h1 class="super-admin-page-title">Profile &amp; settings</h1>
            <p class="super-admin-page-subtitle">Manage your institution-wide account and sign-in security.</p>
        </div>
        <a href="{{ route('super-admin.dashboard') }}" class="btn btn-outline-secondary">Back to dashboard</a>
    </header>

    <div class="row g-4">
        <div class="col-lg-5">
            <section class="super-admin-profile-card h-100">
                <div class="p-4 text-white rounded-top"
                    @if($profileUser->background_picture_path)
                        style="background: linear-gradient(90deg, rgba(44,7,14,.9), rgba(44,7,14,.5)), url('{{ asset('storage/'.$profileUser->background_picture_path) }}') center/cover"
                    @else
                        style="background: linear-gradient(135deg, #2c070e, #701b2b)"
                    @endif>
                    <span class="badge bg-warning text-dark mb-3">{{ $profileUser->role_label }}</span>
                    <div class="d-flex align-items-center gap-3">
                        @if($profileUser->profile_picture_path)
                            <img src="{{ asset('storage/'.$profileUser->profile_picture_path) }}" alt="{{ $profileUser->name }}" class="rounded-circle border border-3 border-white" width="76" height="76" style="object-fit: cover">
                        @else
                            <span class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 76px; height: 76px" aria-label="Profile picture placeholder">{{ strtoupper(substr($profileUser->name, 0, 1)) }}</span>
                        @endif
                        <div>
                            <h2 class="h4 mb-1">{{ $profileUser->name }}</h2>
                            <p class="mb-0 small">{{ $profileUser->email }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold">Institution-wide system access</h3>
                    <p class="text-muted small">Manage faculty accounts, colleges, courses, and platform-wide reporting.</p>
                    <a href="{{ route('super-admin.users.index') }}" class="btn btn-sm btn-outline-danger">Manage faculty</a>
                    <a href="{{ route('super-admin.departments.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Manage colleges</a>

                    <hr class="my-4">
                    <h3 class="h6 fw-bold">Profile images</h3>
                    <form action="{{ route('super-admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        @method('PUT')
                        <label class="form-label small" for="super-admin-profile-picture">Profile picture</label>
                        <input class="form-control form-control-sm" id="super-admin-profile-picture" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp">
                        <button class="btn btn-sm btn-danger mt-2" type="submit">Update picture</button>
                    </form>
                    <form action="{{ route('super-admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <label class="form-label small" for="super-admin-background-picture">Background picture</label>
                        <input class="form-control form-control-sm" id="super-admin-background-picture" type="file" name="background_picture" accept="image/jpeg,image/png,image/webp">
                        <button class="btn btn-sm btn-outline-danger mt-2" type="submit">Update background</button>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="super-admin-profile-card mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-1">Personal information</h2>
                    <p class="text-muted small mb-3">Only your first name and surname are editable. Your verified account email is immutable.</p>
                    <form action="{{ route('super-admin.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="super-admin-first-name">First name</label>
                                <input class="form-control" id="super-admin-first-name" name="first_name" value="{{ old('first_name', $profileUser->first_name) }}" autocomplete="given-name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="super-admin-last-name">Surname (optional)</label>
                                <input class="form-control" id="super-admin-last-name" name="last_name" value="{{ old('last_name', $profileUser->last_name) }}" autocomplete="family-name">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="super-admin-email">Immutable account email</label>
                                <input class="form-control" id="super-admin-email" type="email" value="{{ $profileUser->email }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="profile-current-password">Current password confirmation</label>
                                <input class="form-control" id="profile-current-password" type="password" name="current_password" autocomplete="current-password" required>
                            </div>
                        </div>
                        <button class="btn btn-danger mt-3" type="submit">Save name</button>
                    </form>
                </div>
            </section>

            <section class="super-admin-profile-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-1">Account security</h2>
                    <p class="text-muted small">Keep your admin password private and unique.</p>
                    <form action="{{ route('super-admin.profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="current-password">Current password</label>
                                <input class="form-control" id="current-password" type="password" name="current_password" autocomplete="current-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="new-password">New password</label>
                                <input class="form-control" id="new-password" type="password" name="password" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="confirm-password">Confirm new password</label>
                                <input class="form-control" id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" required>
                            </div>
                        </div>
                        <button class="btn btn-outline-danger mt-3" type="submit">Update password</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('js/super-admin/super-admin.js') }}"></script>
@endpush
