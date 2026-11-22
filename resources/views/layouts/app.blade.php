<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'GOATVERSE') · GOATVERSE</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="stylesheet" href="{{ asset('css/goatverse.css') }}">
</head>
<body>
<header class="nav">
    <a class="brand" href="{{ route('home') }}">@include('partials.logo')<span>GOATVERSE</span></a>
    <nav>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('games.index') }}">Arcade</a>
            <a href="{{ route('profile.show', auth()->user()->username) }}">My profile</a>
            <a href="{{ route('profile.edit') }}">Settings</a>
            @if(auth()->user()->hasPermission('view_admin'))<a href="{{ route('admin.index') }}">Admin</a>@endif
            <form class="inline" method="POST" action="{{ route('logout') }}">@csrf<button class="link">Logout</button></form>
        @else
            <a href="{{ route('login') }}">Login</a>
            <a class="btn sm" href="{{ route('register') }}">Join</a>
        @endauth
    </nav>
</header>
<main class="wrap">
    @if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="flash err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @yield('content')
</main>
<footer class="foot">GOATVERSE · One Universe. Endless Fun. · Created by Zishan Al Islam</footer>
</body>
</html>
