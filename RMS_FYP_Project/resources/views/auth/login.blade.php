@extends('layouts.auth')
@section('title', 'Login')
@section('content')
    <div class="text-center mb-4">
        <div class="brand-circle"></div>
        <h4>BloomWay</h4>
        <p class="text-muted mb-0">Rehabilitation Management System</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-teal btn-block">Login</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
@endsection
