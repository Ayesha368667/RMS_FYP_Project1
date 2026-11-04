@php
    $isUrdu = app()->getLocale() === 'ur';
    $link = fn ($name) => Route::has($name) ? route($name) : '#';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isUrdu ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('portal.dashboard.title')) | {{ __('portal.app_name') }}</title>

    @if ($isUrdu)
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;600&display=swap" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/patient.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 bg-light {{ $isUrdu ? 'lang-ur' : 'lang-en' }}">

    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $link('patient.dashboard') }}">
                <span class="fw-semibold text-primary">{{ __('portal.app_name') }}</span>
            </a>

            <button class="navbar-toggler border-0" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"
                    aria-controls="mobileMenu" aria-label="{{ __('portal.nav.menu') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title text-primary" id="mobileMenuLabel">{{ __('portal.nav.menu') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/dashboard') ? 'active' : '' }}" href="{{ $link('patient.dashboard') }}">
                                <i class="bi bi-house me-1"></i> {{ __('portal.nav.home') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/followups*') ? 'active' : '' }}" href="{{ $link('patient.followups') }}">
                                <i class="bi bi-clipboard2-pulse me-1"></i> {{ __('portal.nav.followups') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/chat*') ? 'active' : '' }}" href="{{ $link('patient.chat') }}">
                                <i class="bi bi-chat-dots me-1"></i> {{ __('portal.nav.chat') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/profile*') ? 'active' : '' }}" href="{{ $link('patient.profile') }}">
                                <i class="bi bi-person me-1"></i> {{ __('portal.nav.profile') }}
                            </a>
                        </li>

                        {{-- Language switcher --}}
                        <li class="nav-item ms-lg-2 mt-3 mt-lg-0">
                            <div class="btn-group btn-group-sm w-100" role="group" aria-label="{{ __('portal.language') }}">
                                <a href="{{ route('lang.switch', 'en') }}"
                                   class="btn {{ $isUrdu ? 'btn-outline-primary' : 'btn-primary' }}">{{ __('portal.english') }}</a>
                                <a href="{{ route('lang.switch', 'ur') }}"
                                   class="btn {{ $isUrdu ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('portal.urdu') }}</a>
                            </div>
                        </li>

                        <li class="nav-item ms-lg-2 mt-3 mt-lg-0">
                            {{-- Logout route apne project ke mutabiq badal lein --}}
                            <form method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-box-arrow-right me-1"></i> {{ __('portal.nav.logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            &copy; {{ date('Y') }} {{ __('portal.app_name') }}. {{ __('portal.footer') }}
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>