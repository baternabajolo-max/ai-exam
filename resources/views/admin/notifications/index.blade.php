@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
@endpush

@section('content')
<div class="container admin-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Department Notifications</h1>
            <p class="text-muted">Stay updated on student registration requests, test submissions, and system alerts.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>

    <div class="alert alert-info">
        No new notifications at this time.
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/admin.js') }}"></script>
@endpush