<?php

namespace App\Services;

use App\Exceptions\GoatException;
use App\Models\CoinLedger;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The ONLY place where balances change. Every change locks the wallet row,
 * checks the balance, updates it and appends an immutable ledger entry in one DB transaction.
 */
class EconomyService
{
    public function credit(User $user, int $amount, string $type, string $reason, array $meta = [], ?string $key = null, string $source = 'system', ?int $related = null, string $account = 'coins'): CoinLedger
    {
        return $this->apply($user, $amount, $type, $reason, $meta, $key, $source, $related, $account);
    }

    public function debit(User $user, int $amount, string $type, string $reason, array $meta = [], ?string $key = null, string $source = 'system', ?int $related = null, string $account = 'coins'): CoinLedger
    {
        return $this->apply($user, -$amount, $type, $reason, $meta, $key, $source, $related, $account);
    }

    private function apply(User $user, int $signed, string $type, string $reason, array $meta, ?string $key, string $source, ?int $related, string $account): CoinLedger
    {
        if ($signed === 0) {
            throw new GoatException('Amount must not be zero.');
        }
        if (! in_array($account, ['coins', 'bank'], true)) {
            throw new GoatException('Unknown account.');
        }

        return DB::transaction(function () use ($user, $signed, $type, $reason, $meta, $key, $source, $related, $account) {
            // Lock first: concurrent requests for the same user are serialized here.
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($key !== null && ($existing = CoinLedger::where('idempotency_key', $key)->first())) {
                return $existing; // already applied: no double reward
            }

            $before = (int) $wallet->{$account};
            $after = $before + $signed;
            if ($after < 0) {
                throw new GoatException('Not enough coins.');
            }

            $wallet->{$account} = $after;
            $wallet->save();

            return CoinLedger::create([
                'transaction_id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'account' => $account,
                'type' => $type,
                'amount' => $signed,
                'balance_before' => $before,
                'balance_after' => $after,
                'related_user_id' => $related,
                'reason' => mb_substr($reason, 0, 200),
                'source' => $source,
                'status' => 'completed',
                'meta' => $meta ?: null,
                'idempotency_key' => $key,
            ]);
        });
    }
}
