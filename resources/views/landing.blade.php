@extends('layouts.app')
@section('hide-navbar', true)
@section('title', 'LEMU | Student Learning Platform')

@push('styles')
<style>
    :root { --lemu-maroon:#800020; --lemu-maroon-dark:#520014; --lemu-gold:#d6a84b; }
    body { background:#5d0019; }
    .landing-page { min-height:100vh; overflow:hidden; background:linear-gradient(145deg,#4b0013 0%,#800020 55%,#a12640 100%); color:white; }
    .landing-page::before { content:""; position:absolute; inset:0; pointer-events:none; opacity:.14; background-image:radial-gradient(circle at 1px 1px,rgba(255,255,255,.45) 1px,transparent 0); background-size:28px 28px; }
    .public-nav { position:relative; z-index:10; border-bottom:1px solid rgba(255,255,255,.12); }
    .brand-mark-wrap { width:72px;height:72px;display:grid;place-items:center;flex:0 0 72px;border:1px solid rgba(255,255,255,.55);border-radius:22px;background:linear-gradient(145deg,rgba(255,255,255,.28),rgba(255,255,255,.08));box-shadow:0 0 0 7px rgba(255,255,255,.07),0 16px 38px rgba(0,0,0,.24); }
    .brand-mark { width:60px;height:60px;background:white;border-radius:17px;padding:4px;object-fit:contain;box-shadow:0 5px 16px rgba(0,0,0,.16); }
    .public-brand-name { font-size:1.2rem;letter-spacing:.04em; }
    .nav-link-public { color:rgba(255,255,255,.78);text-decoration:none;font-weight:600; }
    .nav-link-public:hover { color:white; }
    .hero-section { position:relative;z-index:2;padding:clamp(4rem,9vw,8rem) 0 5rem; }
    .eyebrow { color:#f5d89a;text-transform:uppercase;letter-spacing:.18em;font-size:.78rem;font-weight:800; }
    .hero-title { max-width:760px;font-size:clamp(2.7rem,7vw,5.3rem);line-height:.98;letter-spacing:-.045em;font-weight:800; }
    .hero-copy { max-width:650px;color:rgba(255,255,255,.78);font-size:clamp(1rem,2vw,1.2rem);line-height:1.75; }
    .primary-action { background:white;color:var(--lemu-maroon-dark);padding:.95rem 1.45rem;border-radius:12px;text-decoration:none;font-weight:800;box-shadow:0 18px 45px rgba(35,0,8,.28);transition:.2s ease; }
    .primary-action:hover { transform:translateY(-2px);color:var(--lemu-maroon-dark);box-shadow:0 22px 55px rgba(35,0,8,.35); }
    .secondary-action { color:white;text-decoration:none;font-weight:750;padding:.95rem .4rem; }
    .secondary-action:hover { color:#f5d89a; }
    .dashboard-preview { transform:perspective(1400px) rotateY(-7deg) rotateX(3deg);transform-origin:left center; }
    .preview-window { background:#fff;color:#1f2937;border-radius:24px;padding:18px;box-shadow:0 35px 90px rgba(31,0,8,.42); }
    .preview-toolbar { height:12px;display:flex;gap:7px;margin-bottom:17px; }
    .preview-toolbar span { width:8px;height:8px;border-radius:50%;background:#d1d5db; }
    .preview-panel { background:#f8f5f5;border:1px solid #eadede;border-radius:16px;padding:18px; }
    .score-ring { width:112px;height:112px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(var(--lemu-maroon) 78%,#eadfe2 0);position:relative;margin:auto; }
    .score-ring::after { content:"78%";position:absolute;width:82px;height:82px;border-radius:50%;background:white;display:grid;place-items:center;font-weight:850;color:var(--lemu-maroon); }
    .feature-section { position:relative;z-index:2;background:#f8f5f5;color:#202124;padding:5rem 0; }
    .feature-card { height:100%;background:white;border:1px solid #eadede;border-radius:18px;padding:1.7rem;box-shadow:0 12px 35px rgba(82,0,20,.05);transition:.2s ease; }
    .feature-card:hover { transform:translateY(-5px);box-shadow:0 20px 45px rgba(82,0,20,.1); }
    .feature-icon { width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#fff0f3;color:var(--lemu-maroon);font-size:1.25rem; }
    .site-footer { position:relative;z-index:2;background:#3e000e;color:rgba(255,255,255,.72); }
    @media(max-width:991px) { .dashboard-preview { transform:none;margin-top:2rem; } }
    @media(max-width:575px) { .hero-section { text-align:center; } .hero-actions { justify-content:center; } }
</style>
@endpush

@section('content')
<div class="landing-page">
    <nav class="public-nav py-3" aria-label="Primary navigation">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a href="{{ url('/') }}" class="d-flex align-items-center gap-3 text-white text-decoration-none">
                <span class="brand-mark-wrap"><img src="{{ asset('storage/images/systemlogo.jpg') }}" class="brand-mark" alt="LEMU system logo"></span>
                <div><strong class="public-brand-name d-block">LEMU</strong><small class="opacity-75">Student Learning Platform</small></div>
            </a>
            <div class="d-none d-sm-flex align-items-center gap-4">
                @auth
                    <a href="{{ route('student.dashboard') }}" class="nav-link-public">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link-public">Sign in</a>
                    <a href="{{ route('register') }}" class="primary-action py-2">Create account</a>
                @endauth
            </div>
        </div>
    </nav>

    <main>
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="eyebrow mb-4">Board exam preparation</div>
                        <h1 class="hero-title mb-4">Study with purpose. Improve with evidence.</h1>
                        <p class="hero-copy mb-4">LEMU helps students organize reviewers, practice course-aligned quizzes, understand weak topics, and monitor readiness for board examinations.</p>
                    </div>
                    <div class="col-lg-6 dashboard-preview" aria-label="Student dashboard preview">
                        <div class="preview-window">
                            <div class="preview-toolbar"><span></span><span></span><span></span></div>
                            <div class="row g-3">
                                <div class="col-sm-4"><div class="preview-panel h-100 text-center"><div class="score-ring mb-3"></div><strong>Board readiness</strong></div></div>
                                <div class="col-sm-8"><div class="preview-panel h-100"><small class="text-uppercase fw-bold text-secondary">Today’s study plan</small><div class="mt-3 p-3 bg-white rounded-3 border"><strong>Database Systems</strong><div class="progress mt-2"><div class="progress-bar" style="width:78%;background:#800020"></div></div></div><div class="mt-2 p-3 bg-white rounded-3 border"><strong>General practice test</strong><div class="small text-secondary mt-1">Review weak topics first</div></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="feature-section">
            <div class="container">
                <div class="text-center mx-auto mb-5" style="max-width:680px"><div class="eyebrow mb-2 text-danger">Student tools</div><h2 class="fw-bold mb-3">Everything needed to prepare smarter</h2><p class="text-secondary">LEMU turns study materials, quiz performance, and practice history into a clear learning path.</p></div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3"><article class="feature-card"><div class="feature-icon mb-3"><i class="bi bi-journal-bookmark-fill"></i></div><h3 class="h5 fw-bold">Learn from reviewers</h3><p class="text-secondary small mb-0">Study uploaded modules, subtopics, documents, and instructor resources in one place.</p></article></div>
                    <div class="col-md-6 col-lg-3"><article class="feature-card"><div class="feature-icon mb-3"><i class="bi bi-ui-checks-grid"></i></div><h3 class="h5 fw-bold">Practice with quizzes</h3><p class="text-secondary small mb-0">Answer course-aligned quizzes and general tests drawn from your reviewer material.</p></article></div>
                    <div class="col-md-6 col-lg-3"><article class="feature-card"><div class="feature-icon mb-3"><i class="bi bi-bullseye"></i></div><h3 class="h5 fw-bold">Find weak topics</h3><p class="text-secondary small mb-0">Use test results to locate concepts that need more review before your board exam.</p></article></div>
                    <div class="col-md-6 col-lg-3"><article class="feature-card"><div class="feature-icon mb-3"><i class="bi bi-bar-chart-fill"></i></div><h3 class="h5 fw-bold">Track readiness</h3><p class="text-secondary small mb-0">Follow mastery, recent scores, study activity, and progress from your dashboard.</p></article></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer py-4">
        <div class="container">
            <div class="row g-3 align-items-center">
                <div class="col-md-6"><strong class="d-block text-white">LEMU Student Learning Platform</strong><small>Learning, evaluation, and board-exam readiness support.</small></div>
                <div class="col-md-6 text-md-end small"><a href="mailto:support@lemu.edu" class="text-white text-decoration-none me-3"><i class="bi bi-envelope me-1"></i> support@lemu.edu</a><span>Department Office & Academic Support</span></div>
            </div>
            <div class="border-top border-white border-opacity-10 mt-3 pt-3 small">&copy; {{ date('Y') }} LEMU. All rights reserved.</div>
        </div>
    </footer>
</div>
@endsection
