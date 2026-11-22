@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1>Hi, {{ $user->username }} @if($user->isVip())<span class="badge gold">VIP</span>@endif</h1>

<div class="grid">
    <div class="card"><div class="muted">Wallet</div><div class="big gold">{{ number_format($wallet->coins) }} GC</div></div>
    <div class="card"><div class="muted">Bank</div><div class="big">{{ number_format($wallet->bank) }} GC</div></div>
    <div class="card"><div class="muted">Total wealth</div><div class="big">{{ number_format($wallet->total()) }} GC</div></div>
</div>

<div class="card">
    <h3>Level {{ $progress['level'] }}</h3>
    <div class="bar"><i style="width:{{ $progress['pct'] }}%"></i></div>
    <p class="muted small">{{ number_format($user->profile->exp) }} EXP total · {{ number_format($progress['need'] - $progress['into']) }} EXP to level {{ $progress['level'] + 1 }}
    @if($nextRole) · next role: <b>{{ $nextRole['name'] }}</b> at level {{ $nextRole['level'] }}@endif</p>
</div>

<div class="card">
    <h3>GOAT Arcade today <span class="muted small">(resets in {{ $reset }}, Dhaka time)</span></h3>
    <div class="table-wrap"><table>
        <tr><th>Game</th><th>Plays</th><th>Left</th><th></th></tr>
        @foreach($games as $g)
        <tr>
            <td>{{ $g['name'] }}</td><td>{{ $g['used'] }}/{{ $g['limit'] }}</td><td>{{ $g['remaining'] }}</td>
            <td><a class="btn sm" href="{{ route('games.show', $g['key']) }}">Play</a>
                @if($g['claimable'])
                <form class="inline" method="POST" action="{{ route('games.claim', $g['key']) }}">@csrf<button class="btn sm gold">Claim {{ number_format($g['bonus']) }} GC</button></form>
                @elseif($g['claimed'])<span class="badge">Bonus claimed</span>@endif
            </td>
        </tr>
        @endforeach
    </table></div>
</div>

<div class="card">
    <h3>Recent coin activity</h3>
    <div class="table-wrap"><table>
        <tr><th>When</th><th>What</th><th>Amount</th><th>Balance</th></tr>
        @forelse($recent as $e)
        <tr><td>{{ $e->created_at->format('d M H:i') }}</td><td>{{ $e->reason }}</td>
            <td class="{{ $e->amount >= 0 ? 'pos' : 'neg' }}">{{ $e->amount >= 0 ? '+' : '' }}{{ number_format($e->amount) }}</td><td>{{ number_format($e->balance_after) }}</td></tr>
        @empty<tr><td colspan="4" class="muted">Nothing yet.</td></tr>@endforelse
    </table></div>
</div>
@endsection
