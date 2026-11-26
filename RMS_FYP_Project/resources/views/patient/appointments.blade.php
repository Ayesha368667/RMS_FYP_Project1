@extends('layouts.patient')

@section('title', __('appointments.title'))

@section('content')
    <h1 class="h4 mb-3">{{ __('appointments.title') }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Request form --}}
    @if (! $hasDoctor)
        <div class="alert alert-warning">{{ __('appointments.no_doctor') }}</div>
    @else
        <form method="POST" action="{{ route('patient.appointments.store') }}" class="card shadow-sm mb-4">
            @csrf
            <div class="card-header bg-white fw-semibold">{{ __('appointments.request_title') }}</div>

            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        {{ __('appointments.type') }} <span class="text-danger">*</span>
                    </label>
                    <select name="appointment_type" class="form-select" required>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(old('appointment_type') === $type)>
                                {{ __('appointments.type_' . $type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('appointments.reason') }}</label>
                    <textarea name="reason" class="form-control" rows="3" maxlength="1000">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-primary">{{ __('appointments.submit') }}</button>
            </div>
        </form>
    @endif

    {{-- Appointments list --}}
    <h2 class="h5 mb-3">{{ __('appointments.my_appointments') }}</h2>

    @if ($appointments->isEmpty())
        <div class="alert alert-info">{{ __('appointments.none') }}</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white">
                <thead>
                    <tr>
                        <th>{{ __('appointments.doctor') }}</th>
                        <th>{{ __('appointments.type') }}</th>
                        <th>{{ __('appointments.requested_on') }}</th>
                        <th>{{ __('appointments.date') }}</th>
                        <th>{{ __('appointments.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments as $a)
                        @php
                            $badge = match ($a->status) {
                                'approved', 'completed' => 'success',
                                'rejected', 'cancelled', 'missed' => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <tr>
                            <td>{{ $a->doctor?->user?->name ?? '-' }}</td>
                            <td>{{ __('appointments.type_' . $a->appointment_type) }}</td>
                            <td>{{ $a->requested_at?->format('d M Y') }}</td>
                            <td>{{ $a->appointment_date?->format('d M Y, h:i A') ?? __('appointments.after_approval') }}</td>
                            <td>
                                <span class="badge text-bg-{{ $badge }}">{{ __('appointments.status_' . $a->status) }}</span>
                                @if ($a->status === 'rejected' && $a->rejection_reason)
                                    <div class="small text-muted mt-1">{{ __('appointments.rejection_reason') }}: {{ $a->rejection_reason }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $appointments->links() }}
    @endif
@endsection