<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class InstallGoatverse extends Command
{
    protected $signature = 'goatverse:install {--username=zishan : Main Owner username}';

    protected $description = 'Create the Main Owner account (password is typed here, hashed, and never stored in code)';

    public function handle(AccountService $accounts): int
    {
        if (User::where('is_owner', true)->exists()) {
            $this->error('A Main Owner already exists. Nothing was changed.');

            return self::FAILURE;
        }

        $username = (string) $this->option('username');

        // Non-interactive option for deployments; remove the variable after installing.
        $password = getenv('GOATVERSE_OWNER_PASSWORD') ?: null;
        if (! $password) {
            $password = (string) $this->secret('Main Owner password (min 10 chars, letters + numbers)');
            if ($password !== (string) $this->secret('Confirm password')) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        $v = Validator::make(
            ['username' => $username, 'password' => $password],
            [
                'username' => ['required', 'regex:/^[A-Za-z0-9_]{3,20}$/', 'unique:users,username'],
                'password' => ['required', 'min:10', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            ]
        );
        if ($v->fails() || stripos($password, $username) !== false) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }
            $this->error('Use a password of 10+ characters with letters and numbers that does not contain the username.');

            return self::FAILURE;
        }

        $accounts->createPlayer($username, $password, 'owner', true);
        $this->info("Main Owner '{$username}' created. You can log in now.");

        return self::SUCCESS;
    }
}
