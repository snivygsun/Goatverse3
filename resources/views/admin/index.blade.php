@extends('layouts.app')
@section('title', 'Admin')
@section('content')
<h1>Admin</h1>
<p><a href="{{ route('admin.users') }}">Players</a>@if(auth()->user()->hasPermission('view_audit')) · <a href="{{ route('admin.audit') }}">Audit log</a>@endif</p>
<div class="grid">
    <div class="card"><div class="muted">Players</div><div class="big">{{ number_format($users) }}</div></div>
    <div class="card"><div class="muted">Coins in wallets</div><div class="big gold">{{ number_format($coins) }}</div></div>
    <div class="card"><div class="muted">Coins in banks</div><div class="big">{{ number_format($bank) }}</div></div>
    <div class="card"><div class="muted">Rounds played</div><div class="big">{{ number_format($rounds) }}</div></div>
</div>
<div class="card"><h3>Recent admin actions</h3>@include('admin.partials.log', ['logs' => $recent])</div>
@endsection
