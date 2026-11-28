@extends('layouts.patient')

@section('title', __('profile.title'))

@section('content')
    <h1 class="h4 mb-4">{{ __('profile.title') }}</h1>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('profile.my_details') }}</h2>
                    <dl class="row mb-2">
                        <dt class="col-5">{{ __('profile.name') }}</dt>
                        <dd class="col-7">{{ $user->name }}</dd>
                        <dt class="col-5">{{ __('profile.email') }}</dt>
                        <dd class="col-7" dir="ltr">{{ $user->email }}</dd>
                        <dt class="col-5">{{ __('profile.patient_number') }}</dt>
                        <dd class="col-7">{{ $patient?->patient_number ?? '-' }}</dd>
                        <dt class="col-5">{{ __('profile.father_name') }}</dt>
                        <dd class="col-7">{{ $patient?->father_name ?? '-' }}</dd>
                        <dt class="col-5">{{ __('profile.dob') }}</dt>
                        <dd class="col-7">{{ $patient?->date_of_birth?->format('d/m/Y') ?? '-' }}</dd>
                        <dt class="col-5">{{ __('profile.gender') }}</dt>
                        <dd class="col-7">{{ $patient?->gender ? ucfirst($patient->gender) : '-' }}</dd>
                        <dt class="col-5">{{ __('profile.city') }}</dt>
                        <dd class="col-7">{{ $patient?->city ?? '-' }}</dd>
                        <dt class="col-5">{{ __('profile.address') }}</dt>
                        <dd class="col-7">{{ $patient?->address ?? '-' }}</dd>
                    </dl>
                    <p class="text-muted small mb-0">{{ __('profile.details_note') }}</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('profile.settings') }}</h2>

                    <form method="POST" action="{{ route('patient.profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="phone" class="form-label">{{ __('profile.phone') }}</label>
                            <input type="text" id="phone" name="phone" dir="ltr" placeholder="03XXXXXXXXX"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('profile.phone_help') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="preffered_language" class="form-label">{{ __('profile.language') }}</label>
                            <select id="preffered_language" name="preffered_language"
                                    class="form-select @error('preffered_language') is-invalid @enderror">
                                <option value="en" @selected(old('preffered_language', $user->preffered_language) === 'en')>{{ __('portal.english') }}</option>
                                <option value="ur" @selected(old('preffered_language', $user->preffered_language) === 'ur')>{{ __('portal.urdu') }}</option>
                            </select>
                            @error('preffered_language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">{{ __('profile.save') }}</button>
                    </form>

                    <hr>
                    <a href="{{ route('password.change') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-key me-1"></i> {{ __('profile.change_password') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection