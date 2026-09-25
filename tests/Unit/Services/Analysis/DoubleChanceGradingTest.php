<?php

namespace Tests\Unit\Services\Analysis;

use App\Models\{Fixture, MarketAnalysis};
use App\Services\Football\MarketGrader;
use PHPUnit\Framework\TestCase;

final class DoubleChanceGradingTest extends TestCase
{
    public function test_draw_wins_on_both_sides_and_a_decisive_result_loses_on_the_other_side(): void
    {
        $grader = new MarketGrader();
        foreach ([[1, 1, 'won', 'won'], [2, 0, 'won', 'lost'], [0, 2, 'lost', 'won']] as [$home, $away, $homeResult, $awayResult]) {
            $fixture = new Fixture(['home_goals' => $home, 'away_goals' => $away]);
            $homeAnalysis = new MarketAnalysis(['market_type' => 'home_or_draw']);
            $awayAnalysis = new MarketAnalysis(['market_type' => 'away_or_draw']);
            $homeAnalysis->setRelation('fixture', $fixture);
            $awayAnalysis->setRelation('fixture', $fixture);

            $this->assertSame($homeResult, $grader->grade($homeAnalysis));
            $this->assertSame($awayResult, $grader->grade($awayAnalysis));
        }
    }
}
