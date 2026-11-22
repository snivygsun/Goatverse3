<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpLedger extends Model
{
    const UPDATED_AT = null;

    protected $table = 'exp_ledger';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('The EXP ledger is immutable.'));
        static::deleting(fn () => throw new \LogicException('The EXP ledger is immutable.'));
    }
}
