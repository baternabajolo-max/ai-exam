@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/super-admin/super-admin.css') }}">
@endpush

@section('content')
<div class="super-admin-page admin-directory py-4 py-lg-5">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <span class="super-admin-eyebrow">Institution access</span>
                <h1 class="super-admin-page-title">College faculty</h1>
                <p class="super-admin-page-subtitle">Create and manage faculty accounts assigned to colleges.</p>
            </div>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                <i class="bi bi-person-plus me-1"></i>Add Faculty
            </button>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error') || session('invitation_error'))
            <div class="alert alert-danger" role="alert">{{ session('error') ?? session('invitation_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="directory-card overflow-hidden">
            <div class="d-flex align-items-center justify-content-between border-bottom p-4">
                <div>
                    <h2 class="h5 fw-bold mb-1">Faculty Accounts</h2>
                    <p class="text-muted small mb-0">{{ $admins->count() }} faculty member{{ $admins->count() === 1 ? '' : 's' }} registered</p>
                </div>
                <span class="badge rounded-pill text-bg-light"><i class="bi bi-shield-check me-1"></i>Faculty only</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Faculty member</th>
                            <th>College</th>
                            <th>Account</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($admin->profile_picture_path)
                                            <img class="admin-avatar" src="{{ asset('storage/'.$admin->profile_picture_path) }}" alt="{{ $admin->name }}">
                                        @else
                                            <span class="admin-avatar">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                        @endif
                                        <div>
                                            <div class="admin-name">{{ $admin->name }}</div>
                                            <small class="text-muted">{{ $admin->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong class="d-block">{{ $admin->department?->name ?? 'College not assigned' }}</strong>
                                </td>
                                <td>
                                    @if($admin->email_verified_at)
                                        <span class="badge rounded-pill text-bg-success">Active</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-warning">Invitation pending</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $admin->created_at?->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        @if($admin->email_verified_at === null)
                                            <form
                                                action="{{ route('super-admin.users.resend-invitation', $admin) }}"
                                                method="POST"
                                                data-confirm-title="Resend faculty invitation?"
                                                data-confirm-text="A new setup link will be sent and the previous link will stop working."
                                                data-confirm-button="Resend invitation"
                                            >
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-envelope-arrow-up me-1"></i>Resend
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAdminModal{{ $admin->id }}">
                                            <i class="bi bi-pencil me-1"></i>Assign
                                        </button>
                                        <form
                                            action="{{ route('super-admin.users.destroy', $admin) }}"
                                            method="POST"
                                            class="delete-form delete-faculty"
                                            data-confirm-title="Delete this faculty account?"
                                            data-confirm-text="This faculty member will lose access to LEMU."
                                            data-confirm-button="Delete faculty"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state"><i class="bi bi-people fs-2 d-block mb-2"></i>No faculty accounts have been created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

@foreach($admins as $admin)
    <div class="modal fade" id="editAdminModal{{ $admin->id }}" tabindex="-1" aria-labelledby="editAdminModalLabel{{ $admin->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('super-admin.users.update', $admin) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5 fw-bold" id="editAdminModalLabel{{ $admin->id }}">Edit Faculty Account</h2>
                            <small class="text-muted">{{ $admin->name }} · {{ $admin->email }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="faculty-college-{{ $admin->id }}" class="form-label fw-semibold">Assigned college</label>
                        <select class="form-select" id="faculty-college-{{ $admin->id }}" name="department_id" required>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($admin->department_id === $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <label for="admin-password-{{ $admin->id }}" class="form-label fw-semibold mt-3">New password (optional)</label>
                        <input type="password" class="form-control" id="admin-password-{{ $admin->id }}" name="password" minlength="8">
                        <div class="form-text">Leave blank to keep the current password. If set, use at least 8 characters, including an uppercase letter and a symbol.</div>
                        <label for="admin-password-confirmation-{{ $admin->id }}" class="form-label fw-semibold mt-3">Confirm new password</label>
                        <input type="password" class="form-control" id="admin-password-confirmation-{{ $admin->id }}" name="password_confirmation" minlength="8">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('super-admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 fw-bold" id="createAdminModalLabel">Add Faculty</h2>
                        <small class="text-muted">Faculty can work across programs within the assigned college.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin-name" class="form-label fw-semibold">Full name</label>
                        <input type="text" class="form-control" id="admin-name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin-email" class="form-label fw-semibold">Email address</label>
                        <input type="email" class="form-control" id="admin-email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div>
                        <label for="admin-department" class="form-label fw-semibold">College</label>
                        <select class="form-select" id="admin-department" name="department_id" required>
                            <option value="">Select college</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Create Faculty &amp; Send Setup Link</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && (old('name') || old('email') || old('department_id')))
            const createModal = document.getElementById('createAdminModal');
            if (createModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(createModal).show();
            }
        @endif
    });
</script>
@endpush
@endsection
