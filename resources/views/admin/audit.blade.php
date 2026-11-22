@extends('layouts.app')
@section('title', 'Audit log')
@section('content')
<h1>Audit log</h1>
<div class="card">@include('admin.partials.log', ['logs' => $logs])</div>
{{ $logs->links('pagination::simple-default') }}
@endsection
