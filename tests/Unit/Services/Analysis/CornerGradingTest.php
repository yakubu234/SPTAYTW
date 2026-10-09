<?php

namespace Tests\Unit\Services\Analysis;

use App\Models\{Fixture, MarketAnalysis};
use App\Services\Football\MarketGrader;
use PHPUnit\Framework\TestCase;

final class CornerGradingTest extends TestCase
{
    public function test_boundaries_and_missing_counts(): void
    {
        $grader = new MarketGrader();
        $fixture = new Fixture(['home_goals' => 0, 'away_goals' => 0, 'home_corners' => 2, 'away_corners' => 2]);
        $over = new MarketAnalysis(['market_type' => 'corners_over_4_5']);
        $under = new MarketAnalysis(['market_type' => 'corners_under_18']);
        $over->setRelation('fixture', $fixture);
        $under->setRelation('fixture', $fixture);
        $this->assertSame('lost', $grader->grade($over));
        $this->assertSame('won', $grader->grade($under));
        $fixture->home_corners = 5;
        $fixture->away_corners = 12;
        $this->assertSame('won', $grader->grade($over));
        $this->assertSame('won', $grader->grade($under));
        $fixture->away_corners = 13;
        $this->assertSame('lost', $grader->grade($under));
        $fixture->away_corners = null;
        $this->assertNull($grader->grade($over));
        $this->assertNull($grader->grade($under));
    }
}
