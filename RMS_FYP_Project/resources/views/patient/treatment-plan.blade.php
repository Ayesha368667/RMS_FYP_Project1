@extends('layouts.patient')

@section('title', __('care.plan.title'))

@section('content')
    <h1 class="h4 mb-3">{{ __('care.plan.title') }}</h1>

    @forelse ($plans as $plan)
        @php
            $statusKey = 'care.status.'.$plan->status;
            $planSessions = $sessions->get($plan->id, collect());
        @endphp

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ $plan->plan_title }}</span>
                <span class="badge text-bg-{{ $plan->status === 'active' ? 'success' : 'secondary' }}">
                    {{ \Illuminate\Support\Facades\Lang::has($statusKey) ? __($statusKey) : $plan->status }}
                </span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    {{ __('care.plan.doctor') }}: {{ $plan->doctor_name }}
                    &middot; {{ __('care.plan.start') }}: {{ \Carbon\Carbon::parse($plan->start_date)->format('d/m/Y') }}
                    @if ($plan->target_end_date)
                        &middot; {{ __('care.plan.target_end') }}: {{ \Carbon\Carbon::parse($plan->target_end_date)->format('d/m/Y') }}
                    @endif
                </p>

                <h2 class="h6">{{ __('care.plan.goals') }}</h2>
                <p style="white-space: pre-line">{{ $plan->goals }}</p>

                <h2 class="h6">{{ __('care.plan.interventions') }}</h2>
                <p style="white-space: pre-line">{{ $plan->planned_interventions }}</p>

                @if ($planSessions->isNotEmpty())
                    <h2 class="h6 mt-4">{{ __('care.plan.sessions') }}</h2>
                    <ul class="list-group list-group-flush">
                        @foreach ($planSessions as $s)
                            @php $sKey = 'care.status.'.$s->session_status; @endphp
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ \Carbon\Carbon::parse($s->session_date)->format('d/m/Y h:i A') }}</span>
                                    <span class="badge text-bg-light border">
                                        {{ \Illuminate\Support\Facades\Lang::has($sKey) ? __($sKey) : $s->session_status }}
                                    </span>
                                </div>
                                @if ($s->progress_summary)
                                    <div class="small mt-1"><strong>{{ __('care.plan.progress') }}:</strong> {{ $s->progress_summary }}</div>
                                @endif
                                @if ($s->next_steps)
                                    <div class="small"><strong>{{ __('care.plan.next_steps') }}:</strong> {{ $s->next_steps }}</div>
                                @endif
                                @if ($s->next_session_date)
                                    <div class="small text-muted">{{ __('care.plan.next_session') }}: {{ \Carbon\Carbon::parse($s->next_session_date)->format('d/m/Y h:i A') }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">{{ __('care.plan.empty') }}</div>
    @endforelse
@endsection