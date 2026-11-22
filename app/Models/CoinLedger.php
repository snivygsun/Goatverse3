<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only ledger. Rows can never be updated or deleted through Eloquent. */
class CoinLedger extends Model
{
    const UPDATED_AT = null;

    protected $table = 'coin_ledger';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array', 'amount' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('The coin ledger is immutable.'));
        static::deleting(fn () => throw new \LogicException('The coin ledger is immutable.'));
    }
}
