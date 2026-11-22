<?php

namespace App\Http\Controllers;

use App\Models\CoinLedger;
use App\Services\GameService;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __invoke(Request $r, GameService $games)
    {
        $user = $r->user()->load(['profile', 'wallet']);
        $progress = LevelService::progress((int) $user->profile->exp);

        $nextRole = null;
        foreach (config('goatverse.level_roles') as $lvl => $name) {
            if ($lvl > $progress['level']) {
                $nextRole = ['level' => $lvl, 'name' => $name];
                break;
            }
        }

        return view('dashboard', [
            'user' => $user,
            'wallet' => $user->wallet,
            'progress' => $progress,
            'nextRole' => $nextRole,
            'games' => $games->summary($user),
            'reset' => $games->resetIn(),
            'recent' => CoinLedger::where('user_id', $user->id)->latest('id')->limit(8)->get(),
        ]);
    }
}
