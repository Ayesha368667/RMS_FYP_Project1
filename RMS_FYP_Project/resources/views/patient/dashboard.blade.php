@extends('layouts.patient')

@section('title', 'Home')

@section('content')
    <h1 class="h4 mb-3">Welcome</h1>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Aaj ka follow-up</h2>
                    <p class="text-muted mb-3">Apna check-in form bharein.</p>
                    <a href="#" class="btn btn-primary btn-sm">Form kholein</a>
                </div>
            </div>
        </div>
    </div>
@endsection