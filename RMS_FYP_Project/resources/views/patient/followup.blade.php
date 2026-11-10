@extends('layouts.patient')

@section('title', __('followup.title'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ __('followup.title') }}</h1>
        @if (Route::has('patient.followups.history'))
            <a href="{{ route('patient.followups.history') }}" class="btn btn-outline-primary btn-sm">{{ __('followup.view_history') }}</a>
        @endif
    </div>

    @if (! $admission)
        <div class="alert alert-warning">{{ __('followup.no_admission') }}</div>
    @elseif (! $form)
        <div class="alert alert-info">{{ __('followup.no_active_form') }}</div>
    @elseif ($already)
        <div class="alert alert-success">{{ __('followup.already') }}</div>
    @else
        <p class="text-muted">{{ __('followup.intro') }}</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('patient.followups.store') }}" class="card shadow-sm">
            @csrf
            <input type="hidden" name="followup_form_id" value="{{ $form->id }}">

            <div class="card-body">
                @foreach ($questions as $q)
                    @php
                        $name     = 'answers['.$q['key'].']';
                        $old      = old('answers.'.$q['key']);
                        $required = (bool) ($q['required'] ?? false);
                    @endphp

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ $q['label'] }}
                            @if ($required) <span class="text-danger">*</span> @endif
                        </label>

                        @if ($q['type'] === 'number')
                            <input type="number" name="{{ $name }}" value="{{ $old }}" class="form-control"
                                   min="{{ $q['min'] ?? 0 }}" max="{{ $q['max'] ?? 10 }}" @required($required)>

                        @elseif ($q['type'] === 'boolean')
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $name }}"
                                           id="{{ $q['key'] }}_yes" value="1" @checked($old === '1') @required($required)>
                                    <label class="form-check-label" for="{{ $q['key'] }}_yes">{{ __('followup.yes') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $name }}"
                                           id="{{ $q['key'] }}_no" value="0" @checked($old === '0') @required($required)>
                                    <label class="form-check-label" for="{{ $q['key'] }}_no">{{ __('followup.no') }}</label>
                                </div>
                            </div>

                        @elseif ($q['type'] === 'textarea')
                            <textarea name="{{ $name }}" class="form-control" rows="3" @required($required)>{{ $old }}</textarea>

                        @else
                            <input type="text" name="{{ $name }}" value="{{ $old }}" class="form-control" @required($required)>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-primary">{{ __('followup.submit') }}</button>
            </div>
        </form>
    @endif
@endsection