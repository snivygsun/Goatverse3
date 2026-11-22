<?php

namespace App\Games;

use App\Exceptions\GoatException;

class DiceGame implements GameEngine
{
    public function validate(array $input): mixed
    {
        $choice = $input['choice'] ?? null;
        if (! in_array($choice, ['low', 'high'], true)) {
            throw new GoatException('Choose Low (1-3) or High (4-6).');
        }

        return $choice;
    }

    public function resolve(mixed $choice): array
    {
        $roll = random_int(1, 6);
        $won = ($choice === 'low' && $roll <= 3) || ($choice === 'high' && $roll >= 4);

        return [
            'won' => $won,
            'result' => ['roll' => $roll, 'choice' => $choice],
            'multiplier' => (float) config('goatverse.games.dice.payout_multiplier', 1.9),
        ];
    }

    public function describe(array $result, bool $won): string
    {
        return "Rolled {$result['roll']} (you picked {$result['choice']}).";
    }
}
