@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/student/student.css') }}">
@endpush

@section('content')
<div class="container student-dashboard">
    <h1 class="mb-4">Department Reviewers</h1>
    <p class="text-muted">Access uploaded reviewers and learning materials provided by your department.</p>

    <div class="alert alert-info">
        No reviewers have been uploaded for your department yet. Check back soon for downloadable study materials.
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/student/student.js') }}"></script>
@endpush