@extends('layouts.patient')

@section('title', __('portal.followup.title'))

@section('content')
    <h1 class="h4 mb-2">{{ __('portal.followup.title') }}</h1>

    @if (! $form)
        <div class="alert alert-info">{{ __('portal.followup.no_active_form') }}</div>
    @else
        <p class="text-muted">{{ __('portal.followup.intro') }}</p>

        @php $canSubmit = Route::has('patient.followups.store'); @endphp

        <form method="POST"
              @if ($canSubmit) action="{{ route('patient.followups.store') }}" @else onsubmit="return false" @endif
              class="card shadow-sm">
            @csrf
            <input type="hidden" name="followup_form_id" value="{{ $form->id }}">

            <div class="card-body">
                @foreach ($questions as $q)
                    @php
                        $name = 'answers['.$q['key'].']';
                        $required = (bool) ($q['required'] ?? false);
                    @endphp

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ $q['label'] }}
                            @if ($required) <span class="text-danger">*</span> @endif
                        </label>

                        @if ($q['type'] === 'number')
                            <input type="number" name="{{ $name }}" class="form-control"
                                   min="{{ $q['min'] ?? 0 }}" max="{{ $q['max'] ?? 10 }}" @required($required)>

                        @elseif ($q['type'] === 'boolean')
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $name }}"
                                           id="{{ $q['key'] }}_yes" value="1" @required($required)>
                                    <label class="form-check-label" for="{{ $q['key'] }}_yes">{{ __('portal.followup.yes') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $name }}"
                                           id="{{ $q['key'] }}_no" value="0" @required($required)>
                                    <label class="form-check-label" for="{{ $q['key'] }}_no">{{ __('portal.followup.no') }}</label>
                                </div>
                            </div>

                        @elseif ($q['type'] === 'textarea')
                            <textarea name="{{ $name }}" class="form-control" rows="3" @required($required)></textarea>

                        @else
                            <input type="text" name="{{ $name }}" class="form-control" @required($required)>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="card-footer bg-white">
                @if ($canSubmit)
                    <button type="submit" class="btn btn-primary">{{ __('portal.followup.submit') }}</button>
                @else
                    <button type="button" class="btn btn-primary" disabled>{{ __('portal.followup.coming_soon') }}</button>
                @endif
            </div>
        </form>
    @endif
@endsection