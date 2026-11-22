<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function record(?User $actor, string $action, ?User $target = null, array $data = []): void
    {
        static::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_user_id' => $target?->id,
            'data' => $data,
            'ip' => request()?->ip(),
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
