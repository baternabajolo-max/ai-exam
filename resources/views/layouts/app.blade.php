<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'LEMU'))</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/images/systemlogo.jpg') }}">

    <!-- Bootstrap CSS & Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.3/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/password-visibility.css') }}">

    @auth
        @if(auth()->user()->role === 'student')
            @php
                $studentDepartment = auth()->user()->department;
                $studentDepartmentName = strtolower($studentDepartment?->name ?? '');
                $studentTheme = $studentDepartment?->color_theme;
                if (! $studentTheme) {
                    $studentTheme = str_contains($studentDepartmentName, 'engineering')
                        ? '#0d6efd'
                        : (str_contains($studentDepartmentName, 'education') ? '#198754' : '#'.substr(md5($studentDepartment?->name ?? 'LEMU'), 0, 6));
                }
            @endphp
            <style>
                :root { 
                    --student-theme: {{ $studentTheme }}; 
                }
                body.student-theme { 
                    --bs-primary: var(--student-theme); 
                    --bs-primary-rgb: {{ implode(',', sscanf($studentTheme, "#%02x%02x%02x")) }};
                    background-color: color-mix(in srgb, var(--student-theme) 14%, #f8fafc);
                }
                .student-theme .navbar { 
                    border-bottom: 3px solid color-mix(in srgb, var(--student-theme) 62%, white); 
                }
                .student-theme .navbar-brand, 
                .student-theme .nav-link:hover, 
                .student-theme .nav-link.active { 
                    color: var(--student-theme); 
                }
                .student-theme .card { 
                    border-top: 3px solid color-mix(in srgb, var(--student-theme) 62%, white); 
                }
            </style>
        @endif
    @endauth

    <style>
        /* Streak Badge Styles */
        .student-streak-indicator { 
            align-items: center; 
            background: #fff7ed; 
            border: 1px solid #fed7aa; 
            border-radius: 999px; 
            color: #ea580c; 
            display: inline-flex; 
            font-size: .875rem; 
            font-weight: 700; 
            gap: .35rem; 
            line-height: 1; 
            padding: .45rem .75rem; 
            white-space: nowrap; 
        }
        .student-streak-indicator.frozen { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
        .student-streak-indicator.inactive { background: #f1f5f9; border-color: #cbd5e1; color: #64748b; }

        .admin-nav-search {
            flex: 0 1 18rem;
            min-width: 13rem;
            position: relative;
        }

        .navbar-nav .nav-link {
            white-space: nowrap;
        }

        .admin-nav-search-results {
            left: 0;
            max-height: 20rem;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: calc(100% + .5rem);
            z-index: 1090;
        }

        .admin-nav-search-results[hidden] {
            display: none;
        }

        @media (max-width: 1399.98px) {
            .admin-nav-search {
                flex-basis: 100%;
                margin: .5rem 0;
            }
        }

        @media (max-width: 767.98px) {
            .admin-nav-search-results {
                max-height: 16rem;
            }
        }

        /* UI Micro-interactions */
        .card, .btn, .modal-content { transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .08) !important; }
        .btn:active { transform: scale(.98); }
        #ai-loading-overlay { backdrop-filter: blur(6px); }
        #app-toast-container { z-index: 1090; }
        #app-toast-container .toast.toast-success {
            background-color: #198754;
            color: #fff;
        }
        #app-toast-container .toast.toast-danger {
            background-color: #b02a37;
            color: #fff;
        }
        #app-toast-container .toast-success .toast-header,
        #app-toast-container .toast-danger .toast-header {
            background-color: transparent;
            color: #fff;
            border-bottom-color: rgba(255, 255, 255, .25);
        }
        #app-toast-container .toast-success .toast-body,
        #app-toast-container .toast-danger .toast-body {
            color: #fff;
        }

        /* Dark Mode Theme Rules */
        html[data-bs-theme="dark"] body { background-color: #0f172a; color: #e2e8f0; }
        html[data-bs-theme="dark"] .card, 
        html[data-bs-theme="dark"] .modal-content, 
        html[data-bs-theme="dark"] .navbar,
        html[data-bs-theme="dark"] .dropdown-menu { background-color: #1e293b !important; color: #e2e8f0; border-color: #334155; }
        html[data-bs-theme="dark"] .text-muted, 
        html[data-bs-theme="dark"] .form-label { color: #94a3b8 !important; }
        html[data-bs-theme="dark"] .form-control, 
        html[data-bs-theme="dark"] .form-select { background-color: #0f172a; border-color: #475569; color: #e2e8f0; }
        html[data-bs-theme="dark"] .list-group-item { background-color: #1e293b; color: #e2e8f0; border-color: #334155; }
        html[data-bs-theme="dark"] .list-group-item.bg-light { background-color: #334155 !important; }
        html[data-bs-theme="dark"] .dropdown-item { color: #e2e8f0; }
        html[data-bs-theme="dark"] .dropdown-item:hover { background-color: #334155; }

        /* Keep page content consistently spaced below the sticky navigation.
           Individual page containers should not add another top offset. */
        #app > main.app-main {
            padding-block: 1.5rem 2rem;
        }

        #app > main.app-main.py-0 {
            padding-block: 0 !important;
        }

        #app > main.app-main > .container,
        #app > main.app-main > main {
            padding-top: 0 !important;
        }

        #app > main.app-main > [class*="py-"],
        #app > main.app-main > [class*=" pt-"] {
            padding-top: 0 !important;
        }

        @media (min-width: 992px) {
            #app > main.app-main {
                padding-block: 2rem 2.5rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body @auth @if(auth()->user()->role === 'student') class="student-theme" @elseif(auth()->user()->role === 'super-admin') class="super-admin-theme" @endif @endauth>
    <div id="app">
        @unless(View::hasSection('hide-navbar') || View::hasSection('hide-nav'))
            <nav class="navbar navbar-expand-xxl navbar-light bg-white shadow-sm sticky-top">
                <div class="container-fluid px-3 px-xxl-4">
                    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/dashboard') }}">
                        <img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="" width="36" height="36" class="rounded-circle bg-white object-fit-contain p-1">
                        <span>{{ config('app.name', 'LEMU') }}</span>
                    </a>
                    
                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Navigation Links -->
                        <ul class="navbar-nav me-auto gap-1">
                            @auth
                                @if(auth()->user()->role === 'student')
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('student.learn.*') ? 'active fw-semibold' : '' }}" href="{{ route('student.learn.index') }}">Learn</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('student.exams.*') ? 'active fw-semibold' : '' }}" href="{{ route('student.exams.index') }}">Examinations</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('student.achievements') ? 'active fw-semibold' : '' }}" href="{{ route('student.achievements') }}">Achievements</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('student.flashcards.*') ? 'active fw-semibold' : '' }}" href="{{ route('student.flashcards.index') }}">Flashcards</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('student.leaderboard') ? 'active fw-semibold' : '' }}" href="{{ route('student.leaderboard') }}">Leaderboard</a>
                                    </li>
                                @elseif(auth()->user()->role === 'admin')
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.students') }}" class="nav-link {{ request()->routeIs('admin.students*') ? 'active fw-semibold' : '' }}">Manage Students</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.modules.index') }}" class="nav-link {{ request()->routeIs('admin.modules*') ? 'active fw-semibold' : '' }}">Modules</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.question-bank.index') }}" class="nav-link {{ request()->routeIs('admin.question-bank*') ? 'active fw-semibold' : '' }}">Question Bank</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.tests.index') }}" class="nav-link {{ request()->routeIs('admin.tests*') ? 'active fw-semibold' : '' }}">Examinations</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.leaderboard') }}" class="nav-link {{ request()->routeIs('admin.leaderboard*') ? 'active fw-semibold' : '' }}">Leaderboard</a>
                                    </li>
                                @elseif(auth()->user()->role === 'super-admin')
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.departments*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.departments.index') }}">Colleges</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.users*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.users.index') }}">Faculty</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.monitoring*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.monitoring') }}">Monitoring</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.tests*', 'super-admin.item-analysis*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.tests.index') }}">Examinations &amp; Analysis</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.question-bank*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.question-bank.index') }}">Question Bank</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.leaderboard*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.leaderboard') }}">Leaderboard</a>
                                    </li>
                                @endif
                            @endauth
                        </ul>

                        @auth
                            @if(in_array(Auth::user()->role, ['admin', 'super-admin'], true))
                                @php
                                    $studentSearchDashboardRoute = Auth::user()->role === 'super-admin'
                                        ? 'super-admin.dashboard'
                                        : 'admin.dashboard';
                                    $studentSuggestionsRoute = Auth::user()->role === 'super-admin'
                                        ? 'super-admin.students.search-suggestions'
                                        : 'admin.students.search-suggestions';
                                @endphp
                                <form action="{{ route($studentSearchDashboardRoute) }}" method="GET" class="admin-nav-search d-flex" role="search" data-suggestions-url="{{ route($studentSuggestionsRoute) }}">
                                    @if(Auth::user()->role === 'super-admin')
                                        <input type="hidden" name="college_id" value="{{ request('college_id') }}">
                                        @if(request()->routeIs('super-admin.dashboard'))
                                            <input type="hidden" name="period" value="{{ request('period', '30') }}">
                                            @if(request('period') === 'custom')
                                                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                                                <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                                            @endif
                                        @endif
                                    @endif
                                    <label class="visually-hidden" for="admin-nav-student-search">Search students by name, email, or student ID</label>
                                    <input
                                        id="admin-nav-student-search"
                                        type="search"
                                        name="student_search"
                                        value="{{ request('student_search') }}"
                                        class="form-control form-control-sm rounded-start-pill"
                                        placeholder="Search students..."
                                        autocomplete="off"
                                        aria-autocomplete="list"
                                        aria-controls="admin-nav-student-search-results"
                                        aria-expanded="false"
                                    >
                                    <button class="btn btn-primary btn-sm rounded-end-pill px-3" type="submit" aria-label="Search students">
                                        <i class="bi bi-search" aria-hidden="true"></i>
                                    </button>
                                    <div id="admin-nav-student-search-results" class="admin-nav-search-results list-group shadow" role="region" aria-label="Student search suggestions" aria-live="polite" hidden></div>
                                </form>
                            @endif
                        @endauth

                        <!-- Right Navigation Controls -->
                        <ul class="navbar-nav ms-auto align-items-md-center gap-2">
                            @guest
                                @if (Route::has('login'))
                                    <li class="nav-item">
                                        <a class="btn btn-outline-primary px-4" href="{{ route('login') }}">Login</a>
                                    </li>
                                @endif
                            @else
                                @if(Auth::user()->role === 'student')
                                    @php
                                        $dailyStreakState = Auth::user()->daily_streak_state;
                                        $visibleDailyStreak = Auth::user()->visible_daily_streak;
                                    @endphp
                                    <li class="nav-item">
                                        <span
                                            class="student-streak-indicator {{ $dailyStreakState }}"
                                            title="{{ $dailyStreakState === 'frozen' ? 'Streak frozen after missing one day' : ($dailyStreakState === 'inactive' ? 'Streak reset after missing two days' : 'Complete one quiz or general test each day to keep your streak') }}"
                                            aria-label="{{ $visibleDailyStreak }} day streak{{ $dailyStreakState === 'frozen' ? ', frozen' : '' }}"
                                        >
                                            <i class="bi {{ $dailyStreakState === 'frozen' ? 'bi-snow' : 'bi-fire' }}" aria-hidden="true"></i>
                                            <span>{{ $visibleDailyStreak }}</span>
                                            <span class="d-md-none">day streak</span>
                                        </span>
                                    </li>

                                    <!-- Notifications Dropdown -->
                                    <li class="nav-item dropdown">
                                        <button class="nav-link btn btn-link position-relative border-0" id="studentNotificationsDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                                            <i class="bi bi-bell-fill fs-5" aria-hidden="true"></i>
                                            <span class="d-md-none ms-2">Notifications</span>
                                            @if(($unreadStudentNotificationCount ?? 0) > 0)
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" data-notification-unread-badge>
                                                    {{ $unreadStudentNotificationCount > 99 ? '99+' : $unreadStudentNotificationCount }}
                                                    <span class="visually-hidden">unread notifications</span>
                                                </span>
                                            @endif
                                        </button>
                                        
                                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0" aria-labelledby="studentNotificationsDropdown" style="min-width: 320px; max-width: min(92vw, 380px);">
                                            <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2 bg-body-tertiary rounded-top">
                                                <strong class="text-dark">Notifications</strong>
                                                @if(($unreadStudentNotificationCount ?? 0) > 0)
                                                    <span class="badge bg-primary rounded-pill" data-notification-unread-summary>{{ $unreadStudentNotificationCount }} new</span>
                                                @endif
                                            </div>
                                            
                                            <div class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
                                                @forelse($studentNavNotifications ?? [] as $notification)
                                                    <div class="list-group-item {{ $notification->read_at ? '' : 'bg-light' }} p-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-link text-start text-decoration-none w-100 p-1 notification-modal-trigger text-dark"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#studentNotificationModal"
                                                            data-notification-title="{{ $notification->subject }}"
                                                            data-notification-message="{{ $notification->message }}"
                                                            data-notification-sender="{{ $notification->admin?->name ?? config('app.name', 'LEMU') }}"
                                                            data-notification-read="{{ $notification->read_at ? 'true' : 'false' }}"
                                                            data-notification-achievement="{{ str_starts_with($notification->subject, 'Achievement unlocked:') ? 'true' : 'false' }}"
                                                            data-notification-read-url="{{ route('student.notifications.read', $notification) }}"
                                                            data-notification-delete-url="{{ route('student.notifications.destroy', $notification) }}"
                                                        >
                                                            <strong class="small d-block text-truncate fw-semibold">{{ $notification->subject }}</strong>
                                                            <span class="small text-muted d-block text-truncate">{{ \Illuminate\Support\Str::limit($notification->message, 80) }}</span>
                                                        </button>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted px-3 py-4">
                                                        <i class="bi bi-bell-slash fs-4 d-block mb-1" aria-hidden="true"></i>
                                                        <p class="small mb-0">You have no notifications.</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                            
                                            <div class="d-flex border-top bg-body-tertiary rounded-bottom">
                                                <a class="dropdown-item flex-fill text-center py-2 border-end small" href="{{ route('student.notifications.index') }}">View all</a>
                                                <form
                                                    class="flex-fill"
                                                    method="POST"
                                                    action="{{ route('student.notifications.destroy-all') }}"
                                                    data-confirm-title="Delete all notifications?"
                                                    data-confirm-text="This will permanently remove all your notifications."
                                                    data-confirm-button="Delete all"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item w-100 text-center text-danger py-2 small">Delete all</button>
                                                </form>
                                            </div>
                                        </div>
                                    </li>
                                @elseif(Auth::user()->role === 'super-admin')
                                    <li class="nav-item dropdown">
                                        <button class="nav-link btn btn-link position-relative border-0" id="adminNotificationsDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Admin notifications">
                                            <i class="bi bi-bell-fill fs-5" aria-hidden="true"></i>
                                            <span class="d-md-none ms-2">Notifications</span>
                                            @if(($unreadAdminNotificationCount ?? 0) > 0)
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                                    {{ $unreadAdminNotificationCount > 99 ? '99+' : $unreadAdminNotificationCount }}
                                                    <span class="visually-hidden">unread notifications</span>
                                                </span>
                                            @endif
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0" aria-labelledby="adminNotificationsDropdown" style="min-width: 320px; max-width: min(92vw, 420px);">
                                            <div class="border-bottom px-3 py-2 bg-body-tertiary rounded-top">
                                                <strong class="text-dark">Admin notifications</strong>
                                                @if(($unreadAdminNotificationCount ?? 0) > 0)
                                                    <span class="badge bg-primary rounded-pill ms-2">{{ $unreadAdminNotificationCount }} new</span>
                                                @endif
                                            </div>
                                            <div class="list-group list-group-flush" style="max-height: 420px; overflow-y: auto;">
                                                @forelse($adminNavNotifications ?? [] as $notification)
                                                    <div class="list-group-item {{ $notification->read_at ? '' : 'bg-light' }} p-3">
                                                        <a
                                                            class="admin-notification-trigger d-block text-decoration-none text-reset"
                                                            href="{{ $notification->data['url'] ?? route('super-admin.question-bank.index') }}"
                                                            data-notification-read="{{ $notification->read_at ? 'true' : 'false' }}"
                                                            data-notification-read-url="{{ route('super-admin.notifications.read', $notification->id) }}"
                                                        >
                                                        <strong class="small d-block">{{ $notification->data['title'] ?? 'Notification' }}</strong>
                                                        @if (!empty($notification->data['message']))
                                                            <span class="small text-muted d-block">{{ $notification->data['message'] }}</span>
                                                            <span class="btn btn-sm btn-primary mt-2">
                                                                <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i> Review examination
                                                            </span>
                                                        @else
                                                            <span class="small text-muted d-block">
                                                                {{ $notification->data['report_date'] ?? $notification->created_at?->toDateString() }}
                                                                · {{ $notification->data['total_questions_added'] ?? 0 }} question(s) added
                                                            </span>
                                                        @endif
                                                        </a>
                                                        @if(!empty($notification->data['faculty_counts']))
                                                            <details class="small mt-2">
                                                                <summary>View Faculty breakdown</summary>
                                                                <ul class="mb-0 mt-1 ps-3">
                                                                    @foreach($notification->data['faculty_counts'] as $facultyCount)
                                                                        <li>{{ $facultyCount['faculty_name'] }}: {{ $facultyCount['questions_added'] }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </details>
                                                        @endif
                                                        @if(!$notification->read_at)
                                                            <form method="POST" action="{{ route('super-admin.notifications.read', $notification->id) }}" class="mt-2">
                                                                @csrf
                                                                <button class="btn btn-sm btn-outline-primary" type="submit">Mark as read</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted px-3 py-4">
                                                        <i class="bi bi-bell-slash fs-4 d-block mb-1" aria-hidden="true"></i>
                                                        <p class="small mb-0">You have no notifications.</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </li>
                                @endif

                                <!-- User Menu Dropdown -->
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle fw-semibold d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        @if(Auth::user()->role === 'student' && Auth::user()->profile_picture_path)
                                            <img src="{{ asset('storage/'.Auth::user()->profile_picture_path) }}" alt="" class="rounded-circle border" width="32" height="32" style="object-fit: cover;">
                                        @else
                                            <i class="bi bi-person-circle fs-5" aria-hidden="true"></i>
                                        @endif
                                        <span>{{ Auth::user()->name }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary border fs-6 px-2 py-1">{{ Auth::user()->role_label }}</span>
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                                        @if(Auth::user()->role === 'student')
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('student.profile') }}">
                                                <i class="bi bi-person" aria-hidden="true"></i>My Profile
                                            </a>
                                        @elseif(Auth::user()->role === 'admin')
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.profile') }}">
                                                <i class="bi bi-person" aria-hidden="true"></i>My Profile
                                            </a>
                                        @elseif(Auth::user()->role === 'super-admin')
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('super-admin.profile') }}">
                                                <i class="bi bi-person" aria-hidden="true"></i>My Profile
                                            </a>
                                        @endif
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#themeSettingsModal">
                                            <i class="bi bi-sliders" aria-hidden="true"></i>Settings
                                        </a>
                                        <hr class="dropdown-divider">
                                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>
        @endunless

        <main class="app-main {{ View::hasSection('hide-navbar') || View::hasSection('hide-nav') ? 'py-0' : '' }}">
            @yield('content')
        </main>
    </div>

    @php
        $toastNotifications = collect([
            ['type' => 'success', 'title' => 'Success', 'message' => session('success')],
            ['type' => 'danger', 'title' => 'Something needs attention', 'message' => session('error')],
            ['type' => 'warning', 'title' => 'Please note', 'message' => session('warning')],
            ['type' => 'info', 'title' => 'Information', 'message' => session('info')],
            ['type' => 'success', 'title' => 'Notice', 'message' => session('status')],
            ['type' => 'warning', 'title' => 'AI study advice unavailable', 'message' => session('study_advice_error')],
        ])->filter(fn (array $notification): bool => filled($notification['message']))->values();

        if ($errors->any()) {
            $toastNotifications->push([
                'type' => 'danger',
                'title' => 'Please review the form',
                'errors' => $errors->all(),
            ]);
        }
    @endphp
    @if($toastNotifications->isNotEmpty())
        <div id="app-toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="false">
            @foreach($toastNotifications as $notification)
                <div class="toast toast-{{ $notification['type'] }} border-start border-4 border-{{ $notification['type'] }} shadow-sm mb-2"
                     role="{{ $notification['type'] === 'danger' ? 'alert' : 'status' }}"
                     aria-live="{{ $notification['type'] === 'danger' ? 'assertive' : 'polite' }}"
                     aria-atomic="true"
                     data-bs-autohide="{{ $notification['type'] === 'danger' ? 'false' : 'true' }}"
                     data-bs-delay="7000">
                    <div class="toast-header">
                        <i class="bi bi-{{ $notification['type'] === 'success' ? 'check-circle-fill' : ($notification['type'] === 'danger' ? 'exclamation-triangle-fill' : ($notification['type'] === 'warning' ? 'exclamation-circle-fill' : 'info-circle-fill')) }} {{ in_array($notification['type'], ['success', 'danger'], true) ? 'text-white' : 'text-'.$notification['type'] }} me-2" aria-hidden="true"></i>
                        <strong class="me-auto">{{ $notification['title'] }}</strong>
                        <button type="button" class="btn-close {{ in_array($notification['type'], ['success', 'danger'], true) ? 'btn-close-white' : '' }}" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        @if(isset($notification['errors']))
                            <ul class="mb-0 ps-3">
                                @foreach($notification['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @else
                            {{ $notification['message'] }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- AI Loading Overlay -->
    @unless(View::hasSection('disable-ai-loading'))
        <div id="ai-loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center bg-dark bg-opacity-50" role="dialog" aria-modal="true" aria-labelledby="ai-loading-title" aria-describedby="ai-loading-message" style="z-index: 2000;">
            <div class="card border-0 shadow-lg p-4 text-center" style="max-width: 360px;">
                <div class="spinner-border text-primary mx-auto mb-3" role="status">
                    <span class="visually-hidden">AI is working...</span>
                </div>
                <h2 class="h5 fw-bold mb-1" id="ai-loading-title">AI is working</h2>
                <p class="text-muted mb-0 small" id="ai-loading-message">Please wait while the requested AI task is completed.</p>
            </div>
        </div>
    @endunless

    <!-- Theme Settings Modal -->
    @auth
        <div class="modal fade" id="themeSettingsModal" tabindex="-1" aria-labelledby="themeSettingsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h2 class="modal-title h5 d-flex align-items-center gap-2" id="themeSettingsModalLabel">
                            <i class="bi bi-sliders" aria-hidden="true"></i>Settings
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="themePreference" class="form-label fw-semibold">Appearance</label>
                        <select id="themePreference" class="form-select">
                            <option value="system">System default</option>
                            <option value="light">Light mode</option>
                            <option value="dark">Dark mode</option>
                        </select>
                        <p class="text-muted small mt-2 mb-0">Choose how LEMU should look on this device.</p>
                    </div>
                </div>
            </div>
        </div>

        @if(Auth::user()->role === 'student')
            <!-- Student Notification Modal -->
            <div class="modal fade" id="studentNotificationModal" tabindex="-1" aria-labelledby="studentNotificationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title h5 fw-bold mb-1" id="studentNotificationModalLabel"></h2>
                                <small class="text-muted">From <span id="studentNotificationSender" class="fw-semibold"></span></small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="studentNotificationMessage" class="mb-0 text-break lh-base" style="white-space: pre-wrap;"></p>
                            <p id="studentNotificationReadStatus" class="small text-muted mt-3 mb-0"></p>
                        </div>
                        <div class="modal-footer">
                            <a id="studentAchievementLink" href="{{ route('student.achievements') }}" class="btn btn-outline-primary d-none me-auto">
                                <i class="bi bi-award me-1" aria-hidden="true"></i>Go to achievements
                            </a>
                            <form id="studentNotificationDeleteForm" method="POST" class="me-auto">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.showToast = function (message, type = 'info', title = '') {
            const allowedTypes = ['success', 'danger', 'warning', 'info'];
            const toastType = allowedTypes.includes(type) ? type : 'info';
            const titles = {
                success: 'Success',
                danger: 'Something needs attention',
                warning: 'Please note',
                info: 'Information',
            };
            const icons = {
                success: 'check-circle-fill',
                danger: 'exclamation-triangle-fill',
                warning: 'exclamation-circle-fill',
                info: 'info-circle-fill',
            };
            const container = document.getElementById('app-toast-container') || document.body.appendChild(
                Object.assign(document.createElement('div'), {
                    id: 'app-toast-container',
                    className: 'toast-container position-fixed bottom-0 end-0 p-3',
                })
            );
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'false');
            const toast = document.createElement('div');
            toast.className = `toast toast-${toastType} border-start border-4 border-${toastType} shadow-sm mb-2`;
            toast.setAttribute('role', toastType === 'danger' ? 'alert' : 'status');
            toast.setAttribute('aria-live', toastType === 'danger' ? 'assertive' : 'polite');
            toast.setAttribute('aria-atomic', 'true');
            toast.dataset.bsAutohide = toastType === 'danger' ? 'false' : 'true';
            toast.dataset.bsDelay = '7000';

            const header = document.createElement('div');
            header.className = 'toast-header';
            const icon = document.createElement('i');
            icon.className = `bi bi-${icons[toastType]} ${['success', 'danger'].includes(toastType) ? 'text-white' : `text-${toastType}`} me-2`;
            icon.setAttribute('aria-hidden', 'true');
            const heading = document.createElement('strong');
            heading.className = 'me-auto';
            heading.textContent = title || titles[toastType];
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = `btn-close ${['success', 'danger'].includes(toastType) ? 'btn-close-white' : ''}`;
            closeButton.dataset.bsDismiss = 'toast';
            closeButton.setAttribute('aria-label', 'Close');
            header.append(icon, heading, closeButton);

            const body = document.createElement('div');
            body.className = 'toast-body';
            body.textContent = message;
            toast.append(header, body);
            container.append(toast);
            bootstrap.Toast.getOrCreateInstance(toast).show();
        };

        window.showAiLoading = function (title, message) {
            const overlay = document.getElementById('ai-loading-overlay');
            if (!overlay) {
                return;
            }

            document.getElementById('ai-loading-title').textContent = title || 'AI is working';
            document.getElementById('ai-loading-message').textContent = message || 'Please wait while the requested AI task is completed.';
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
        };

        document.querySelectorAll('#app-toast-container .toast').forEach(function (toast) {
            bootstrap.Toast.getOrCreateInstance(toast).show();
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('[data-ai-loading]');
            if (form) {
                window.showAiLoading(form.dataset.aiLoadingTitle, form.dataset.aiLoadingMessage);
            }
        });

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('a[data-ai-loading]');
            if (trigger) {
                window.showAiLoading(trigger.dataset.aiLoadingTitle, trigger.dataset.aiLoadingMessage);
            }
        });

        // Dynamic Theme Management
        function applyTheme(theme) {
            const root = document.documentElement;
            if (theme === 'system') {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                root.setAttribute('data-bs-theme', systemDark ? 'dark' : 'light');
            } else {
                root.setAttribute('data-bs-theme', theme);
            }
        }

        const savedTheme = localStorage.getItem('lemu-theme') || 'system';
        applyTheme(savedTheme);

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (localStorage.getItem('lemu-theme') === 'system') {
                applyTheme('system');
            }
        });

        document.getElementById('themePreference')?.addEventListener('change', function (event) {
            const selectedTheme = event.target.value;
            localStorage.setItem('lemu-theme', selectedTheme);
            applyTheme(selectedTheme);
        });

        document.getElementById('themeSettingsModal')?.addEventListener('show.bs.modal', function () {
            document.getElementById('themePreference').value = localStorage.getItem('lemu-theme') || 'system';
        });

        // Notification Modal Data Population
        function updateUnreadNotificationCount(count) {
            const unreadCount = Math.max(0, Number(count) || 0);
            document.querySelectorAll('[data-notification-unread-badge]').forEach(function (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badge.classList.remove('d-none');
                } else {
                    badge.remove();
                }
            });

            document.querySelectorAll('[data-notification-unread-summary]').forEach(function (summary) {
                if (unreadCount > 0) {
                    summary.textContent = unreadCount + ' new';
                } else {
                    summary.remove();
                }
            });

            document.querySelectorAll('[data-notification-page-unread]').forEach(function (badge) {
                if (unreadCount === 0) {
                    badge.remove();
                }
            });
        }

        document.querySelectorAll('.notification-modal-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                document.getElementById('studentNotificationModalLabel').textContent = trigger.dataset.notificationTitle;
                document.getElementById('studentNotificationMessage').textContent = trigger.dataset.notificationMessage;
                document.getElementById('studentNotificationSender').textContent = trigger.dataset.notificationSender;
                document.getElementById('studentNotificationReadStatus').textContent = trigger.dataset.notificationRead === 'true' ? 'Status: Already read' : 'Status: Unread';
                document.getElementById('studentNotificationDeleteForm').action = trigger.dataset.notificationDeleteUrl;
                document.getElementById('studentAchievementLink').classList.toggle('d-none', trigger.dataset.notificationAchievement !== 'true');

                if (trigger.dataset.notificationRead !== 'true') {
                    fetch(trigger.dataset.notificationReadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then(function (response) {
                        if (!response.ok) {
                            throw new Error('Unable to mark notification as read.');
                        }
                        return response.json();
                    }).then(function (payload) {
                        document.getElementById('studentNotificationReadStatus').textContent = 'Status: Read';
                        trigger.dataset.notificationRead = 'true';
                        trigger.closest('.list-group-item')?.classList.remove('bg-light');
                        trigger.closest('article')?.classList.remove('border-primary', 'bg-primary-subtle');
                        trigger.closest('article')?.querySelector('.badge.bg-primary.rounded-pill')?.classList.replace('bg-primary', 'bg-light');
                        trigger.closest('article')?.querySelector('.badge.rounded-pill')?.classList.replace('text-primary', 'text-secondary');
                        const articleUnreadBadge = trigger.closest('article')?.querySelector('.badge.bg-primary.rounded-pill');
                        if (articleUnreadBadge) {
                            articleUnreadBadge.textContent = 'Read';
                            articleUnreadBadge.classList.remove('bg-primary', 'text-white');
                            articleUnreadBadge.classList.add('bg-light', 'text-secondary', 'border');
                        }
                        updateUnreadNotificationCount(payload.unread_count);
                    }).catch(function () {
                        document.getElementById('studentNotificationReadStatus').textContent = 'Status: Could not update';
                    });
                }
            });
        });

        document.querySelectorAll('.admin-notification-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                if (trigger.dataset.notificationRead === 'true') {
                    return;
                }

                event.preventDefault();
                if (trigger.dataset.notificationMarking === 'true') {
                    return;
                }
                trigger.dataset.notificationMarking = 'true';

                fetch(trigger.dataset.notificationReadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Unable to mark notification as read.');
                    }

                    trigger.dataset.notificationRead = 'true';
                    window.location.assign(trigger.href);
                }).catch(function () {
                    trigger.dataset.notificationMarking = 'false';
                    window.showToast('This notification could not be marked as read. Please try again.', 'danger');
                });
            });
        });
    </script>
    @auth
        @if(in_array(Auth::user()->role, ['admin', 'super-admin'], true))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const searchForm = document.querySelector('.admin-nav-search');
                    const searchInput = document.getElementById('admin-nav-student-search');
                    const results = document.getElementById('admin-nav-student-search-results');
                    let debounceTimer;
                    let activeRequest;

                    if (!searchForm || !searchInput || !results) {
                        return;
                    }

                    function hideResults() {
                        results.hidden = true;
                        searchInput.setAttribute('aria-expanded', 'false');
                    }

                    function showMessage(message) {
                        results.replaceChildren();
                        const item = document.createElement('div');
                        item.className = 'list-group-item small text-muted';
                        item.textContent = message;
                        results.append(item);
                        results.hidden = false;
                        searchInput.setAttribute('aria-expanded', 'true');
                    }

                    searchInput.addEventListener('input', function () {
                        window.clearTimeout(debounceTimer);
                        activeRequest?.abort();

                        const query = searchInput.value.trim();
                        if (query.length === 0) {
                            hideResults();
                            return;
                        }

                        showMessage('Searching students...');
                        debounceTimer = window.setTimeout(async function () {
                            activeRequest = new AbortController();
                            const url = new URL(searchForm.dataset.suggestionsUrl, window.location.origin);
                            url.searchParams.set('q', query);
                            const collegeId = searchForm.querySelector('[name="college_id"]')?.value;
                            if (collegeId) {
                                url.searchParams.set('college_id', collegeId);
                            }

                            try {
                                const response = await fetch(url, {
                                    headers: { Accept: 'application/json' },
                                    signal: activeRequest.signal,
                                });

                                if (!response.ok) {
                                    throw new Error('Student search request failed.');
                                }

                                const payload = await response.json();
                                if (searchInput.value.trim() !== query) {
                                    return;
                                }

                                results.replaceChildren();

                                if (payload.data.length === 0) {
                                    showMessage('No students found.');
                                    return;
                                }

                                payload.data.forEach(function (student) {
                                    const link = document.createElement('a');
                                    link.className = 'list-group-item list-group-item-action';
                                    link.href = student.url;

                                    const name = document.createElement('strong');
                                    name.className = 'd-block';
                                    name.textContent = student.name;
                                    link.append(name);

                                    const email = document.createElement('small');
                                    email.className = 'text-muted';
                                    email.textContent = student.email;
                                    link.append(email);

                                    results.append(link);
                                });

                                results.hidden = false;
                                searchInput.setAttribute('aria-expanded', 'true');
                            } catch (error) {
                                if (error.name !== 'AbortError') {
                                    showMessage('Search is unavailable. Please try again.');
                                }
                            }
                        }, 200);
                    });

                    searchInput.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            hideResults();
                        }
                    });

                    document.addEventListener('click', function (event) {
                        if (!searchForm.contains(event.target)) {
                            hideResults();
                        }
                    });
                });
            </script>
        @endif
    @endauth
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.3/dist/sweetalert2.all.min.js"></script>
    <script src="{{ asset('js/confirm-actions.js') }}" defer></script>
    @stack('scripts')
    <script src="{{ asset('js/password-visibility.js') }}" defer></script>
</body>
</html>