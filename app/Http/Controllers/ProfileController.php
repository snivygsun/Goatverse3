<?php

namespace App\Http\Controllers;

use App\Models\GameStat;
use App\Models\User;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $r)
    {
        return view('profile.edit', ['profile' => $r->user()->profile]);
    }

    public function update(Request $r)
    {
        $minAge = (int) config('goatverse.min_age');
        $vis = ['sometimes', Rule::in(['public', 'private'])];

        $data = $r->validate([
            'gender' => ['nullable', Rule::in(array_keys(config('goatverse.genders')))],
            'country_code' => ['nullable', Rule::in(array_keys(config('goatverse.countries')))],
            'city' => ['nullable', 'string', 'max:60'],
            'birthdate' => ['nullable', 'date_format:Y-m-d', 'after:1900-01-01', 'before_or_equal:' . now()->subYears($minAge)->toDateString()],
            'bio' => ['nullable', 'string', 'max:160'],
            'gender_visibility' => $vis,
            'country_visibility' => $vis,
            'city_visibility' => $vis,
            'birthdate_visibility' => $vis,
        ], [
            'birthdate.before_or_equal' => "You must be at least {$minAge} years old.",
        ]);

        $r->user()->profile->fill($data)->save();

        return redirect()->route('profile.edit')->with('status', 'Profile saved.');
    }

    /** Public profile: private fields and balances are never exposed. */
    public function show(string $username)
    {
        $user = User::where('username', $username)->with('profile')->firstOrFail();
        $p = $user->profile;

        return view('profile.show', [
            'user' => $user,
            'profile' => $p,
            'progress' => LevelService::progress((int) $p->exp),
            'rewards' => $user->rewards()->whereNull('revoked_at')->orderBy('earned_at')->get(),
            'stats' => GameStat::where('user_id', $user->id)->get(),
        ]);
    }
}
