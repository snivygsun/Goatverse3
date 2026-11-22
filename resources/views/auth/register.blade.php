@extends('layouts.app')
@section('title', 'Join')
@section('content')
<div class="card" style="max-width:440px;margin:auto">
    <h2>Join GOATVERSE</h2>
    <form method="POST" action="{{ url('/register') }}">@csrf
        <label for="username">Username</label>
        <input id="username" name="username" value="{{ old('username') }}" maxlength="20" required autofocus autocomplete="username">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        <label class="check"><input type="checkbox" name="age_confirm" value="1" required> I am {{ config('goatverse.min_age') }} years old or older</label>
        <p><button class="btn gold">Create account</button></p>
    </form>
    <p class="muted small">Already playing? <a href="{{ route('login') }}">Login</a></p>
</div>
@endsection
