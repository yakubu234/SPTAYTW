<?php

namespace Tests\Unit\Services\Analysis;

use App\DTOs\Football\MatchEvidence;
use App\Enums\AnalysisStatus;
use App\Services\Analysis\MarketStatusResolver;
use App\Services\Analysis\Markets\Under45Analyser;
use PHPUnit\Framework\TestCase;

final class Under45AnalyserTest extends TestCase
{
    private function evidence(
        float $over45 = .05,
        bool $highVariance = false,
        int $sample = 10,
        float $over25 = .35,
        float $over35 = .15,
        float $average = 2.1,
        float $homeRelevantOver35 = .10,
        float $awayRelevantOver35 = .20,
        float $homeRelevantAverage = 2.0,
        float $awayRelevantAverage = 2.2,
    ): MatchEvidence {
        return new MatchEvidence(
            $sample, $over45, $over45, $over45, .70,
            .5, .4, .5, .4, .8, .7, .6, .7, .55,
            false, $highVariance,
            $over25, $over35, $average,
            $homeRelevantOver35, $awayRelevantOver35,
            $homeRelevantAverage, $awayRelevantAverage,
        );
    }

    public function test_balanced_low_goal_distribution_can_qualify_strong(): void
    {
        $result = (new Under45Analyser(new MarketStatusResolver()))->analyse($this->evidence());
        $this->assertGreaterThanOrEqual(85, $result->score);
        $this->assertSame(95, $result->dataQuality);
        $this->assertSame(AnalysisStatus::STRONG, $result->status);
        $this->assertStringContainsString('average total goals', $result->positiveSignals[1]);
    }

    public function test_zero_five_plus_rate_does_not_hide_high_four_goal_profile(): void
    {
        $result = (new Under45Analyser(new MarketStatusResolver()))->analyse(
            $this->evidence(0.0, false, 10, .65, .45, 3.35, .50, .40, 3.4, 3.2)
        );
        $this->assertLessThan(80, $result->score);
        $this->assertNotSame(AnalysisStatus::STRONG, $result->status);
        $this->assertNotEmpty($result->contradictions);
    }

    public function test_high_variance_competition_is_penalised_in_score_and_quality(): void
    {
        $normal = (new Under45Analyser(new MarketStatusResolver()))->analyse($this->evidence());
        $risk = (new Under45Analyser(new MarketStatusResolver()))->analyse($this->evidence(.05, true));
        $this->assertSame($normal->score - 15, $risk->score);
        $this->assertSame(70, $risk->dataQuality);
        $this->assertNotSame(AnalysisStatus::STRONG, $risk->status);
        $this->assertStringContainsString('high variance', implode(' ', $risk->contradictions));
    }

    public function test_missing_venue_history_reduces_data_quality(): void
    {
        $result = (new Under45Analyser(new MarketStatusResolver()))->analyse(
            $this->evidence(.05, false, 10, .35, .15, 2.1, 0, 0, 0, 0)
        );
        $this->assertSame(85, $result->dataQuality);
        $this->assertStringContainsString('Venue-relevant history', implode(' ', $result->contradictions));
    }
}
