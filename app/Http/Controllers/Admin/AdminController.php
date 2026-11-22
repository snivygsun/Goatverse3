<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GoatException;
use App\Models\AuditLog;
use App\Models\CoinLedger;
use App\Models\GameRound;
use App\Models\User;
use App\Models\UserReward;
use App\Models\Wallet;
use App\Services\EconomyService;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index', [
            'users' => User::count(),
            'coins' => (int) Wallet::sum('coins'),
            'bank' => (int) Wallet::sum('bank'),
            'rounds' => GameRound::count(),
            'recent' => AuditLog::with(['actor', 'target'])->latest('id')->limit(8)->get(),
        ]);
    }

    public function users(Request $r)
    {
        $q = trim((string) $r->query('q'));
        $users = User::query()
            ->when($q !== '', fn ($b) => $b->where('username', 'like', '%' . $q . '%'))
            ->with(['wallet', 'profile'])->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.users', ['users' => $users, 'q' => $q]);
    }

    public function user(User $user)
    {
        $user->load(['wallet', 'profile']);

        return view('admin.user', [
            'target' => $user,
            'ledger' => CoinLedger::where('user_id', $user->id)->latest('id')->limit(15)->get(),
            'rewards' => $user->rewards()->orderBy('earned_at')->get(),
        ]);
    }

    public function adjust(Request $r, User $user, EconomyService $economy, LevelService $levels)
    {
        $actor = $r->user();
        $data = $r->validate([
            'type' => 'required|in:coins,exp',
            'amount' => 'required|integer|not_in:0|between:-1000000000,1000000000',
            'reason' => 'required|string|min:3|max:200',
        ]);

        abort_unless($actor->hasPermission($data['type'] === 'coins' ? 'adjust_coins' : 'adjust_exp'), 403);
        abort_if($user->is_owner && ! $actor->is_owner, 403);                 // nobody touches the Main Owner
        abort_if($user->id === $actor->id && ! $actor->is_owner, 403);        // no self-service for staff

        $amount = (int) $data['amount'];
        try {
            if ($data['type'] === 'coins') {
                $amount > 0
                    ? $economy->credit($user, $amount, 'admin_adjust', $data['reason'], [], null, 'admin:' . $actor->id)
                    : $economy->debit($user, -$amount, 'admin_adjust', $data['reason'], [], null, 'admin:' . $actor->id);
            } else {
                $levels->addExp($user, $amount, 'admin:' . $actor->id, $data['reason']);
            }
        } catch (GoatException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::record($actor, 'adjust_' . $data['type'], $user, ['amount' => $amount, 'reason' => $data['reason']]);

        return back()->with('status', 'Adjustment applied.');
    }

    /** Only the Main Owner can revoke a permanent reward, and it is always audited. */
    public function revoke(Request $r, User $user, UserReward $reward)
    {
        abort_unless($r->user()->is_owner, 403);
        abort_unless($reward->user_id === $user->id, 404);
        $data = $r->validate(['reason' => 'required|string|min:3|max:200']);

        $reward->forceFill([
            'revoked_at' => now(), 'revoked_by' => $r->user()->id, 'revoke_reason' => $data['reason'],
        ])->save();

        AuditLog::record($r->user(), 'revoke_reward', $user, ['reward' => $reward->name, 'reason' => $data['reason']]);

        return back()->with('status', 'Reward revoked.');
    }

    public function audit(Request $r)
    {
        abort_unless($r->user()->hasPermission('view_audit'), 403);

        return view('admin.audit', ['logs' => AuditLog::with(['actor', 'target'])->latest('id')->paginate(30)]);
    }
}
