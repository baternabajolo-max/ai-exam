@extends('layouts.app')

@section('content')
<div class="container py-4 py-lg-5">
    
    {{-- Page Title & Quick Metrics --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold text-slate-900 mb-1">Student Management</h2>
            <p class="text-muted small mb-0">Review pending registration requests and manage active student access.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fw-semibold small">
                <i class="bi bi-clock-history me-1"></i>{{ $pendingStudents->count() }} Pending Approval
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold small">
                <i class="bi bi-person-check me-1"></i>{{ $approvedStudents->count() }} Active
            </span>
        </div>
    </div>

    {{-- Main Container Card --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        
        {{-- Card Header & Filter Navigation --}}
        <div class="card-header bg-white border-bottom p-3 p-md-4">
            <div class="row g-3 align-items-center justify-content-between">
                
                {{-- Status Filter Tabs --}}
                <div class="col-12 col-md-auto">
                    <ul class="nav nav-pills gap-1 bg-light p-1 rounded-pill d-inline-flex border" id="studentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-3 py-1.5 fs-7 fw-semibold" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-panel" type="button" role="tab" aria-controls="pending-panel" aria-selected="true">
                                Pending Requests
                                <span class="badge bg-warning text-dark ms-1.5 rounded-pill">{{ $pendingStudents->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1.5 fs-7 fw-semibold" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved-panel" type="button" role="tab" aria-controls="approved-panel" aria-selected="false">
                                Approved Students
                                <span class="badge bg-secondary-subtle text-secondary ms-1.5 rounded-pill">{{ $approvedStudents->count() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Quick Search Input --}}
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 ps-3 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="studentSearchInput" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search by name, email, or college...">
                    </div>
                </div>

            </div>
        </div>

        {{-- Tab Content Stream --}}
        <div class="tab-content" id="studentTabContent">
            
            {{-- PANEL 1: PENDING REQUESTS --}}
            <div class="tab-pane fade show active" id="pending-panel" role="tabpanel" aria-labelledby="pending-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="pendingTable">
                        <thead class="bg-light-subtle border-bottom">
                            <tr class="text-uppercase text-muted fs-8 tracking-wider">
                                <th class="ps-4 py-3 fw-bold">Student Profile</th>
                                <th class="py-3 fw-bold">College</th>
                                <th class="py-3 fw-bold">Status</th>
                                <th class="text-end pe-4 py-3 fw-bold">Approval Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($pendingStudents as $student)
                            <tr class="student-row">
                                {{-- Name & Email --}}
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                            {{ strtoupper(substr($student->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-slate-900 d-block student-name">{{ $student->name }}</span>
                                            <small class="text-muted student-email">{{ $student->email }}</small>
                                        </div>
                                    </div>
                                </td>

                                {{-- College --}}
                                <td class="py-3 student-college">
                                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-2 fw-medium">
                                        <i class="bi bi-building me-1 text-muted"></i>{{ $student->department->name ?? 'Unassigned' }}
                                    </span>
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-3">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5 rounded-pill fw-semibold">
                                        <i class="bi bi-dot me-0.5 fs-6"></i>Pending
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-end pe-4 py-3">
                                    <div class="d-inline-flex gap-1.5">
                                        {{-- Approve --}}
                                        <form action="{{ route('admin.students.approve', $student->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success px-3 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check-lg"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form
                                            action="{{ route('admin.students.reject', $student->id) }}"
                                            method="POST"
                                            class="d-inline"
                                            data-confirm-title="Reject this registration request?"
                                            data-confirm-text="The student registration request will be rejected."
                                            data-confirm-button="Reject request"
                                        >
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-x-lg"></i>
                                                <span>Reject</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <div class="py-4">
                                        <i class="bi bi-inbox text-secondary display-6 d-block mb-2 opacity-50"></i>
                                        <p class="mb-0 fw-medium">No pending registration requests found</p>
                                        <small class="text-muted">New student requests will appear here for review.</small>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PANEL 2: APPROVED STUDENTS --}}
            <div class="tab-pane fade" id="approved-panel" role="tabpanel" aria-labelledby="approved-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="approvedTable">
                        <thead class="bg-light-subtle border-bottom">
                            <tr class="text-uppercase text-muted fs-8 tracking-wider">
                                <th class="ps-4 py-3 fw-bold">Student Profile</th>
                                <th class="py-3 fw-bold">College</th>
                                <th class="py-3 fw-bold">Status</th>
                                <th class="text-end pe-4 py-3 fw-bold">Manage Access</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approvedStudents as $student)
                            <tr class="student-row">
                                {{-- Name & Email --}}
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle bg-success-subtle text-success fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                            {{ strtoupper(substr($student->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-slate-900 d-block student-name">{{ $student->name }}</span>
                                            <small class="text-muted student-email">{{ $student->email }}</small>
                                        </div>
                                    </div>
                                </td>

                                {{-- College --}}
                                <td class="py-3 student-college">
                                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-2 fw-medium">
                                        <i class="bi bi-building me-1 text-muted"></i>{{ $student->department->name ?? 'Unassigned' }}
                                    </span>
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-3">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1 small"></i>Active
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-end pe-4 py-3">
                                    <form
                                        action="{{ route('admin.students.reject', $student->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        data-confirm-title="Revoke student access?"
                                        data-confirm-text="Revoking approval will restrict this student's access."
                                        data-confirm-button="Revoke access"
                                    >
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-person-x"></i>
                                            <span>Revoke Access</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <div class="py-4">
                                        <i class="bi bi-people text-secondary display-6 d-block mb-2 opacity-50"></i>
                                        <p class="mb-0 fw-medium">No approved student accounts registered</p>
                                        <small class="text-muted">Approved student accounts will be listed here.</small>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

{{-- Inline Client-side Search Filter Script --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.location.hash === '#approved-panel') {
            const approvedTab = document.getElementById('approved-tab');
            bootstrap.Tab.getOrCreateInstance(approvedTab).show();
            window.requestAnimationFrame(function () {
                document.getElementById('approved-panel').scrollIntoView({ block: 'start' });
            });
        }

        const searchInput = document.getElementById('studentSearchInput');
        if (!searchInput) return;

        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            const activePane = document.querySelector('.tab-pane.active');
            if (!activePane) return;

            const rows = activePane.querySelectorAll('tbody .student-row');
            rows.forEach(row => {
                const name = row.querySelector('.student-name')?.textContent.toLowerCase() || '';
                const email = row.querySelector('.student-email')?.textContent.toLowerCase() || '';
                const college = row.querySelector('.student-college')?.textContent.toLowerCase() || '';

                if (name.includes(query) || email.includes(query) || college.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>
@endsection