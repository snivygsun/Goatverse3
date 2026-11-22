@extends('layouts.app')
@section('title', 'Dice')
@section('content')
<h1>🎲 Dice</h1>
<div class="grid2">
    <div class="card">
        <p class="muted">{{ $def['rules'] }}</p>
        <p>Plays today: <b>{{ $info['used'] }}/{{ $info['limit'] }}</b> · resets in {{ $reset }} (Dhaka)</p>
        <form method="POST" action="{{ route('games.play', $game) }}">@csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="bet">Bet (GC) — {{ number_format($min) }} to {{ number_format($max) }}</label>
            <input id="bet" type="number" name="bet" min="{{ $min }}" max="{{ $max }}" value="{{ old('bet', $min) }}" required>
            <label class="radio"><input type="radio" name="choice" value="low" checked> Low (1–3)</label>
            <label class="radio"><input type="radio" name="choice" value="high"> High (4–6)</label>
            <p><button class="btn gold" @disabled($info['remaining'] < 1)>Roll the dice</button></p>
        </form>
        @if($info['claimable'])
        <form method="POST" action="{{ route('games.claim', $game) }}">@csrf<button class="btn">Claim daily completion bonus: {{ number_format($info['bonus']) }} GC</button></form>
        @elseif($info['claimed'])<p class="muted">Completion bonus claimed today.</p>
        @else<p class="muted small">Finish all {{ $info['limit'] }} plays to unlock today's completion bonus ({{ number_format($info['bonus']) }} GC).</p>@endif
    </div>
    <div class="card">
        <h3>Your Dice stats</h3>
        @if($stat)<p>{{ $stat->plays }} plays · {{ $stat->wins }} wins · {{ $stat->losses }} losses · best streak {{ $stat->best_streak }}</p>@else<p class="muted">No rounds yet.</p>@endif
        <h3>Last rounds</h3>
        <div class="table-wrap"><table>
            <tr><th>Bet</th><th>Roll</th><th>Result</th></tr>
            @forelse($rounds as $rd)
            <tr><td>{{ number_format($rd->bet) }}</td><td>{{ $rd->result['roll'] ?? '' }} ({{ $rd->result['choice'] ?? '' }})</td>
                <td class="{{ $rd->won ? 'pos' : 'neg' }}">{{ $rd->won ? 'Won ' . number_format($rd->payout) : 'Lost' }}</td></tr>
            @empty<tr><td colspan="3" class="muted">Nothing yet.</td></tr>@endforelse
        </table></div>
    </div>
</div>
@endsection
