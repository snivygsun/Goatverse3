<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $r, AccountService $accounts)
    {
        $data = $r->validate([
            'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'age_confirm' => ['accepted'],
        ], [
            'username.regex' => 'Username can only use letters, numbers and underscores.',
            'age_confirm.accepted' => 'You must be at least ' . config('goatverse.min_age') . ' years old to join.',
        ]);

        $user = $accounts->createPlayer($data['username'], $data['password']);
        Auth::login($user);
        $r->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to GOATVERSE!');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $cred = $r->validate(['username' => 'required|string|max:50', 'password' => 'required|string|max:100']);

        $key = 'login:' . Str::lower($cred['username']) . '|' . $r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $s = RateLimiter::availableIn($key);

            return back()->withErrors(['username' => "Too many attempts. Try again in {$s} seconds."])->onlyInput('username');
        }

        $user = User::where('username', $cred['username'])->first();
        if (! $user || ! Hash::check($cred['password'], $user->password)) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['username' => 'Wrong username or password.'])->onlyInput('username');
        }
        if ($user->banned_at) {
            return back()->withErrors(['username' => 'This account is suspended.'])->onlyInput('username');
        }

        RateLimiter::clear($key);
        Auth::login($user, $r->boolean('remember'));
        $r->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('home');
    }
}
