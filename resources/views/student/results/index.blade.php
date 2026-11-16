@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">My General Test Results History</h2>

    @if($results->isEmpty())
        <div class="alert alert-info">You haven't taken any general tests yet.</div>
    @else
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Test Title</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Date Taken</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td class="fw-bold">{{ $result->test->title ?? 'Deleted Test' }}</td>
                                <td>{{ $result->score }} / {{ $result->total_questions }}</td>
                                <td>{{ number_format($result->percentage, 1) }}%</td>
                                <td>
                                    <span class="badge {{ $result->passed ? 'bg-success' : 'bg-danger' }}">
                                        {{ $result->passed ? 'Passed' : 'Failed' }}
                                    </span>
                                </td>
                                <td>{{ $result->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <a href="{{ route('student.results.show', $result->id) }}" class="btn btn-sm btn-outline-primary">View Breakdown</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection