<?php

namespace App\Games;

/** Implement this and register the class in config/goatverse.php to add a new game. */
interface GameEngine
{
    /** Validate the player's choice. Throws GoatException when invalid. */
    public function validate(array $input): mixed;

    /** Decide the outcome on the server. Returns ['won' => bool, 'result' => array, 'multiplier' => float]. */
    public function resolve(mixed $choice): array;

    /** One-line text describing a finished round. */
    public function describe(array $result, bool $won): string;
}
