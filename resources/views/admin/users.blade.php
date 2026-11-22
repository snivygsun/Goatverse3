@extends('layouts.app')
@section('title', 'Players')
@section('content')
<h1>Players</h1>
<form method="GET"><input name="q" value="{{ $q }}" placeholder="Search username"></form>
<div class="card"><div class="table-wrap"><table>
    <tr><th>ID</th><th>Username</th><th>Role</th><th>Level</th><th>Wallet</th><th>Bank</th></tr>
    @foreach($users as $u)
    <tr><td>{{ $u->id }}</td><td><a href="{{ route('admin.user', $u) }}">{{ $u->username }}</a></td><td>{{ $u->is_owner ? 'owner' : $u->role }}</td>
        <td>{{ $u->profile?->level }}</td><td>{{ number_format($u->wallet?->coins ?? 0) }}</td><td>{{ number_format($u->wallet?->bank ?? 0) }}</td></tr>
    @endforeach
</table></div></div>
{{ $users->links('pagination::simple-default') }}
@endsection
