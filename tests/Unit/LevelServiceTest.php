<?php

namespace Tests\Unit;

use App\Services\LevelService;
use PHPUnit\Framework\TestCase;

class LevelServiceTest extends TestCase
{
    public function test_exp_table_matches_the_spec(): void
    {
        $this->assertSame(0, LevelService::totalExpForLevel(1));
        $this->assertSame(1000, LevelService::totalExpForLevel(2));
        $this->assertSame(3000, LevelService::totalExpForLevel(3));
        $this->assertSame(6000, LevelService::totalExpForLevel(4));
        $this->assertSame(10000, LevelService::totalExpForLevel(5));
        $this->assertSame(15000, LevelService::totalExpForLevel(6));
    }

    public function test_level_for_exp_boundaries(): void
    {
        $this->assertSame(1, LevelService::levelForExp(0));
        $this->assertSame(1, LevelService::levelForExp(999));
        $this->assertSame(2, LevelService::levelForExp(1000));
        $this->assertSame(2, LevelService::levelForExp(2999));
        $this->assertSame(3, LevelService::levelForExp(3000));
    }

    public function test_levels_beyond_500_work(): void
    {
        $this->assertSame(500, LevelService::levelForExp(LevelService::totalExpForLevel(500)));
        $this->assertSame(499, LevelService::levelForExp(LevelService::totalExpForLevel(500) - 1));
        $this->assertSame(750, LevelService::levelForExp(LevelService::totalExpForLevel(750)));
    }
}
