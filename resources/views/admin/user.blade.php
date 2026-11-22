@extends('layouts.app')
@section('title', $target->username)
@section('content')
@php $actor = auth()->user(); @endphp
<h1>{{ $target->username }} <span class="badge">{{ $target->is_owner ? 'owner' : $target->role }}</span></h1>
<div class="grid">
    <div class="card"><div class="muted">Wallet</div><div class="big gold">{{ number_format($target->wallet->coins) }}</div></div>
    <div class="card"><div class="muted">Bank</div><div class="big">{{ number_format($target->wallet->bank) }}</div></div>
    <div class="card"><div class="muted">Level / EXP</div><div class="big">{{ $target->profile->level }} <span class="small muted">{{ number_format($target->profile->exp) }} EXP</span></div></div>
</div>

@if($actor->hasPermission('adjust_coins') || $actor->hasPermission('adjust_exp'))
<div class="card"><h3>Adjust balance</h3>
    <form method="POST" action="{{ route('admin.adjust', $target) }}">@csrf
        <div class="row">
            <div><label>Type</label><select name="type"><option value="coins">Coins (GC)</option><option value="exp">EXP</option></select></div>
            <div><label>Amount (negative to remove)</label><input type="number" name="amount" required></div>
        </div>
        <label>Reason (saved in the audit log)</label><input name="reason" maxlength="200" required>
        <p><button class="btn gold">Apply</button></p>
    </form>
</div>
@endif

<div class="card"><h3>Permanent collection</h3>
    @forelse($rewards as $rw)
    <div style="margin:8px 0">
        <span class="badge {{ $rw->revoked_at ? '' : 'gold' }}">{{ $rw->name }}</span>
        <span class="muted small">{{ $rw->type }} · {{ $rw->earned_at->format('d M Y') }}@if($rw->revoked_at) · REVOKED: {{ $rw->revoke_reason }}@endif</span>
        @if($actor->is_owner && ! $rw->revoked_at)
        <form class="inline" method="POST" action="{{ route('admin.revoke', [$target, $rw]) }}">@csrf
            <input name="reason" placeholder="Reason" required style="width:160px;display:inline-block"> <button class="btn sm">Revoke</button>
        </form>
        @endif
    </div>
    @empty<span class="muted">None yet.</span>@endforelse
</div>

<div class="card"><h3>Recent ledger</h3><div class="table-wrap"><table>
    <tr><th>When</th><th>Type</th><th>Reason</th><th>Amount</th><th>Balance</th></tr>
    @foreach($ledger as $e)
    <tr><td>{{ $e->created_at->format('d M H:i') }}</td><td>{{ $e->type }}</td><td>{{ $e->reason }}</td>
        <td class="{{ $e->amount >= 0 ? 'pos' : 'neg' }}">{{ number_format($e->amount) }}</td><td>{{ number_format($e->balance_after) }}</td></tr>
    @endforeach
</table></div></div>
@endsection
