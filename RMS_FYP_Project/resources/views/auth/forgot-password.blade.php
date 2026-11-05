@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('content')
    <h4 class="text-center mb-3">Forgot Password</h4>
    <p class="text-center text-muted">Enter your email. We will send you a reset link.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
        </div>
        <button class="btn btn-teal btn-block">Send reset link</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}">Back to login</a>
    </div>
@endsection