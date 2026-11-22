<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_rounds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('game', 30);
            $t->unsignedBigInteger('bet');
            $t->boolean('won');
            $t->unsignedBigInteger('payout')->default(0);
            $t->unsignedBigInteger('bonus_coins')->default(0);
            $t->unsignedInteger('bonus_exp')->default(0);
            $t->json('result')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['user_id', 'game', 'id']);
        });

        Schema::create('daily_game_counters', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('game', 30);
            $t->date('play_date');
            $t->unsignedInteger('plays')->default(0);
            $t->timestamp('completion_claimed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'game', 'play_date']);
        });

        Schema::create('game_stats', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('game', 30);
            $t->unsignedInteger('plays')->default(0);
            $t->unsignedInteger('wins')->default(0);
            $t->unsignedInteger('losses')->default(0);
            $t->unsignedInteger('current_streak')->default(0);
            $t->unsignedInteger('best_streak')->default(0);
            $t->unsignedBigInteger('total_won')->default(0);
            $t->unsignedBigInteger('total_lost')->default(0);
            $t->timestamps();
            $t->unique(['user_id', 'game']);
        });
    }

    public function down(): void
    {
        foreach (['game_stats', 'daily_game_counters', 'game_rounds'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
