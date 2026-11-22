@extends('layouts.app')
@section('title', 'Profile settings')
@section('content')
@php $vis = fn($f) => old($f.'_visibility', $profile->{$f.'_visibility'}); @endphp
<div class="card" style="max-width:640px;margin:auto">
    <h2>Profile settings</h2>
    <p class="muted small">Everything here is optional. Fields marked Only me stay private; Public fields show on your profile page.</p>
    <form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
        <div class="row">
            <div>
                <label for="gender">Gender</label>
                <select id="gender" name="gender"><option value="">—</option>
                    @foreach(config('goatverse.genders') as $k => $n)<option value="{{ $k }}" @selected(old('gender', $profile->gender) === $k)>{{ $n }}</option>@endforeach
                </select>
                <div class="vis"><select name="gender_visibility"><option value="private" @selected($vis('gender')==='private')>Only me</option><option value="public" @selected($vis('gender')==='public')>Public</option></select></div>
            </div>
            <div>
                <label for="country_code">Country</label>
                <select id="country_code" name="country_code"><option value="">—</option>
                    @foreach(config('goatverse.countries') as $k => $n)<option value="{{ $k }}" @selected(old('country_code', $profile->country_code) === $k)>{{ $n }}</option>@endforeach
                </select>
                <div class="vis"><select name="country_visibility"><option value="private" @selected($vis('country')==='private')>Only me</option><option value="public" @selected($vis('country')==='public')>Public</option></select></div>
            </div>
            <div>
                <label for="city">City</label>
                <input id="city" name="city" maxlength="60" value="{{ old('city', $profile->city) }}">
                <div class="vis"><select name="city_visibility"><option value="private" @selected($vis('city')==='private')>Only me</option><option value="public" @selected($vis('city')==='public')>Public</option></select></div>
            </div>
            <div>
                <label for="birthdate">Birthdate</label>
                <input id="birthdate" type="date" name="birthdate" min="1900-01-02" max="{{ now()->subYears(config('goatverse.min_age'))->toDateString() }}" value="{{ old('birthdate', $profile->birthdate) }}">
                <div class="vis"><select name="birthdate_visibility"><option value="private" @selected($vis('birthdate')==='private')>Only me</option><option value="public" @selected($vis('birthdate')==='public')>Public (age only)</option></select></div>
            </div>
        </div>
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" rows="2" maxlength="160">{{ old('bio', $profile->bio) }}</textarea>
        <p><button class="btn gold">Save</button></p>
    </form>
</div>
@endsection
