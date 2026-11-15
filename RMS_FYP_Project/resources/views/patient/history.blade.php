@extends('layouts.patient')

@section('title', __('followup.history.title'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ __('followup.history.title') }}</h1>
        <a href="{{ route('patient.followups') }}" class="btn btn-outline-primary btn-sm">{{ __('followup.history.fill_today') }}</a>
    </div>

    @if ($responses->isEmpty())
        <div class="alert alert-info">{{ __('followup.history.empty') }}</div>
    @else
        <div class="accordion shadow-sm" id="historyList">
            @foreach ($responses as $r)
                @php
                    $statusKey  = 'followup.history.status.'.$r->status;
                    $statusText = \Illuminate\Support\Facades\Lang::has($statusKey) ? __($statusKey) : $r->status;
                @endphp

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#response-{{ $r->id }}"
                                aria-expanded="false" aria-controls="response-{{ $r->id }}">
                            <span class="fw-semibold me-3">{{ $r->due_date->translatedFormat('d F Y') }}</span>
                            <span class="badge text-bg-{{ $r->status === 'reviewed' ? 'success' : 'secondary' }}">{{ $statusText }}</span>
                        </button>
                    </h2>
                    <div id="response-{{ $r->id }}" class="accordion-collapse collapse" data-bs-parent="#historyList">
                        <div class="accordion-body">
                            <p class="text-muted small mb-3">
                                {{ __('followup.history.submitted_at') }}: {{ $r->submitted_at->format('d/m/Y h:i A') }}
                            </p>
                            <ul class="list-group list-group-flush">
                                @foreach ($r->answer_rows as $row)
                                    <li class="list-group-item px-0">
                                        <div class="fw-semibold">{{ $row['label'] }}</div>
                                        <div>{{ $row['value'] }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $responses->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection