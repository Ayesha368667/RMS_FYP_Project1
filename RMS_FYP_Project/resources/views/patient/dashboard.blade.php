@extends('layouts.patient')

@section('title', __('portal.dashboard.title'))

@section('content')
    <h1 class="h4 mb-4">{{ __('portal.dashboard.welcome') }}</h1>

    <div class="row g-3">

        {{-- Aaj ka follow-up --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1">{{ __('portal.dashboard.todays_followup') }}</h2>
                    <p class="text-muted">{{ __('portal.dashboard.followup_desc') }}</p>
                    <a href="#" class="btn btn-primary btn-sm">{{ __('portal.dashboard.open_form') }}</a>
                </div>
            </div>
        </div>

        {{-- Progress --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('portal.dashboard.progress') }}</h2>
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fs-4 fw-semibold">-</div>
                                <div class="small text-muted">{{ __('portal.dashboard.mood') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fs-4 fw-semibold">-</div>
                                <div class="small text-muted">{{ __('portal.dashboard.pain') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fs-4 fw-semibold">-</div>
                                <div class="small text-muted">{{ __('portal.dashboard.craving') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reminders --}}
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3"><i class="bi bi-bell me-1"></i> {{ __('portal.dashboard.reminders') }}</h2>
                    <p class="text-muted mb-0">{{ __('portal.dashboard.no_reminders') }}</p>
                </div>
            </div>
        </div>

        {{-- Family contacts --}}
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1"><i class="bi bi-people me-1"></i> {{ __('portal.dashboard.family') }}</h2>
                    <p class="text-muted small mb-2">{{ __('portal.dashboard.family_desc') }}</p>
                    <span class="badge text-bg-secondary">{{ __('portal.dashboard.coming_soon') }}</span>
                </div>
            </div>
        </div>

        {{-- Chat --}}
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1"><i class="bi bi-chat-dots me-1"></i> {{ __('portal.dashboard.chat') }}</h2>
                    <p class="text-muted small mb-2">{{ __('portal.dashboard.chat_desc') }}</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">{{ __('portal.dashboard.open_chat') }}</a>
                </div>
            </div>
        </div>

    </div>
@endsection