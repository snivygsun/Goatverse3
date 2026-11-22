<?php

namespace Tests\Feature;

use App\Exceptions\GoatException;
use App\Models\CoinLedger;
use App\Models\User;
use App\Models\UserReward;
use App\Models\Wallet;
use App\Services\AccountService;
use App\Services\EconomyService;
use App\Services\GameService;
use App\Services\LevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoatverseTest extends TestCase
{
    use RefreshDatabase;

    private function player(string $name = 'tester', string $role = 'player'): User
    {
        return app(AccountService::class)->createPlayer($name, 'secret-pass-123', $role);
    }

    public function test_registration_creates_account_wallet_and_signup_bonus(): void
    {
        $this->post('/register', [
            'username' => 'newbie', 'password' => 'password123',
            'password_confirmation' => 'password123', 'age_confirm' => '1',
        ])->assertRedirect('/dashboard');

        $user = User::where('username', 'newbie')->firstOrFail();
        $this->assertSame((int) config('goatverse.starting_coins'), (int) $user->wallet->coins);
        $this->assertFalse($user->is_owner);
        $this->assertSame('player', $user->role);
    }

    public function test_registration_requires_age_confirmation(): void
    {
        $this->post('/register', [
            'username' => 'kiddo', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('age_confirm');
    }

    public function test_profile_rejects_birthdate_under_minimum_age(): void
    {
        $user = $this->player();
        $this->actingAs($user)->put('/profile', ['birthdate' => now()->subYears(10)->toDateString()])
            ->assertSessionHasErrors('birthdate');

        $this->actingAs($user)->put('/profile', [
            'gender' => 'male', 'country_code' => 'BD', 'city' => 'Dhaka', 'birthdate' => now()->subYears(20)->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Dhaka', $user->profile->fresh()->city);
    }

    public function test_credit_debit_and_ledger_are_consistent(): void
    {
        $user = $this->player();
        $economy = app(EconomyService::class);
        $start = (int) Wallet::where('user_id', $user->id)->value('coins');

        $economy->credit($user, 500, 'test', 'credit');
        $economy->debit($user, 200, 'test', 'debit');

        $this->assertSame($start + 300, (int) Wallet::where('user_id', $user->id)->value('coins'));
        $last = CoinLedger::where('user_id', $user->id)->latest('id')->first();
        $this->assertSame($start + 300, (int) $last->balance_after);
    }

    public function test_cannot_spend_more_than_balance(): void
    {
        $user = $this->player();
        $this->expectException(GoatException::class);
        app(EconomyService::class)->debit($user, 999999999, 'test', 'too much');
    }

    public function test_idempotency_key_prevents_double_credit(): void
    {
        $user = $this->player();
        $economy = app(EconomyService::class);
        $before = (int) Wallet::where('user_id', $user->id)->value('coins');

        $economy->credit($user, 1000, 'test', 'once', [], 'same-key');
        $economy->credit($user, 1000, 'test', 'once', [], 'same-key');

        $this->assertSame($before + 1000, (int) Wallet::where('user_id', $user->id)->value('coins'));
    }

    public function test_ledger_rows_are_immutable(): void
    {
        $user = $this->player();
        $row = CoinLedger::where('user_id', $user->id)->firstOrFail();
        $this->expectException(\LogicException::class);
        $row->update(['amount' => 1]);
    }

    public function test_max_bet_is_enforced_on_the_server(): void
    {
        $user = $this->player();
        $this->expectException(GoatException::class);
        app(GameService::class)->play($user, 'dice', 10000001, ['choice' => 'low']);
    }

    public function test_daily_limit_and_single_completion_claim(): void
    {
        $user = $this->player();
        $games = app(GameService::class);
        $limit = $games->limitFor($user, 'dice');
        $this->assertSame(20, $limit);

        for ($i = 0; $i < $limit; $i++) {
            $games->play($user, 'dice', 100, ['choice' => 'low']);
        }

        try {
            $games->play($user, 'dice', 100, ['choice' => 'low']);
            $this->fail('The 21st play should be refused.');
        } catch (GoatException $e) {
            $this->assertStringContainsString('Daily limit', $e->getMessage());
        }

        $this->assertSame(1000000, $games->claimCompletion($user, 'dice'));
        $this->expectException(GoatException::class);
        $games->claimCompletion($user, 'dice');
    }

    public function test_vip_gets_30_plays(): void
    {
        $user = $this->player();
        $user->forceFill(['vip_until' => now()->addDay()])->save();
        $this->assertSame(30, app(GameService::class)->limitFor($user->fresh(), 'dice'));
    }

    public function test_level_roles_stay_after_exp_is_removed(): void
    {
        $user = $this->player();
        $levels = app(LevelService::class);

        $levels->addExp($user, 10000000, 'test', 'big grant');
        $this->assertTrue(UserReward::where('user_id', $user->id)->where('type', 'level_role')->where('reward_key', '100')->exists());

        $levels->addExp($user, -10000000, 'test', 'reset');
        $this->assertSame(1, (int) $user->profile->fresh()->level);
        $this->assertTrue(UserReward::where('user_id', $user->id)->where('type', 'level_role')->where('reward_key', '100')->whereNull('revoked_at')->exists());
    }

    public function test_only_the_owner_can_revoke_permanent_rewards(): void
    {
        $mod = $this->player('mod1', 'moderator');
        $target = $this->player('target1');
        UserReward::grant($target->id, 'title', 'x', 'X', 'test');
        $reward = UserReward::firstOrFail();

        $this->actingAs($mod)->post("/admin/users/{$target->id}/rewards/{$reward->id}/revoke", ['reason' => 'testing'])->assertForbidden();
        $this->assertNull($reward->fresh()->revoked_at);

        $owner = app(AccountService::class)->createPlayer('boss', 'secret-pass-123', 'owner', true);
        $this->actingAs($owner)->post("/admin/users/{$target->id}/rewards/{$reward->id}/revoke", ['reason' => 'testing'])->assertRedirect();
        $this->assertNotNull($reward->fresh()->revoked_at);
    }

    public function test_moderator_cannot_adjust_coins(): void
    {
        $mod = $this->player('mod2', 'moderator');
        $target = $this->player('target2');
        $this->actingAs($mod)->post("/admin/users/{$target->id}/adjust", ['type' => 'coins', 'amount' => 500, 'reason' => 'nope'])->assertForbidden();
    }
}
