<?php

return [
    'timezone' => 'Asia/Dhaka',
    'min_age' => 12,
    'starting_coins' => 10000,

    // Global bet limits (a game may override with its own min_bet / max_bet).
    'bet' => ['min' => 100, 'max' => 10000000],

    // Plays per game per day (a game may override with daily_plays => ['normal' => x, 'vip' => y]).
    'daily_plays' => ['normal' => 20, 'vip' => 30],
    'completion_bonus' => 1000000,

    'games' => [
        'dice' => [
            'name' => 'Dice',
            'enabled' => true,
            'class' => \App\Games\DiceGame::class,
            'rules' => 'Pick Low (1-3) or High (4-6). The server rolls one die. Win and you get your stake back plus profit.',
            'payout_multiplier' => 1.9, // total payout = floor(bet * multiplier) on a win
            'win_bonus_gc' => 300,      // extra coins on a win (set 0 to disable)
            'win_bonus_exp' => 300,     // EXP on a win
            'loss_penalty_gc' => 0,     // extra coins lost on a loss, on top of the stake
            'loss_exp' => 0,
        ],
    ],

    'level_roles' => [10 => 'Rookie', 20 => 'Veteran', 50 => 'Elite', 100 => 'Legend', 200 => 'Mythic', 500 => 'Ascendant'],

    // Win milestones per game. Use a game key to override 'default'.
    'mastery' => [
        'default' => [10 => 'Novice', 25 => 'Apprentice', 50 => 'Expert', 100 => 'Master', 250 => 'Grandmaster', 500 => 'Legend'],
    ],

    // Owner (is_owner = true) always has every permission.
    'permissions' => [
        'admin' => ['view_admin', 'adjust_coins', 'adjust_exp', 'view_audit'],
        'moderator' => ['view_admin'],
    ],

    'genders' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not' => 'Prefer not to say'],

    'countries' => [
        'BD' => 'Bangladesh', 'IN' => 'India', 'PK' => 'Pakistan', 'NP' => 'Nepal', 'LK' => 'Sri Lanka', 'BT' => 'Bhutan',
        'MV' => 'Maldives', 'AF' => 'Afghanistan', 'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada',
        'AU' => 'Australia', 'NZ' => 'New Zealand', 'IE' => 'Ireland', 'DE' => 'Germany', 'FR' => 'France', 'IT' => 'Italy',
        'ES' => 'Spain', 'NL' => 'Netherlands', 'SE' => 'Sweden', 'NO' => 'Norway', 'CH' => 'Switzerland', 'SA' => 'Saudi Arabia',
        'AE' => 'United Arab Emirates', 'QA' => 'Qatar', 'KW' => 'Kuwait', 'OM' => 'Oman', 'BH' => 'Bahrain', 'MY' => 'Malaysia',
        'SG' => 'Singapore', 'ID' => 'Indonesia', 'TH' => 'Thailand', 'VN' => 'Vietnam', 'PH' => 'Philippines', 'JP' => 'Japan',
        'KR' => 'South Korea', 'CN' => 'China', 'TR' => 'Turkey', 'EG' => 'Egypt', 'NG' => 'Nigeria', 'ZA' => 'South Africa',
        'KE' => 'Kenya', 'BR' => 'Brazil', 'MX' => 'Mexico', 'RU' => 'Russia',
    ],
];
