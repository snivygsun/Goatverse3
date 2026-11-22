<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->banned_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'This account is suspended.']);
        }

        return $next($request);
    }
}
