@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Users</h2>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-3">Add User</h4>
                    <form action="{{ route('super-admin.users.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student">Student</option>
                                <option value="admin">Faculty</option>
                                <option value="super-admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">None / Global</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create User</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $userAccount)
                                <tr>
                                    <td class="fw-bold">{{ $userAccount->name }}</td>
                                    <td>{{ $userAccount->email }}</td>
                                    <td>
                                        <span class="badge 
                                            @if($userAccount->role === 'super-admin') bg-danger 
                                            @elseif($userAccount->role === 'admin') bg-warning text-dark 
                                            @else bg-info text-dark @endif">
                                            {{ $userAccount->role_label }}
                                        </span>
                                    </td>
                                    <td>{{ $userAccount->department->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($userAccount->id !== auth()->id())
                                            <form
                                                action="{{ route('super-admin.users.destroy', $userAccount->id) }}"
                                                method="POST"
                                                class="d-inline"
                                                data-confirm-title="Delete this user?"
                                                data-confirm-text="This action cannot be undone."
                                                data-confirm-button="Delete user"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection