@extends('layouts.auth')
@section('title', 'Reset Password')
@section('content')
    <h4 class="text-center mb-3">Reset Password</h4>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="form-group">
            <label>New password</label>
            <input type="password" name="password" class="form-control" required autofocus>
            <small class="text-muted">At least 8 characters, with letters and numbers.</small>
        </div>
        <div class="form-group">
            <label>Confirm new password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-teal btn-block">Reset password</button>
    </form>
@endsection