@extends('layouts.app')
@section('title', 'GOAT Arcade')
@section('content')
<h1>GOAT Arcade</h1>
<p class="muted">Daily plays reset in {{ $reset }} (midnight, Dhaka time).</p>
<div class="grid">
@foreach($games as $g)
    <div class="card">
        <h3>{{ $g['name'] }}</h3>
        <p>Plays today: <b>{{ $g['used'] }}/{{ $g['limit'] }}</b></p>
        <a class="btn gold" href="{{ route('games.show', $g['key']) }}">Play</a>
    </div>
@endforeach
</div>
@endsection
