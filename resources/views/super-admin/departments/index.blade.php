@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/super-admin/super-admin.css') }}">
@endpush

@section('content')
<div class="super-admin-page college-management py-4 py-lg-5">
    <div class="container">
        <header class="super-admin-page-header mb-4">
            <div>
                <span class="super-admin-eyebrow">Institution setup</span>
                <h1 class="super-admin-page-title">Colleges &amp; courses</h1>
                <p class="super-admin-page-subtitle">Organize the colleges, programs, and student accounts in LEMU.</p>
            </div>
            <a href="{{ route('super-admin.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Analytics
            </a>
        </header>

        <section class="college-create-panel mb-4" aria-labelledby="create-college-title">
            <div class="college-create-copy">
                <span class="college-create-icon"><i class="bi bi-building-add" aria-hidden="true"></i></span>
                <div>
                    <h2 id="create-college-title" class="h5 fw-bold mb-1">Add a college</h2>
                    <p class="small text-secondary mb-0">Create the college first, then add its courses.</p>
                </div>
            </div>
            <form action="{{ route('super-admin.departments.store') }}" method="POST" class="college-create-form">
                @csrf
                <label class="visually-hidden" for="college-name">College name</label>
                <input id="college-name" type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. College of Computing" required>
                <button type="submit" class="btn btn-danger"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Create college</button>
            </form>
        </section>

        <div class="college-list-heading d-flex justify-content-between align-items-end gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Your colleges</h2>
                <p class="small text-secondary mb-0">Manage college details and their available courses.</p>
            </div>
            <span class="college-count-badge">{{ $departments->count() }} {{ \Illuminate\Support\Str::plural('college', $departments->count()) }}</span>
        </div>

        <section class="row g-3" aria-label="Colleges">
            @forelse($departments as $department)
                <div class="col-12">
                    <article class="college-card">
                        <div class="college-card-header">
                            <span class="college-card-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
                            <div class="college-card-title">
                                <span class="college-card-code">{{ $department->code }}</span>
                                <h3>{{ $department->name }}</h3>
                                <p>{{ $department->courses->count() }} {{ \Illuminate\Support\Str::plural('course', $department->courses->count()) }} <span aria-hidden="true">·</span> {{ $department->users_count ?? 0 }} accounts</p>
                            </div>
                            <button type="button" class="btn btn-outline-danger college-manage-button" data-bs-toggle="modal" data-bs-target="#collegeModal{{ $department->id }}">
                                <i class="bi bi-sliders me-1" aria-hidden="true"></i>Manage college
                            </button>
                        </div>
                        <div class="college-course-preview">
                            @forelse($department->courses->take(5) as $course)
                                <span class="college-course-chip">{{ $course->name }}</span>
                            @empty
                                <span class="small text-secondary">No courses added yet.</span>
                            @endforelse
                            @if($department->courses->count() > 5)
                                <span class="college-course-chip college-course-more">+{{ $department->courses->count() - 5 }} more</span>
                            @endif
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="college-empty-state">
                        <span class="college-empty-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
                        <h3 class="h5 fw-bold">No colleges yet</h3>
                        <p class="text-secondary mb-0">Create a college above to start organizing courses and student accounts.</p>
                    </div>
                </div>
            @endforelse
        </section>
    </div>
</div>

@foreach($departments as $department)
    <div class="modal fade" id="collegeModal{{ $department->id }}" tabindex="-1" aria-labelledby="collegeModalLabel{{ $department->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content college-modal">
                <div class="modal-header">
                    <div>
                        <span class="super-admin-eyebrow">College settings</span>
                        <h2 class="modal-title h5 fw-bold" id="collegeModalLabel{{ $department->id }}">{{ $department->name }}</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('super-admin.departments.update', $department) }}" method="POST" class="college-name-form mb-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="form-label fw-semibold" for="college-edit-name-{{ $department->id }}">College name</label>
                            <input id="college-edit-name-{{ $department->id }}" type="text" name="name" class="form-control" value="{{ $department->name }}" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger align-self-end"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save name</button>
                    </form>

                    <div class="college-courses-heading">
                        <div>
                            <h3 class="h6 fw-bold mb-1">Courses &amp; programs</h3>
                            <p class="small text-secondary mb-0">Edit course names or remove a course.</p>
                        </div>
                    </div>
                    <div class="college-course-list">
                        @forelse($department->courses as $course)
                            <div class="college-course-row">
                                <form action="{{ route('super-admin.courses.update', $course) }}" method="POST" class="college-course-edit">
                                    @csrf
                                    @method('PUT')
                                    <div class="college-course-edit-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></div>
                                    <div class="college-course-edit-field">
                                        <label class="visually-hidden" for="course-name-{{ $course->id }}">Course name</label>
                                        <input id="course-name-{{ $course->id }}" type="text" name="name" class="form-control form-control-sm" value="{{ $course->name }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary" aria-label="Save changes to {{ $course->name }}">
                                        <i class="bi bi-check2" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Save</span>
                                    </button>
                                </form>
                                <form
                                    action="{{ route('super-admin.courses.destroy', $course) }}"
                                    method="POST"
                                    class="delete-form delete-course-form"
                                    data-confirm-title="Delete this course?"
                                    data-confirm-text="This course will be permanently removed."
                                    data-confirm-button="Delete course"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $course->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        @empty
                            <p class="college-no-courses">No courses have been added to this college.</p>
                        @endforelse
                    </div>

                    <form action="{{ route('super-admin.departments.courses.store', $department) }}" method="POST" class="college-add-course">
                        @csrf
                        <div class="college-add-course-icon"><i class="bi bi-plus-lg" aria-hidden="true"></i></div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold" for="new-course-name-{{ $department->id }}">Add a course</label>
                            <input id="new-course-name-{{ $department->id }}" type="text" name="name" class="form-control" placeholder="e.g. BS Information Technology" required>
                        </div>
                        <button type="submit" class="btn btn-dark align-self-end">Add course</button>
                    </form>

                    <form
                        action="{{ route('super-admin.departments.destroy', $department) }}"
                        method="POST"
                        class="delete-form delete-department mt-4 text-end"
                        data-confirm-title="Delete this college?"
                        data-confirm-text="This will affect all courses associated with this college."
                        data-confirm-button="Delete college"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i>Delete college</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
