@extends('layouts.patient')

@section('title', __('notifications.title'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ __('notifications.title') }}</h1>

        @if (auth()->user()->unreadNotifications()->count() > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-outline-primary btn-sm">{{ __('notifications.mark_all') }}</button>
            </form>
        @endif
    </div>

    @forelse ($notifications as $n)
        <div class="card shadow-sm mb-2 {{ $n->read_at ? '' : 'border-primary' }}">
            <div class="card-body d-flex justify-content-between align-items-start gap-3">
                <div>
                    <div class="fw-semibold">{{ $n->data['title'] ?? __('notifications.default_title') }}</div>
                    @if (! empty($n->data['body']))
                        <div class="text-muted small">{{ $n->data['body'] }}</div>
                    @endif
                    <div class="text-muted small mt-1">{{ $n->created_at->format('d/m/Y h:i A') }}</div>
                </div>

                @if (! $n->read_at)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                        @csrf
                        <button class="btn btn-sm btn-primary">{{ __('notifications.mark_read') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-muted">{{ __('notifications.empty') }}</p>
    @endforelse

    {{ $notifications->links('pagination::bootstrap-5') }}
@endsection