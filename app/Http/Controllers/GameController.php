<?php

namespace App\Http\Controllers;

use App\Exceptions\GoatException;
use App\Models\GameRound;
use App\Models\GameStat;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function __construct(private GameService $games)
    {
    }

    public function index(Request $r)
    {
        return view('games.index', [
            'games' => $this->games->summary($r->user()),
            'reset' => $this->games->resetIn(),
        ]);
    }

    public function show(Request $r, string $game)
    {
        $this->ensureEnabled($game);
        $user = $r->user();

        // One-time token: pressing "Play" twice (double click, refresh) cannot play two rounds.
        $token = (string) Str::uuid();
        $r->session()->put("play_token.$game", $token);

        [$min, $max] = $this->games->betLimits($game);

        return view("games.$game", [
            'game' => $game,
            'def' => config("goatverse.games.$game"),
            'info' => $this->games->summary($user)[$game],
            'reset' => $this->games->resetIn(),
            'token' => $token,
            'min' => $min,
            'max' => $max,
            'rounds' => GameRound::where('user_id', $user->id)->where('game', $game)->latest('id')->limit(10)->get(),
            'stat' => GameStat::where('user_id', $user->id)->where('game', $game)->first(),
        ]);
    }

    public function play(Request $r, string $game)
    {
        $this->ensureEnabled($game);
        $data = $r->validate(['bet' => 'required|integer|min:1', 'token' => 'required|string']);

        $expected = $r->session()->pull("play_token.$game");
        if (! $expected || ! hash_equals($expected, $data['token'])) {
            return redirect()->route('games.show', $game)->with('error', 'That round was already submitted. Please try again.');
        }

        try {
            $round = $this->games->play($r->user(), $game, (int) $data['bet'], $r->only('choice'));
        } catch (GoatException $e) {
            return redirect()->route('games.show', $game)->with('error', $e->getMessage());
        }

        return redirect()->route('games.show', $game)->with('status', $this->games->describe($round));
    }

    public function claim(Request $r, string $game)
    {
        $this->ensureEnabled($game);
        try {
            $amount = $this->games->claimCompletion($r->user(), $game);
        } catch (GoatException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Completion bonus claimed: ' . number_format($amount) . ' GC!');
    }

    private function ensureEnabled(string $game): void
    {
        abort_unless(config("goatverse.games.$game.enabled"), 404);
    }
}
