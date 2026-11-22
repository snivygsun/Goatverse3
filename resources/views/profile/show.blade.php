@extends('layouts.app')
@section('title', $user->username)
@section('content')
<div class="card">
    <h1>{{ $user->username }} @if($user->isVip())<span class="badge gold">VIP</span>@endif</h1>
    <p>Level <b>{{ $progress['level'] }}</b> · {{ number_format($profile->exp) }} EXP</p>
    <div class="bar"><i style="width:{{ $progress['pct'] }}%"></i></div>
    @if($profile->bio)<p>{{ $profile->bio }}</p>@endif
    <p class="muted small">
        Joined {{ $user->created_at->format('d M Y') }}
        @if($profile->gender_visibility === 'public' && $profile->gender) · {{ config('goatverse.genders')[$profile->gender] ?? '' }}@endif
        @if($profile->city_visibility === 'public' && $profile->city) · {{ $profile->city }}@endif
        @if($profile->country_visibility === 'public' && $profile->country_code) · {{ config('goatverse.countries')[$profile->country_code] ?? '' }}@endif
        @if($profile->birthdate_visibility === 'public' && $profile->birthdate) · Age {{ $profile->age() }}@endif
    </p>
</div>

<div class="card">
    <h3>GOAT Legacy</h3>
    @forelse($rewards as $rw)<span class="badge gold" title="Earned {{ $rw->earned_at->format('d M Y') }}">{{ $rw->name }}</span>@empty<span class="muted">No permanent rewards yet.</span>@endforelse
</div>

@if($stats->isNotEmpty())
<div class="card"><h3>Game stats</h3>
    <div class="table-wrap"><table>
        <tr><th>Game</th><th>Plays</th><th>Wins</th><th>Win rate</th><th>Best streak</th></tr>
        @foreach($stats as $s)<tr><td>{{ config('goatverse.games.'.$s->game.'.name', $s->game) }}</td><td>{{ $s->plays }}</td><td>{{ $s->wins }}</td><td>{{ $s->plays ? round($s->wins / $s->plays * 100) : 0 }}%</td><td>{{ $s->best_streak }}</td></tr>@endforeach
    </table></div>
</div>
@endif
@endsection
