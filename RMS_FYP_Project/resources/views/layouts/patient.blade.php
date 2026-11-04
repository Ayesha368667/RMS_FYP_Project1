<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Patient Portal') | {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/patient.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/patient/dashboard') }}">
                <img src="{{ asset('images/logo.svg') }}" alt="RMS Logo" height="38">
                <span class="fw-semibold text-primary">Rehab Portal</span>
            </a>

            {{-- Mobile menu button --}}
            <button class="navbar-toggler border-0" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"
                    aria-controls="mobileMenu" aria-label="Menu kholein">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Desktop pe normal menu, mobile pe side se nikalne wala menu --}}
            <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title text-primary" id="mobileMenuLabel">Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Band karein"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/dashboard') ? 'active' : '' }}"
                               href="{{ url('/patient/dashboard') }}">
                                <i class="bi bi-house me-1"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/followups*') ? 'active' : '' }}"
                               href="{{ url('/patient/followups') }}">
                                <i class="bi bi-clipboard2-pulse me-1"></i> Follow-ups
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/chat*') ? 'active' : '' }}"
                               href="{{ url('/patient/chat') }}">
                                <i class="bi bi-chat-dots me-1"></i> Chat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('patient/profile*') ? 'active' : '' }}"
                               href="{{ url('/patient/profile') }}">
                                <i class="bi bi-person me-1"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2 mt-3 mt-lg-0">
                            {{-- Logout route apne project ke mutabiq badal lein --}}
                            <form method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    {{-- Page content --}}
    <main class="container py-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>