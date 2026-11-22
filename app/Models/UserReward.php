<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Permanent collection: level roles, mastery rewards, titles, emblems. Only the Main Owner may revoke. */
class UserReward extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array', 'earned_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** Idempotent: returns true only the first time. A revoked reward is never re-granted automatically. */
    public static function grant(int $userId, string $type, string $key, string $name, string $source, array $meta = []): bool
    {
        return DB::table('user_rewards')->insertOrIgnore([
            'user_id' => $userId,
            'type' => $type,
            'reward_key' => $key,
            'name' => $name,
            'source' => $source,
            'meta' => json_encode($meta),
            'earned_at' => now(),
        ]) > 0;
    }
}
