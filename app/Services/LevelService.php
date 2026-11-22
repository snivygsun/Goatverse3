<?php

namespace App\Services;

use App\Models\ExpLedger;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserReward;
use Illuminate\Support\Facades\DB;

class LevelService
{
    /** Total EXP required for level n = 1000 * n * (n - 1) / 2 */
    public static function totalExpForLevel(int $n): int
    {
        return intdiv(1000 * $n * ($n - 1), 2);
    }

    public static function levelForExp(int $exp): int
    {
        $n = max(1, (int) floor((1 + sqrt(1 + 8 * $exp / 1000)) / 2));
        while (self::totalExpForLevel($n + 1) <= $exp) {
            $n++;
        }
        while ($n > 1 && self::totalExpForLevel($n) > $exp) {
            $n--;
        }

        return $n;
    }

    public static function progress(int $exp): array
    {
        $level = self::levelForExp($exp);
        $floor = self::totalExpForLevel($level);
        $next = self::totalExpForLevel($level + 1);
        $need = max(1, $next - $floor);

        return [
            'level' => $level,
            'floor' => $floor,
            'next' => $next,
            'into' => $exp - $floor,
            'need' => $need,
            'pct' => (int) floor((($exp - $floor) / $need) * 100),
        ];
    }

    /** Adds (or removes, when negative) EXP, writes the EXP ledger and grants permanent level roles. */
    public function addExp(User $user, int $amount, string $source, string $reason): array
    {
        return DB::transaction(function () use ($user, $amount, $source, $reason) {
            $profile = UserProfile::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            $before = (int) $profile->exp;
            $after = max(0, $before + $amount);
            $oldLevel = (int) $profile->level;
            $newLevel = self::levelForExp($after);

            $profile->forceFill(['exp' => $after, 'level' => $newLevel])->save();

            ExpLedger::create([
                'user_id' => $user->id,
                'amount' => $after - $before,
                'exp_before' => $before,
                'exp_after' => $after,
                'source' => $source,
                'reason' => mb_substr($reason, 0, 200),
            ]);

            // Permanent: roles are only ever added here, never removed when the level drops.
            foreach (config('goatverse.level_roles') as $level => $name) {
                if ($newLevel >= $level) {
                    UserReward::grant($user->id, 'level_role', (string) $level, $name, 'level:' . $level);
                }
            }

            return ['level_before' => $oldLevel, 'level_after' => $newLevel, 'exp_after' => $after];
        });
    }
}
