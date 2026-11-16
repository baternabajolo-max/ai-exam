@extends('layouts.app')

@section('content')
<main class="container py-4">
    <!-- Header Section -->
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 border-bottom pb-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                Notifications
                @if($hasUnreadNotifications)
                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle fs-6 px-2 py-1" data-notification-page-unread>
                        Unread updates
                    </span>
                @endif
            </h1>
            <p class="text-secondary mb-0">Messages from your administration, achievement unlocks, and leaderboard alerts.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($hasUnreadNotifications)
                <form method="POST" action="{{ route('student.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.501.502 6.146-6.146a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0"/>
                            <path d="m5.354 7.146.896.897-.707.707-.501-.502L.854 11.854a.5.5 0 1 1-.708-.708l5-5a.5.5 0 0 1 .708 0"/>
                        </svg>
                        Mark all as read
                    </button>
                </form>
            @endif
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg>
                Back to dashboard
            </a>
        </div>
    </header>

    <!-- Session Alerts -->
    <!-- Notification Feed -->
    <div class="d-flex flex-column gap-3">
        @forelse($notifications as $notification)
            @php
                $isAchievement = str_starts_with($notification->subject, 'Achievement unlocked:');
                $isUnread = is_null($notification->read_at);
            @endphp
            <article class="card transition-all border-0 shadow-sm {{ $isUnread ? 'bg-primary-subtle bg-opacity-10 border-start border-4 border-primary' : 'bg-white' }}">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Category Icon -->
                            @if($isAchievement)
                                <span class="p-2 rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex" title="Achievement">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-trophy-fill" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M2.5.5A.5.5 0 0 5 2 1h12a.5.5 0 0 1 .5.5c0 2.5-1.5 3.5-3 3.5h-1a6 6 0 0 1-5 0h-1c-1.5 0-3-1-3-3.5"/>
                                        <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .5.5v1A2.5 2.5 0 0 1 0 6zm16 0a.5.5 0 0 0-.5-.5H14a.5.5 0 0 0-.5.5v1A2.5 2.5 0 0 0 16 6z"/>
                                        <path d="M8 12a4 4 0 0 0 4-4H4a4 4 0 0 0 4 4m0 1a5 5 0 0 1-5-5h10a5 5 0 0 1-5 5"/>
                                    </svg>
                                </span>
                            @else
                                <span class="p-2 rounded-circle bg-primary-subtle text-primary d-inline-flex" title="Announcement">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"/>
                                    </svg>
                                </span>
                            @endif

                            <h2 class="h5 fw-semibold mb-0 text-dark">{{ $notification->subject }}</h2>
                        </div>

                        <!-- Read / Unread Status Indicator -->
                        <div>
                            @if($isUnread)
                                <span class="badge bg-primary rounded-pill px-3 py-1">New</span>
                            @else
                                <span class="badge bg-light text-secondary border px-3 py-1">Read</span>
                            @endif
                        </div>
                    </div>

                    <!-- Notification Snippet -->
                    <p class="mb-3 text-secondary lh-base ms-md-5">
                        {{ \Illuminate\Support\Str::limit($notification->message, 180) }}
                    </p>

                    <!-- Card Footer Metadata -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 ms-md-5 pt-2 border-top border-light">
                        <small class="text-muted d-inline-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                            </svg>
                            <strong class="text-dark">{{ $notification->admin?->name ?? config('app.name', 'LEMU') }}</strong>
                            <span>•</span>
                            <time datetime="{{ $notification->created_at->toIso8601String() }}">
                                {{ $notification->created_at->format('M d, Y · h:i A') }}
                            </time>
                        </small>

                        <button
                            type="button"
                            class="btn btn-sm btn-primary px-3 notification-modal-trigger"
                            data-bs-toggle="modal"
                            data-bs-target="#studentNotificationModal"
                            data-notification-title="{{ $notification->subject }}"
                            data-notification-message="{{ $notification->message }}"
                            data-notification-sender="{{ $notification->admin?->name ?? config('app.name', 'LEMU') }}"
                            data-notification-read="{{ $isUnread ? 'false' : 'true' }}"
                            data-notification-achievement="{{ $isAchievement ? 'true' : 'false' }}"
                            data-notification-read-url="{{ route('student.notifications.read', $notification) }}"
                            data-notification-delete-url="{{ route('student.notifications.destroy', $notification) }}"
                        >
                            View details
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <div class="bg-light d-inline-flex p-3 rounded-circle mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-bell-slash text-muted" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M5.164 14H15c-1.5-1-2-5.902-2-7 0-.242-.017-.475-.05-.7L12.01 5.3A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h3.164l1 1zm9.836 1H1a1 1 0 0 1-.8-.4l14-14a.5.5 0 1 1 .707.707l-2.513 2.513A6 6 0 0 1 14 6c0 1.098.5 6 2 7a1 1 0 0 1-.8 1.6zM7.01 1.5a1 1 0 1 1 1.98 0A5 5 0 0 1 13 6c0 .264-.018.521-.051.773l-1.025-1.025A4 4 0 0 0 8 2.5c-.3 0-.588.033-.865.096L6.03 1.503A1 1 0 0 1 7.01 1.5M8 16a2 2 0 0 1-2-2h4a2 2 0 0 1-2 2"/>
                        </svg>
                    </div>
                    <h2 class="h5 fw-bold text-dark">No notifications yet</h2>
                    <p class="text-secondary mb-0">When you earn achievements or receive announcements from your instructor, they will appear here.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $notifications->links() }}
        </div>
    @endif
</main>
@endsection