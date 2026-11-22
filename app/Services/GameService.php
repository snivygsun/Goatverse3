<?php

namespace App\Services;

use App\Exceptions\GoatException;
use App\Models\DailyGameCounter;
use App\Models\GameRound;
use App\Models\GameStat;
use App\Models\User;
use App\Models\UserReward;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function __construct(private EconomyService $economy, private LevelService $levels)
    {
    }

    /** "Today" always follows the server's Asia/Dhaka clock, never the player's device. */
    public static function today(): string
    {
        return Carbon::now(config('goatverse.timezone'))->toDateString();
    }

    public function resetIn(): string
    {
        $now = Carbon::now(config('goatverse.timezone'));
        $secs = abs((int) $now->diffInSeconds($now->copy()->addDay()->startOfDay()));

        return intdiv($secs, 3600) . 'h ' . intdiv($secs % 3600, 60) . 'm';
    }

    public function limitFor(User $user, string $game): int
    {
        $tier = $user->isVip() ? 'vip' : 'normal';

        return (int) (config("goatverse.games.$game.daily_plays.$tier") ?? config("goatverse.daily_plays.$tier"));
    }

    public function bonusFor(string $game): int
    {
        return (int) (config("goatverse.games.$game.completion_bonus") ?? config('goatverse.completion_bonus'));
    }

    public function betLimits(string $game): array
    {
        return [
            (int) (config("goatverse.games.$game.min_bet") ?? config('goatverse.bet.min')),
            (int) (config("goatverse.games.$game.max_bet") ?? config('goatverse.bet.max')),
        ];
    }

    /** One query for all games: plays used, remaining and completion-bonus state for today. */
    public function summary(User $user): array
    {
        $counters = DailyGameCounter::where('user_id', $user->id)->where('play_date', self::today())->get()->keyBy('game');
        $out = [];
        foreach (config('goatverse.games') as $key => $def) {
            if (! ($def['enabled'] ?? false)) {
                continue;
            }
            $c = $counters->get($key);
            $used = (int) ($c->plays ?? 0);
            $limit = $this->limitFor($user, $key);
            $claimed = (bool) ($c?->completion_claimed_at);
            $out[$key] = [
                'key' => $key,
                'name' => $def['name'],
                'used' => $used,
                'limit' => $limit,
                'remaining' => max(0, $limit - $used),
                'claimed' => $claimed,
                'claimable' => $used >= $limit && ! $claimed,
                'bonus' => $this->bonusFor($key),
            ];
        }

        return $out;
    }

    public function play(User $user, string $game, int $bet, array $input): GameRound
    {
        $def = config("goatverse.games.$game");
        if (! $def || ! ($def['enabled'] ?? false)) {
            throw new GoatException('This game is not available.');
        }
        if ($user->banned_at) {
            throw new GoatException('This account is suspended.');
        }

        [$min, $max] = $this->betLimits($game);
        if ($bet < $min || $bet > $max) {
            throw new GoatException('Bet must be between ' . number_format($min) . ' and ' . number_format($max) . ' GC.');
        }

        $engine = app($def['class']);
        $choice = $engine->validate($input);

        return DB::transaction(function () use ($user, $game, $bet, $def, $engine, $choice) {
            $counter = $this->lockedCounter($user, $game);
            if ($counter->plays >= $this->limitFor($user, $game)) {
                throw new GoatException('Daily limit reached for ' . $def['name'] . '. Resets at midnight (Dhaka time).');
            }

            $outcome = $engine->resolve($choice); // decided on the server only
            $won = (bool) $outcome['won'];
            $payout = $won ? (int) floor($bet * $outcome['multiplier']) : 0;
            $bonusGc = $won ? (int) ($def['win_bonus_gc'] ?? 0) : 0;
            $bonusExp = $won ? (int) ($def['win_bonus_exp'] ?? 0) : (int) ($def['loss_exp'] ?? 0);

            $round = GameRound::create([
                'user_id' => $user->id,
                'game' => $game,
                'bet' => $bet,
                'won' => $won,
                'payout' => $payout,
                'bonus_coins' => $bonusGc,
                'bonus_exp' => $bonusExp,
                'result' => $outcome['result'],
            ]);

            $meta = ['round_id' => $round->id, 'game' => $game];
            $src = 'game:' . $game;

            $this->economy->debit($user, $bet, 'game_stake', $def['name'] . ' stake', $meta, null, $src);

            if ($won) {
                if ($payout > 0) {
                    $this->economy->credit($user, $payout, 'game_payout', $def['name'] . ' payout', $meta, null, $src);
                }
                if ($bonusGc > 0) {
                    $this->economy->credit($user, $bonusGc, 'game_win_bonus', $def['name'] . ' win bonus', $meta, null, $src);
                }
            } else {
                $penalty = (int) ($def['loss_penalty_gc'] ?? 0);
                $balance = (int) Wallet::where('user_id', $user->id)->value('coins');
                $penalty = min($penalty, $balance);
                if ($penalty > 0) {
                    $this->economy->debit($user, $penalty, 'game_loss_penalty', $def['name'] . ' loss penalty', $meta, null, $src);
                }
            }

            if ($bonusExp > 0) {
                $this->levels->addExp($user, $bonusExp, $src, $def['name'] . ($won ? ' win' : ' play'));
            }

            $counter->increment('plays');
            $this->updateStats($user, $game, $def['name'], $won, $bet, $payout + $bonusGc);

            return $round;
        });
    }

    public function describe(GameRound $round): string
    {
        $def = config("goatverse.games.{$round->game}");
        $engine = app($def['class']);
        $text = $engine->describe($round->result, $round->won) . ' ';

        if ($round->won) {
            $text .= 'You won! Payout ' . number_format($round->payout) . ' GC';
            if ($round->bonus_coins) {
                $text .= ' + ' . number_format($round->bonus_coins) . ' GC bonus';
            }
            if ($round->bonus_exp) {
                $text .= ' + ' . number_format($round->bonus_exp) . ' EXP';
            }

            return $text . '.';
        }

        return $text . 'You lost ' . number_format($round->bet) . ' GC.';
    }

    /** Completion bonus: once per user, per game, per Dhaka day. Unique key + locked row stop double claims. */
    public function claimCompletion(User $user, string $game): int
    {
        if (! config("goatverse.games.$game.enabled")) {
            throw new GoatException('This game is not available.');
        }

        return DB::transaction(function () use ($user, $game) {
            $counter = $this->lockedCounter($user, $game);
            if ($counter->completion_claimed_at) {
                throw new GoatException('Bonus already claimed today.');
            }
            if ($counter->plays < $this->limitFor($user, $game)) {
                throw new GoatException('Finish all of today\'s plays first.');
            }

            $amount = $this->bonusFor($game);
            $this->economy->credit(
                $user, $amount, 'daily_completion_bonus', 'Daily completion bonus: ' . config("goatverse.games.$game.name"),
                ['game' => $game], 'completion:' . $user->id . ':' . $game . ':' . self::today(), 'game:' . $game
            );
            $counter->forceFill(['completion_claimed_at' => now()])->save();

            return $amount;
        });
    }

    private function lockedCounter(User $user, string $game): DailyGameCounter
    {
        $today = self::today();
        DB::table('daily_game_counters')->insertOrIgnore([
            'user_id' => $user->id, 'game' => $game, 'play_date' => $today,
            'plays' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DailyGameCounter::where('user_id', $user->id)->where('game', $game)
            ->where('play_date', $today)->lockForUpdate()->firstOrFail();
    }

    /** Mastery is tracked per game: Slots wins never count toward Dice. */
    private function updateStats(User $user, string $game, string $name, bool $won, int $bet, int $gained): void
    {
        DB::table('game_stats')->insertOrIgnore([
            'user_id' => $user->id, 'game' => $game, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $s = GameStat::where('user_id', $user->id)->where('game', $game)->lockForUpdate()->firstOrFail();

        $s->plays++;
        if ($won) {
            $s->wins++;
            $s->current_streak++;
            $s->best_streak = max($s->best_streak, $s->current_streak);
            $s->total_won += $gained;
        } else {
            $s->losses++;
            $s->current_streak = 0;
            $s->total_lost += $bet;
        }
        $s->save();

        $tiers = config("goatverse.mastery.$game", config('goatverse.mastery.default'));
        foreach ($tiers as $wins => $title) {
            if ($s->wins >= $wins) {
                UserReward::grant($user->id, 'mastery', $game . ':' . $wins, "$name $title", 'mastery:' . $game, ['wins' => $wins]);
            }
        }
    }
}
