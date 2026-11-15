@extends('layouts.patient')

@section('title', __('care.medicines.title'))

@section('content')
    <h1 class="h4 mb-3">{{ __('care.medicines.title') }}</h1>

    <div class="row g-3">
        @forelse ($medicines as $m)
            @php $statusKey = 'care.status.'.$m->status; @endphp
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h2 class="h6 mb-0">{{ $m->name }}</h2>
                                @if ($m->generic_name)
                                    <div class="small text-muted">{{ __('care.medicines.generic') }}: {{ $m->generic_name }}</div>
                                @endif
                            </div>
                            <span class="badge text-bg-{{ $m->status === 'active' ? 'success' : 'secondary' }}">
                                {{ \Illuminate\Support\Facades\Lang::has($statusKey) ? __($statusKey) : $m->status }}
                            </span>
                        </div>

                        <div class="small"><strong>{{ __('care.medicines.dosage') }}:</strong> {{ $m->dosage }}</div>
                        <div class="small"><strong>{{ __('care.medicines.frequency') }}:</strong> {{ $m->frequency }}</div>
                        @if ($m->instructions)
                            <div class="small"><strong>{{ __('care.medicines.instructions') }}:</strong> {{ $m->instructions }}</div>
                        @endif
                        <div class="small text-muted mt-2">
                            {{ __('care.medicines.from') }} {{ \Carbon\Carbon::parse($m->start_date)->format('d/m/Y') }}
                            @if ($m->end_date)
                                {{ __('care.medicines.until') }} {{ \Carbon\Carbon::parse($m->end_date)->format('d/m/Y') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">{{ __('care.medicines.empty') }}</div></div>
        @endforelse
    </div>
@endsection