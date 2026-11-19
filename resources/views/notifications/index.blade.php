@extends('layouts.app')

@section('title', 'Notifications')

@push('styles')
    <style>
        .notifications-page {
            --notification-accent: var(--bs-primary);
            max-width: 1440px;
        }

        .notifications-page__sidebar,
        .notifications-page__reader {
            background: linear-gradient(145deg, #fff, #f8fafc);
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 1.25rem;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .08), 0 4px 10px rgba(15, 23, 42, .04);
        }

        .notifications-page__icon {
            align-items: center;
            background: color-mix(in srgb, var(--notification-accent) 12%, white);
            border: 1px solid color-mix(in srgb, var(--notification-accent) 22%, white);
            border-radius: 1rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9), 0 8px 18px color-mix(in srgb, var(--notification-accent) 14%, transparent);
            color: var(--notification-accent);
            display: inline-flex;
            height: 3rem;
            justify-content: center;
            width: 3rem;
        }

        .notifications-page__sidebar,
        .notifications-page__reader {
            min-height: 0;
            overflow: hidden;
        }

        .notifications-page__sidebar-list {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            max-height: none;
            min-height: 0;
            min-width: 0;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: .75rem;
            width: 100%;
        }

        .notifications-page__item {
            animation: notification-arrive .32s ease both;
            background: #fff;
            border: 1px solid transparent;
            border-radius: .95rem;
            box-shadow: 0 3px 8px rgba(15, 23, 42, .035);
            color: inherit;
            display: block;
            flex: 0 0 auto;
            min-width: 0;
            overflow: hidden;
            padding: .9rem;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
            width: 100%;
        }

        .notifications-page__item > span,
        .notifications-page__item .flex-grow-1 {
            min-width: 0;
        }

        .notifications-page__item > span {
            width: 100%;
        }

        .notifications-page__item .flex-grow-1 {
            flex: 1 1 0%;
        }

        .notifications-page__item:hover,
        .notifications-page__item:focus-visible {
            border-color: color-mix(in srgb, var(--notification-accent) 32%, white);
            box-shadow: 0 9px 18px rgba(15, 23, 42, .09);
            color: inherit;
            outline: none;
            transform: translateY(-2px);
        }

        .notifications-page__item.is-selected {
            background: color-mix(in srgb, var(--notification-accent) 8%, white);
            border-color: color-mix(in srgb, var(--notification-accent) 35%, white);
            box-shadow: inset 3px 0 0 var(--notification-accent), 0 8px 18px rgba(15, 23, 42, .07);
        }

        .notifications-page__sidebar {
            display: flex;
            flex-direction: column;
        }

        .notifications-page__reader {
            animation: notification-arrive .38s cubic-bezier(.2, .8, .2, 1) both;
            display: flex;
            flex-direction: column;
            padding: clamp(1.25rem, 3vw, 2.25rem);
            transform-origin: top center;
        }

        .notifications-page__reader-content {
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .notifications-page__reader-actions {
            flex: 0 0 auto;
            margin-top: auto;
        }

        @media (min-width: 992px) {
            body:has(.notifications-page) {
                height: 100vh;
                height: 100dvh;
                overflow: hidden;
            }

            body:has(.notifications-page) #app {
                display: flex;
                flex-direction: column;
                height: 100%;
                min-height: 0;
            }

            body:has(.notifications-page) #app > nav {
                flex: 0 0 auto;
            }

            body:has(.notifications-page) #app > main.app-main {
                display: flex;
                flex: 1 1 0px;
                flex-direction: column;
                height: 0;
                min-height: 0;
                overflow: hidden;
                padding-block: .75rem;
            }

            body:has(.notifications-page) .notifications-page {
                display: flex;
                flex: 1 1 0px;
                flex-direction: column;
                height: 100%;
                max-height: 100%;
                min-height: 0;
                padding-block: 0;
            }

            .notifications-page > .row {
                flex: 1 1 0px;
                height: 100%;
                max-height: 100%;
                min-height: 0;
            }

            .notifications-page > .row > [class*="col-"] {
                display: flex;
                height: 100%;
                max-height: 100%;
                min-height: 0;
            }

            .notifications-page__sidebar,
            .notifications-page__reader {
                flex: 1 1 auto;
                height: 100%;
                max-height: 100%;
                width: 100%;
            }
        }

        .notifications-page__reader-message {
            line-height: 1.8;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .notifications-page__empty {
            align-items: center;
            color: #64748b;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 30rem;
            padding: 2rem;
            text-align: center;
        }

        .notifications-page__empty-icon {
            align-items: center;
            background: #f1f5f9;
            border-radius: 1.25rem;
            box-shadow: inset 0 2px 6px rgba(15, 23, 42, .06);
            color: #64748b;
            display: inline-flex;
            font-size: 1.6rem;
            height: 4.25rem;
            justify-content: center;
            margin-bottom: 1rem;
            width: 4.25rem;
        }

        .notification-modal-content {
            animation: notification-arrive .3s cubic-bezier(.2, .8, .2, 1) both;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 1.25rem;
            box-shadow: 0 28px 80px rgba(15, 23, 42, .24), 0 8px 20px rgba(15, 23, 42, .12);
            overflow: hidden;
        }

        @keyframes notification-arrive {
            from { opacity: 0; transform: translateY(10px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (max-width: 767.98px) {
            .notifications-page__sidebar,
            .notifications-page__reader {
                min-height: auto;
            }

            .notifications-page__sidebar-list {
                max-height: 35vh;
            }
        }

        html[data-bs-theme="dark"] .notifications-page__sidebar,
        html[data-bs-theme="dark"] .notifications-page__reader {
            background: linear-gradient(145deg, #1e293b, #172033);
            border-color: #334155;
            color: #e2e8f0;
        }

        html[data-bs-theme="dark"] .notifications-page__item {
            background: #0f172a;
            color: #e2e8f0;
        }

        html[data-bs-theme="dark"] .notifications-page__item.is-selected {
            background: color-mix(in srgb, var(--notification-accent) 18%, #0f172a);
        }

        html[data-bs-theme="dark"] .notifications-page__empty-icon {
            background: #0f172a;
            color: #cbd5e1;
        }
    </style>
@endpush

@section('content')
    @php
        $selectedId = $selectedNotification['id'] ?? null;
        $notificationList = $notifications->getCollection();
    @endphp

    <main class="container notifications-page">
        <div class="row g-3 g-lg-4">
            <aside class="col-12 col-lg-4 col-xl-3" aria-label="All notifications">
                <section class="notifications-page__sidebar">
                    <div class="d-flex justify-content-between align-items-center gap-2 border-bottom px-3 py-3">
                        <div>
                            <h2 class="h6 fw-bold mb-0">Inbox</h2>
                            <small class="text-secondary">{{ $notifications->total() }} total</small>
                        </div>
                        @if ($unreadCount > 0)
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $unreadCount }} unread</span>
                                <form method="POST" action="{{ route($routePrefix.'notifications.read-all') }}">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                                    <button
                                        type="submit"
                                        class="btn btn-outline-primary btn-sm rounded-pill px-2 text-nowrap"
                                        aria-label="Mark all notifications as read"
                                        title="Mark all as read"
                                    >
                                        <i class="bi bi-check2-all me-1" aria-hidden="true"></i>Mark all as read
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <div class="notifications-page__sidebar-list gap-2">
                        @forelse ($notificationList as $notification)
                            <a
                                class="notifications-page__item {{ $selectedId === $notification['id'] ? 'is-selected' : '' }}"
                                href="{{ route($routePrefix.'notifications.index', ['notification' => $notification['id'], 'return_to' => $returnUrl]) }}"
                                data-notification-page-open
                                aria-current="{{ $selectedId === $notification['id'] ? 'page' : 'false' }}"
                            >
                                <span class="d-flex align-items-start gap-2">
                                    <span class="notifications-page__icon flex-shrink-0" style="width: 2.25rem; height: 2.25rem; border-radius: .75rem;" aria-hidden="true">
                                        <i class="bi {{ $notification['is_achievement'] ? 'bi-trophy-fill' : 'bi-bell-fill' }}"></i>
                                    </span>
                                    <span class="min-w-0 flex-grow-1">
                                        <strong class="d-block text-truncate small">{{ $notification['title'] }}</strong>
                                        <span class="d-block text-secondary small text-truncate mt-1">{{ $notification['message'] }}</span>
                                        <span class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                            <time class="text-secondary small" datetime="{{ $notification['created_at']->toIso8601String() }}">{{ $notification['created_at']->diffForHumans() }}</time>
                                            @if ($notification['read'])
                                                <span class="badge rounded-pill bg-light text-secondary border">Read</span>
                                            @else
                                                <span class="badge rounded-pill bg-primary">New</span>
                                            @endif
                                        </span>
                                    </span>
                                </span>
                            </a>
                        @empty
                            <div class="notifications-page__empty py-5">
                                <span class="notifications-page__empty-icon" aria-hidden="true"><i class="bi bi-bell-slash"></i></span>
                                <h3 class="h6 fw-bold text-body">All caught up</h3>
                                <p class="small mb-0">New notifications will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                    @if ($notifications->hasPages())
                        <div class="border-top p-3">{{ $notifications->links() }}</div>
                    @endif
                </section>
            </aside>

            <section class="col-12 col-lg-8 col-xl-9" aria-label="Notification details">
                <div class="notifications-page__reader" @if ($selectedNotification) data-notification-detail data-notification-id="{{ $selectedNotification['id'] }}" data-notification-read="{{ $selectedNotification['read'] ? 'true' : 'false' }}" data-notification-read-url="{{ $selectedNotification['read_url'] }}" @endif>
                    @if ($selectedNotification)
                        <div class="notifications-page__reader-content">
                            <div class="d-flex justify-content-between align-items-start gap-3 border-bottom pb-3 mb-4">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <span class="notifications-page__icon flex-shrink-0" aria-hidden="true">
                                        <i class="bi {{ $selectedNotification['is_achievement'] ? 'bi-trophy-fill' : 'bi-bell-fill' }} fs-5"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="h4 fw-bold text-body mb-1">{{ $selectedNotification['title'] }}</h2>
                                        <p class="small text-secondary mb-0">From {{ $selectedNotification['sender'] }} · {{ $selectedNotification['created_at']->format('M d, Y · h:i A') }}</p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ $selectedNotification['read_url'] }}" aria-label="Close notification details">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                                    <input type="hidden" name="close_detail" value="1">
                                    <button class="btn btn-light rounded-circle shadow-sm" type="submit" aria-label="Close notification">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>

                            <p class="notifications-page__reader-message text-body mb-4">{{ $selectedNotification['message'] }}</p>

                            @if ($selectedNotification['details'])
                                <div class="card border-0 bg-body-tertiary rounded-4 mb-4">
                                    <div class="card-body">
                                        <h3 class="h6 fw-bold mb-3">Faculty breakdown</h3>
                                        <div class="d-grid gap-2">
                                            @foreach ($selectedNotification['details'] as $detail)
                                                <div class="d-flex justify-content-between gap-3 border-bottom pb-2">
                                                    <span>{{ $detail['faculty_name'] }}</span>
                                                    <strong>{{ $detail['questions_added'] }} questions</strong>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="notifications-page__reader-actions d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-3">
                            @if ($selectedNotification['url'])
                                <a class="btn btn-primary rounded-pill px-4" href="{{ $selectedNotification['url'] }}" data-notification-page-leave>
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Open related page
                                </a>
                            @else
                                <span class="small text-secondary">Close this message to mark it as read.</span>
                            @endif
                            <form
                                method="POST"
                                action="{{ $selectedNotification['delete_url'] }}"
                                data-confirm-title="Delete this notification?"
                                data-confirm-text="This notification will be permanently removed from your inbox."
                                data-confirm-button="Delete notification"
                            >
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                                <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="notifications-page__empty">
                            <span class="notifications-page__empty-icon" aria-hidden="true"><i class="bi bi-envelope-open"></i></span>
                            <h2 class="h4 fw-bold text-body">Select a notification</h2>
                            <p class="mb-0">Choose a message from your inbox to read it here.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </main>
@endsection
