<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = [];

    public function total(): int
    {
        return (int) $this->coins + (int) $this->bank;
    }
}
