<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(private EconomyService $economy)
    {
    }

    public function createPlayer(string $username, string $password, string $role = 'player', bool $owner = false): User
    {
        return DB::transaction(function () use ($username, $password, $role, $owner) {
            $user = new User(['username' => $username, 'password' => $password]);
            $user->forceFill(['role' => $role, 'is_owner' => $owner])->save();

            UserProfile::create(['user_id' => $user->id]);
            Wallet::create(['user_id' => $user->id]);

            $start = (int) config('goatverse.starting_coins');
            if ($start > 0) {
                $this->economy->credit($user, $start, 'signup_bonus', 'Welcome bonus', [], 'signup:' . $user->id);
            }

            return $user;
        });
    }
}
