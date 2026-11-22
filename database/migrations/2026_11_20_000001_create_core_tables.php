<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('gender', 12)->nullable();
            $t->string('country_code', 2)->nullable();
            $t->string('city', 60)->nullable();
            $t->date('birthdate')->nullable();
            $t->string('bio', 160)->nullable();
            foreach (['gender', 'country', 'city', 'birthdate'] as $f) {
                $t->string($f . '_visibility', 10)->default('private');
            }
            $t->unsignedBigInteger('exp')->default(0)->index();
            $t->unsignedInteger('level')->default(1)->index();
            $t->timestamps();
        });

        Schema::create('wallets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('coins')->default(0)->index();
            $t->unsignedBigInteger('bank')->default(0)->index();
            $t->timestamps();
        });

        Schema::create('coin_ledger', function (Blueprint $t) {
            $t->id();
            $t->char('transaction_id', 26)->unique();
            $t->foreignId('user_id')->constrained();
            $t->string('account', 10)->default('coins');
            $t->string('type', 40)->index();
            $t->bigInteger('amount');
            $t->unsignedBigInteger('balance_before');
            $t->unsignedBigInteger('balance_after');
            $t->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reason', 200);
            $t->string('source', 60)->default('system');
            $t->string('status', 12)->default('completed');
            $t->json('meta')->nullable();
            $t->string('idempotency_key', 120)->nullable()->unique();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['user_id', 'id']);
        });

        Schema::create('exp_ledger', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->bigInteger('amount');
            $t->unsignedBigInteger('exp_before');
            $t->unsignedBigInteger('exp_after');
            $t->string('source', 60);
            $t->string('reason', 200);
            $t->timestamp('created_at')->useCurrent();
            $t->index(['user_id', 'id']);
        });

        Schema::create('user_rewards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);
            $t->string('reward_key', 80);
            $t->string('name', 80);
            $t->string('source', 60);
            $t->json('meta')->nullable();
            $t->timestamp('earned_at')->useCurrent();
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('revoke_reason', 200)->nullable();
            $t->unique(['user_id', 'type', 'reward_key']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 60)->index();
            $t->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->json('data')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'user_rewards', 'exp_ledger', 'coin_ledger', 'wallets', 'user_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
