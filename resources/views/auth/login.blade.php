@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="card" style="max-width:440px;margin:auto">
    <h2>Login</h2>
    <form method="POST" action="{{ url('/login') }}">@csrf
        <label for="username">Username</label>
        <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <p><button class="btn gold">Login</button></p>
    </form>
    <p class="muted small">New here? <a href="{{ route('register') }}">Create an account</a></p>
</div>
@endsection
