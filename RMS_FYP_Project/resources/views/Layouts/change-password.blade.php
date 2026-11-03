@extends('layouts.auth')
@section('title', 'Change Password')
@section('content')
    <h4 class="text-center mb-2">Change Your Password</h4>
    <p class="text-center text-muted">You must set a new password before continuing.</p>

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label>Current (temporary) password</label>
            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>New password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-muted">At least 8 characters, with letters and numbers.</small>
        </div>
        <div class="form-group">
            <label>Confirm new password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-teal btn-block">Save password</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button class="btn btn-link">Logout</button>
    </form>
@endsection