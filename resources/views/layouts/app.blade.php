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
        .kpi-info-trigger {
            align-items: center;
            background: transparent;
            border: 0;
            color: var(--bs-secondary-color, #64748b);
            cursor: help;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: .9rem;
            justify-content: center;
            line-height: 1;
            padding: .1rem;
        }
        .kpi-info-trigger:hover,
        .kpi-info-trigger:focus-visible {
            color: var(--bs-primary, #0d6efd);
        }
        .kpi-info-trigger:focus-visible {
            border-radius: 50%;
            outline: 2px solid currentColor;
            outline-offset: 2px;
        }

        .admin-nav-search {
            flex: 0 1 18rem;
            min-width: 13rem;
            position: relative;
        }

        :root {
            --role-sidebar-expanded-width: 16.5rem;
            --role-sidebar-collapsed-width: 4.75rem;
            --role-sidebar-width: var(--role-sidebar-expanded-width);
        }

        html.role-sidebar-collapsed {
            --role-sidebar-width: var(--role-sidebar-collapsed-width);
        }

        .navbar-nav .nav-link {
            white-space: nowrap;
        }

        @media (min-width: 992px) {
            .role-sidebar-navbar {
                height: 3.75rem;
                min-height: 3.75rem;
                position: fixed !important;
                inset: 0 0 auto;
                width: 100%;
                z-index: 1030;
            }

            .role-sidebar-navbar .container-fluid {
                min-height: 0;
                gap: .5rem;
            }

            .role-sidebar-navbar .navbar-toggler {
                display: none;
            }

            .role-sidebar-navbar .navbar-collapse {
                align-items: center;
                display: flex !important;
                flex-basis: auto;
                flex-wrap: nowrap;
            }

            .role-sidebar-navbar .navbar-nav.me-auto {
                align-items: stretch;
                background: var(--bs-body-bg, #fff);
                border-right: 1px solid rgba(148, 163, 184, .24);
                bottom: 0;
                box-shadow: 8px 0 24px rgba(15, 23, 42, .04);
                flex-direction: column;
                gap: .3rem !important;
                left: 0;
                margin: 0 !important;
                overflow-x: hidden;
                overflow-y: auto;
                padding: 1rem .75rem;
                position: fixed;
                top: 3.75rem;
                transition: width .22s ease-out, padding .22s ease-out;
                width: var(--role-sidebar-width);
                z-index: 1020;
            }

            .role-sidebar-navbar .navbar-nav.me-auto .nav-link {
                align-items: center;
                border-radius: .75rem;
                display: flex;
                gap: .75rem;
                min-height: 2.75rem;
                padding: .65rem .8rem;
                text-align: left;
                white-space: normal;
            }

            .role-sidebar-navbar .sidebar-nav-icon {
                align-items: center;
                display: inline-flex;
                flex: 0 0 1.25rem;
                font-size: 1.05rem;
                justify-content: center;
                width: 1.25rem;
            }

            .role-sidebar-navbar .sidebar-nav-label {
                animation: none !important;
                transition: none !important;
                white-space: nowrap;
            }

            .role-sidebar-navbar .sidebar-toggle-btn {
                align-items: center;
                background: #fff;
                border: 1px solid rgba(148, 163, 184, .24);
                border-radius: .65rem;
                color: #64748b;
                cursor: pointer;
                display: inline-flex;
                height: 2.5rem;
                justify-content: center;
                padding: 0;
                transition: background .18s ease, color .18s ease;
                width: 2.5rem;
            }

            .role-sidebar-navbar .sidebar-toggle-btn i {
                transition: transform .18s ease-out;
            }

            .role-sidebar-navbar .sidebar-toggle-btn:hover,
            .role-sidebar-navbar .sidebar-toggle-btn:focus-visible {
                background: #f1f5f9;
                color: #334155;
                outline: 2px solid color-mix(in srgb, var(--bs-primary) 45%, transparent);
                outline-offset: 2px;
            }

            .role-sidebar-navbar .navbar-nav.me-auto .nav-link:hover,
            .role-sidebar-navbar .navbar-nav.me-auto .nav-link:focus-visible {
                background: color-mix(in srgb, var(--bs-primary) 8%, white);
                color: var(--bs-primary);
            }

            .role-sidebar-navbar .navbar-nav.me-auto .nav-link.active {
                background: color-mix(in srgb, var(--bs-primary) 12%, white);
                color: var(--bs-primary);
            }

            .role-sidebar-navbar .admin-nav-search {
                flex: 0 1 22rem;
                max-width: min(28rem, 38vw);
                min-width: 16rem;
                margin-left: auto;
                width: 22rem;
            }

            .role-sidebar-navbar .navbar-nav.ms-auto {
                flex-direction: row;
                flex-shrink: 0;
                margin-left: .75rem !important;
            }

            .role-sidebar-navbar .sidebar-account-links {
                border-top: 1px solid rgba(148, 163, 184, .24);
                margin-top: auto;
                padding-top: .75rem;
                width: 100%;
            }

            .role-sidebar-navbar .sidebar-account-links .nav-link {
                width: 100%;
            }

            html.role-sidebar-collapsed .role-sidebar-navbar .navbar-nav.me-auto {
                align-items: center;
                padding-inline: .5rem;
            }

            html.role-sidebar-collapsed .role-sidebar-navbar .navbar-nav.me-auto .nav-link {
                justify-content: center;
                padding-inline: .4rem;
            }

            html.role-sidebar-collapsed .role-sidebar-navbar .sidebar-nav-label {
                display: none !important;
            }

            #app:has(> .role-sidebar-navbar) > main.app-main {
                margin-left: var(--role-sidebar-width);
                min-width: 0;
                padding-top: calc(3.75rem + 2rem) !important;
                transition: margin-left .22s ease-out, width .22s ease-out;
                width: calc(100% - var(--role-sidebar-width));
            }
        }

        @media (max-width: 991.98px) {
            .role-sidebar-navbar .sidebar-toggle-btn {
                display: none !important;
            }
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

        .notification-dropdown {
            min-width: min(340px, 94vw);
            max-width: min(94vw, 430px);
            overflow: hidden;
            transform-origin: top right;
        }

        .notification-dropdown.show {
            animation: notification-dropdown-in .2s ease-out both;
        }

        .notification-dropdown__list {
            max-height: 23rem;
            min-width: 0;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            width: 100%;
        }

        .notification-dropdown__item {
            border-bottom: 1px solid rgba(148, 163, 184, .16);
            min-width: 0;
            overflow: hidden;
            padding: .25rem .65rem;
            width: 100%;
        }

        .notification-dropdown__trigger {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: .85rem;
            color: inherit;
            display: flex;
            gap: .75rem;
            min-width: 0;
            overflow: hidden;
            padding: .7rem;
            text-align: left;
            transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
            width: 100%;
        }

        .notification-dropdown__trigger > .flex-grow-1 {
            flex: 1 1 0%;
            min-width: 0;
        }

        .notification-dropdown__trigger .text-truncate {
            max-width: 100%;
        }

        .notification-dropdown__trigger:hover,
        .notification-dropdown__trigger:focus-visible {
            background: rgba(59, 130, 246, .08);
            box-shadow: 0 6px 14px rgba(15, 23, 42, .07);
            outline: none;
            transform: translateX(2px);
        }

        .notification-dropdown__item.is-unread .notification-dropdown__trigger {
            background: rgba(59, 130, 246, .045);
        }

        .notification-dropdown__avatar,
        .notification-modal__icon {
            align-items: center;
            background: color-mix(in srgb, var(--bs-primary) 12%, white);
            border: 1px solid color-mix(in srgb, var(--bs-primary) 20%, white);
            border-radius: 50%;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9), 0 5px 12px rgba(15, 23, 42, .08);
            color: var(--bs-primary);
            display: inline-flex;
            flex: 0 0 auto;
            height: 2.65rem;
            justify-content: center;
            width: 2.65rem;
        }

        .notification-dropdown__dot {
            background: #2563eb;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
            flex: 0 0 auto;
            height: .6rem;
            width: .6rem;
        }

        .notification-dropdown__view-all {
            background: linear-gradient(180deg, #fff, #f8fafc);
            border-top: 1px solid rgba(148, 163, 184, .2);
            color: var(--bs-primary);
            display: block;
            font-size: .875rem;
            font-weight: 700;
            padding: .85rem 1rem;
            text-align: center;
            text-decoration: none;
            transition: background .18s ease, letter-spacing .18s ease;
        }

        .notification-dropdown__view-all:hover {
            background: rgba(59, 130, 246, .07);
            letter-spacing: .01em;
        }

        .notification-modal-content {
            animation: notification-modal-in .3s cubic-bezier(.2, .8, .2, 1) both;
            border-radius: 1.15rem;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24), 0 6px 18px rgba(15, 23, 42, .12);
        }

        @keyframes notification-dropdown-in {
            from { opacity: 0; transform: translateY(-6px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes notification-modal-in {
            from { opacity: 0; transform: translateY(12px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
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
        html[data-bs-theme="dark"] .notification-dropdown__view-all {
            background: linear-gradient(180deg, #1e293b, #172033);
            border-color: #334155;
        }
        html[data-bs-theme="dark"] .notification-dropdown__trigger:hover,
        html[data-bs-theme="dark"] .notification-dropdown__trigger:focus-visible,
        html[data-bs-theme="dark"] .notification-dropdown__item.is-unread .notification-dropdown__trigger {
            background: rgba(96, 165, 250, .12);
        }

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
    @if(in_array(auth()->user()->role ?? '', ['admin', 'super-admin'], true))
        <script>
            if (localStorage.getItem('lemu.role-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('role-sidebar-collapsed');
            }
        </script>
    @endif
</head>
<body @auth @if(auth()->user()->role === 'student') class="student-theme" @elseif(auth()->user()->role === 'super-admin') class="super-admin-theme" @endif @endauth>
    <div id="app">
        @unless(View::hasSection('hide-navbar') || View::hasSection('hide-nav'))
            <nav class="navbar navbar-expand-xxl navbar-light bg-white shadow-sm sticky-top {{ in_array(auth()->user()->role ?? '', ['admin', 'super-admin'], true) ? 'role-sidebar-navbar' : '' }}">
                <div class="container-fluid px-3 px-xxl-4">
                    @auth
                        @if(in_array(auth()->user()->role, ['admin', 'super-admin'], true))
                            <button
                                type="button"
                                class="sidebar-toggle-btn"
                                id="roleSidebarToggle"
                                aria-label="Collapse sidebar"
                                aria-controls="roleSidebarNavigation"
                                aria-expanded="true"
                                title="Collapse sidebar"
                            >
                                <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                                <span class="visually-hidden">Toggle sidebar labels</span>
                            </button>
                        @endif
                    @endauth
                    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/dashboard') }}">
                        <img src="{{ asset('storage/images/systemlogo.jpg') }}" alt="" width="36" height="36" class="rounded-circle bg-white object-fit-contain p-1">
                        <span>{{ config('app.name', 'LEMU') }}</span>
                    </a>
                    
                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Navigation Links -->
                        <ul class="navbar-nav me-auto gap-1" id="roleSidebarNavigation">
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
                                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}" aria-label="Dashboard" data-sidebar-label="Dashboard">
                                            <i class="bi bi-speedometer2 sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Dashboard</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.students') }}" class="nav-link {{ request()->routeIs('admin.students*') ? 'active fw-semibold' : '' }}" aria-label="Manage Students" data-sidebar-label="Manage Students">
                                            <i class="bi bi-people sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Manage Students</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.modules.index') }}" class="nav-link {{ request()->routeIs('admin.modules*') ? 'active fw-semibold' : '' }}" aria-label="Modules" data-sidebar-label="Modules">
                                            <i class="bi bi-journal-bookmark sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Modules</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.question-bank.index') }}" class="nav-link {{ request()->routeIs('admin.question-bank*') ? 'active fw-semibold' : '' }}" aria-label="Question Bank" data-sidebar-label="Question Bank">
                                            <i class="bi bi-collection sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Question Bank</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.tests.index') }}" class="nav-link {{ request()->routeIs('admin.tests*') ? 'active fw-semibold' : '' }}" aria-label="Examinations" data-sidebar-label="Examinations">
                                            <i class="bi bi-clipboard-check sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Examinations</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.item-analysis.index') }}" class="nav-link {{ request()->routeIs('admin.item-analysis*') ? 'active fw-semibold' : '' }}" aria-label="Analysis" data-sidebar-label="Analysis">
                                            <i class="bi bi-bar-chart-line sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Analysis</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('faculty.results.index') }}" class="nav-link {{ request()->routeIs('faculty.results*') ? 'active fw-semibold' : '' }}" aria-label="Results" data-sidebar-label="Results">
                                            <i class="bi bi-graph-up-arrow sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Results</span>
                                        </a>
                                    </li>
                                @elseif(auth()->user()->role === 'super-admin')
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.dashboard') }}" aria-label="Dashboard" data-sidebar-label="Dashboard">
                                            <i class="bi bi-speedometer2 sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Dashboard</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.departments*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.departments.index') }}" aria-label="Colleges" data-sidebar-label="Colleges">
                                            <i class="bi bi-building sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Colleges</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.users*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.users.index') }}" aria-label="Faculty" data-sidebar-label="Faculty">
                                            <i class="bi bi-person-badge sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Faculty</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.monitoring*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.monitoring') }}" aria-label="Monitoring" data-sidebar-label="Monitoring">
                                            <i class="bi bi-activity sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Monitoring</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.tests*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.tests.index') }}" aria-label="Examinations" data-sidebar-label="Examinations">
                                            <i class="bi bi-clipboard-check sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Examinations</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.item-analysis*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.item-analysis.index') }}" aria-label="Analysis" data-sidebar-label="Analysis">
                                            <i class="bi bi-bar-chart-line sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Analysis</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.question-bank*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.question-bank.index') }}" aria-label="Question Bank" data-sidebar-label="Question Bank">
                                            <i class="bi bi-collection sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Question Bank</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('super-admin.results*') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.results.index') }}" aria-label="Results" data-sidebar-label="Results">
                                            <i class="bi bi-graph-up-arrow sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Results</span>
                                        </a>
                                    </li>
                                @endif
                                @if(in_array(Auth::user()->role, ['admin', 'super-admin'], true))
                                    <li class="nav-item sidebar-account-links">
                                        @if(Auth::user()->role === 'admin')
                                            <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.profile') ? 'active fw-semibold' : '' }}" href="{{ route('admin.profile') }}" aria-label="Profile" data-sidebar-label="Profile">
                                                <i class="bi bi-person sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Profile</span>
                                            </a>
                                        @else
                                            <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('super-admin.profile') ? 'active fw-semibold' : '' }}" href="{{ route('super-admin.profile') }}" aria-label="Profile" data-sidebar-label="Profile">
                                                <i class="bi bi-person sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Profile</span>
                                            </a>
                                        @endif
                                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#themeSettingsModal" aria-label="Settings" data-sidebar-label="Settings">
                                            <i class="bi bi-sliders sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Settings</span>
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                        <a
                                            class="nav-link d-flex align-items-center gap-2 text-danger"
                                            href="{{ route('logout') }}"
                                            aria-label="Logout"
                                            data-sidebar-label="Logout"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                        >
                                            <i class="bi bi-box-arrow-right sidebar-nav-icon" aria-hidden="true"></i><span class="sidebar-nav-label">Logout</span>
                                        </a>
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
                                        <input type="hidden" name="course_id" value="{{ request('course_id') }}">
                                        @if(request()->routeIs('super-admin.dashboard'))
                                            <input type="hidden" name="period" value="{{ request('period', '30') }}">
                                            @if(request('period') === 'custom')
                                                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                                                <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                                            @endif
                                        @endif
                                    @endif
                                    <label class="visually-hidden" for="admin-nav-student-search">{{ request()->routeIs('faculty.results*', 'super-admin.results*') ? 'Search students' : 'Search students by name, email, or student ID' }}</label>
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
                                @endif

                                @if(in_array(Auth::user()->role, ['student', 'admin', 'super-admin'], true))
                                    @php
                                        $isStudentNotifications = Auth::user()->role === 'student';
                                        $notificationRoutePrefix = Auth::user()->role.'.';
                                        $navNotifications = $isStudentNotifications
                                            ? ($studentNavNotifications ?? collect())
                                            : ($adminNavNotifications ?? collect());
                                        $navUnreadCount = $isStudentNotifications
                                            ? ($unreadStudentNotificationCount ?? 0)
                                            : ($unreadAdminNotificationCount ?? 0);
                                    @endphp
                                    <li class="nav-item dropdown">
                                        <button class="nav-link btn btn-link position-relative border-0" id="notificationsDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                                            <i class="bi bi-bell-fill fs-5" aria-hidden="true"></i>
                                            <span class="d-md-none ms-2">Notifications</span>
                                            @if($navUnreadCount > 0)
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" data-notification-unread-badge>
                                                    {{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}
                                                    <span class="visually-hidden">unread notifications</span>
                                                </span>
                                            @endif
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0 notification-dropdown" aria-labelledby="notificationsDropdown">
                                            <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-3 bg-body-tertiary rounded-top">
                                                <div>
                                                    <strong class="text-body d-block">Notifications</strong>
                                                    <small class="text-secondary">Your latest updates</small>
                                                </div>
                                                @if($navUnreadCount > 0)
                                                    <span class="badge bg-primary rounded-pill" data-notification-unread-summary>{{ $navUnreadCount }} new</span>
                                                @endif
                                            </div>
                                            <div class="notification-dropdown__list">
                                                @forelse($navNotifications as $notification)
                                                    @php
                                                        $notificationIsRead = $notification->read_at !== null;
                                                        $notificationTitle = $isStudentNotifications
                                                            ? $notification->subject
                                                            : ($notification->data['title'] ?? 'Notification');
                                                        $notificationMessage = $isStudentNotifications
                                                            ? $notification->message
                                                            : ($notification->data['message'] ?? (
                                                                isset($notification->data['report_date'])
                                                                    ? $notification->data['report_date'].' · '.($notification->data['total_questions_added'] ?? 0).' question(s) added'
                                                                    : 'You have a new update.'
                                                            ));
                                                        $notificationSender = $isStudentNotifications
                                                            ? ($notification->admin?->name ?? config('app.name', 'LEMU'))
                                                            : config('app.name', 'LEMU');
                                                        $notificationReadRoute = $notificationRoutePrefix.'notifications.read';
                                                        $notificationDeleteRoute = $notificationRoutePrefix.'notifications.destroy';
                                                    @endphp
                                                    <div class="notification-dropdown__item {{ $notificationIsRead ? '' : 'is-unread' }}">
                                                        <button
                                                            type="button"
                                                            class="notification-dropdown__trigger notification-modal-trigger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#notificationModal"
                                                            data-notification-title="{{ $notificationTitle }}"
                                                            data-notification-message="{{ $notificationMessage }}"
                                                            data-notification-sender="{{ $notificationSender }}"
                                                            data-notification-date="{{ $notification->created_at?->format('M d, Y · h:i A') }}"
                                                            data-notification-read="{{ $notificationIsRead ? 'true' : 'false' }}"
                                                            data-notification-achievement="{{ $isStudentNotifications && str_starts_with($notificationTitle, 'Achievement unlocked:') ? 'true' : 'false' }}"
                                                            data-notification-related-url="{{ ! $isStudentNotifications ? ($notification->data['url'] ?? '') : '' }}"
                                                            data-notification-read-url="{{ route($notificationReadRoute, $notification->getKey()) }}"
                                                            data-notification-delete-url="{{ route($notificationDeleteRoute, $notification->getKey()) }}"
                                                        >
                                                            <span class="notification-dropdown__avatar" aria-hidden="true">
                                                                <i class="bi {{ $isStudentNotifications && str_starts_with($notificationTitle, 'Achievement unlocked:') ? 'bi-trophy-fill' : 'bi-bell-fill' }}"></i>
                                                            </span>
                                                            <span class="min-w-0 flex-grow-1">
                                                                <strong class="d-block text-truncate small">{{ $notificationTitle }}</strong>
                                                                <span class="d-block text-secondary small text-truncate mt-1">{{ \Illuminate\Support\Str::limit($notificationMessage, 90) }}</span>
                                                            </span>
                                                            @unless($notificationIsRead)
                                                                <span class="notification-dropdown__dot" aria-label="Unread"></span>
                                                            @endunless
                                                        </button>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-secondary px-3 py-4">
                                                        <i class="bi bi-bell-slash fs-4 d-block mb-1" aria-hidden="true"></i>
                                                        <p class="small mb-0">You have no notifications.</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                            <a
                                                class="notification-dropdown__view-all"
                                                href="{{ route($notificationRoutePrefix.'notifications.index') }}"
                                                data-notifications-view-all
                                            >
                                                View all notifications <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </li>
                                @endif

                                @if(in_array(Auth::user()->role, ['admin', 'super-admin'], true))
                                    <li class="nav-item">
                                        <span class="nav-link fw-semibold d-flex align-items-center gap-2" aria-label="Signed in as {{ Auth::user()->name }}">
                                            <i class="bi bi-person-circle fs-5" aria-hidden="true"></i>
                                            <span>{{ Auth::user()->name }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary border fs-6 px-2 py-1">{{ Auth::user()->role_label }}</span>
                                        </span>
                                    </li>
                                @else
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
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('student.profile') }}">
                                                <i class="bi bi-person" aria-hidden="true"></i>My Profile
                                            </a>
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
                                @endif
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
            ['type' => 'danger', 'title' => 'Something needs attention', 'message' => session('error') ?? session('invitation_error')],
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

        <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content notification-modal-content">
                    <div class="modal-header border-0 pb-2">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <span class="notification-modal__icon" aria-hidden="true"><i class="bi bi-bell-fill"></i></span>
                            <div class="min-w-0">
                                <h2 class="modal-title h5 fw-bold mb-1 text-truncate" id="notificationModalLabel"></h2>
                                <small class="text-secondary">From <span id="notificationModalSender" class="fw-semibold"></span></small>
                                <small class="d-block text-secondary" id="notificationModalDate"></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close notification"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p id="notificationModalMessage" class="mb-0 text-body text-break lh-lg" style="white-space: pre-wrap;"></p>
                        <p id="notificationModalReadStatus" class="small text-secondary mt-3 mb-0"></p>
                    </div>
                    <div class="modal-footer border-0 pt-1">
                        @if (Auth::user()->role === 'student')
                            <a id="notificationAchievementLink" href="{{ route('student.achievements') }}" class="btn btn-outline-primary rounded-pill d-none me-auto">
                                <i class="bi bi-award me-1" aria-hidden="true"></i>Go to achievements
                            </a>
                        @endif
                        <a id="notificationRelatedLink" href="#" class="btn btn-primary rounded-pill d-none me-auto">
                            <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Open related page
                        </a>
                        <form
                            id="notificationDeleteForm"
                            method="POST"
                            data-confirm-title="Delete this notification?"
                            data-confirm-text="This notification will be permanently removed from your inbox."
                            data-confirm-button="Delete notification"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                            </button>
                        </form>
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endauth

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-kpi-tooltip]').forEach((element) => {
            bootstrap.Tooltip.getOrCreateInstance(element, { container: 'body' });
        });

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

        // Notification actions are shared across student, faculty, and admin inboxes.
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

        async function markNotificationRead(readUrl, sourceElement) {
            const response = await fetch(readUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to mark notification as read.');
            }

            const payload = await response.json();
            if (sourceElement) {
                sourceElement.dataset.notificationRead = 'true';
                sourceElement.closest('.notification-dropdown__item')?.classList.remove('is-unread');
                sourceElement.closest('.notification-dropdown__item')?.querySelector('.notification-dropdown__dot')?.remove();
            }
            updateUnreadNotificationCount(payload.unread_count);
        }

        let activeNotificationTrigger = null;
        const notificationModal = document.getElementById('notificationModal');
        document.getElementById('notificationDeleteForm')?.addEventListener('submit', function () {
            if (this.dataset.confirmed === 'true') {
                activeNotificationTrigger = null;
            }
        });

        document.querySelectorAll('.notification-modal-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                activeNotificationTrigger = trigger;
                document.getElementById('notificationModalLabel').textContent = trigger.dataset.notificationTitle || 'Notification';
                document.getElementById('notificationModalMessage').textContent = trigger.dataset.notificationMessage || '';
                document.getElementById('notificationModalSender').textContent = trigger.dataset.notificationSender || '';
                document.getElementById('notificationModalDate').textContent = trigger.dataset.notificationDate || '';
                document.getElementById('notificationModalReadStatus').textContent = trigger.dataset.notificationRead === 'true'
                    ? 'This notification is already marked as read.'
                    : 'This notification will be marked as read when you close it.';
                document.getElementById('notificationDeleteForm').action = trigger.dataset.notificationDeleteUrl;

                const achievementLink = document.getElementById('notificationAchievementLink');
                achievementLink?.classList.toggle('d-none', trigger.dataset.notificationAchievement !== 'true');

                const relatedLink = document.getElementById('notificationRelatedLink');
                relatedLink.href = trigger.dataset.notificationRelatedUrl || '#';
                relatedLink.classList.toggle('d-none', !trigger.dataset.notificationRelatedUrl);
            });
        });

        [document.getElementById('notificationRelatedLink'), document.getElementById('notificationAchievementLink')]
            .filter(Boolean)
            .forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (!activeNotificationTrigger || activeNotificationTrigger.dataset.notificationRead === 'true') {
                        return;
                    }

                    event.preventDefault();
                    markNotificationRead(activeNotificationTrigger.dataset.notificationReadUrl, activeNotificationTrigger)
                        .then(function () {
                            window.location.assign(link.href);
                        })
                        .catch(function () {
                            window.showToast('This notification could not be marked as read. Please try again.', 'danger');
                        });
                });
            });

        notificationModal?.addEventListener('hidden.bs.modal', function () {
            const trigger = activeNotificationTrigger;
            activeNotificationTrigger = null;

            if (trigger && trigger.dataset.notificationRead !== 'true') {
                markNotificationRead(trigger.dataset.notificationReadUrl, trigger)
                    .catch(function () {
                        window.showToast('This notification could not be marked as read. Please try again.', 'danger');
                    });
            }
        });

        document.querySelectorAll('[data-notification-page-open]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                const detail = document.querySelector('[data-notification-detail]');
                if (!detail || detail.dataset.notificationRead === 'true') {
                    return;
                }
                if (new URL(link.href).searchParams.get('notification') === detail.dataset.notificationId) {
                    return;
                }

                event.preventDefault();
                markNotificationRead(detail.dataset.notificationReadUrl, null)
                    .then(function () {
                        window.location.assign(link.href);
                    })
                    .catch(function () {
                        window.showToast('This notification could not be marked as read. Please try again.', 'danger');
                    });
            });
        });

        document.querySelectorAll('[data-notification-page-leave]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                    const detail = document.querySelector('[data-notification-detail]');
                    if (!detail || detail.dataset.notificationRead === 'true') {
                        return;
                    }

                    event.preventDefault();
                    markNotificationRead(detail.dataset.notificationReadUrl, null)
                        .then(function () {
                            window.location.assign(link.href);
                        })
                        .catch(function () {
                            window.showToast('This notification could not be marked as read. Please try again.', 'danger');
                        });
            });
        });

        document.querySelectorAll('[data-notifications-view-all]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                const target = new URL(link.href, window.location.origin);
                const current = new URL(window.location.href);
                const currentReturnUrl = current.searchParams.get('return_to');
                const cameFromNotificationPage = /\/notifications\/?$/.test(current.pathname);
                const returnUrl = cameFromNotificationPage && currentReturnUrl
                    ? currentReturnUrl
                    : current.pathname + current.search;
                target.searchParams.set('return_to', returnUrl);
                window.location.assign(target.href);
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
    @auth
        @if(in_array(Auth::user()->role, ['admin', 'super-admin'], true))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const sidebarToggle = document.getElementById('roleSidebarToggle');
                    const sidebarNavigation = document.getElementById('roleSidebarNavigation');
                    const sidebarToggleIcon = sidebarToggle?.querySelector('i');
                    if (!sidebarToggle || !sidebarNavigation || !sidebarToggleIcon) {
                        return;
                    }

                    function setSidebarCollapsed(isCollapsed, persist = true) {
                        document.documentElement.classList.toggle('role-sidebar-collapsed', isCollapsed);
                        sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
                        sidebarToggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
                        sidebarToggle.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
                        sidebarToggleIcon.style.transform = isCollapsed ? 'scaleX(-1)' : '';

                        sidebarNavigation.querySelectorAll('[data-sidebar-label]').forEach(function (link) {
                            if (isCollapsed) {
                                link.title = link.dataset.sidebarLabel;
                            } else {
                                link.removeAttribute('title');
                            }
                        });

                        if (persist) {
                            localStorage.setItem('lemu.role-sidebar-collapsed', String(isCollapsed));
                        }
                    }

                    setSidebarCollapsed(document.documentElement.classList.contains('role-sidebar-collapsed'), false);
                    sidebarToggle.addEventListener('click', function () {
                        setSidebarCollapsed(!document.documentElement.classList.contains('role-sidebar-collapsed'));
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