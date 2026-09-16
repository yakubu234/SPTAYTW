<?php

namespace Tests\Unit\Services\Analysis;

use App\DTOs\Football\MatchEvidence;
use App\Enums\AnalysisStatus;
use App\Services\Analysis\MarketStatusResolver;
use App\Services\Analysis\Markets\Under45Analyser;
use PHPUnit\Framework\TestCase;

final class Under45AnalyserTest extends TestCase
{
    private function evidence(float $over45 = 0.0, bool $highVariance = false, int $sample = 10): MatchEvidence
    {
        return new MatchEvidence($sample, $over45, $over45, $over45, .75, .5, .4, .5, .4, .8, .7, .6, .7, .55, false, $highVariance);
    }

    public function test_clean_sample_is_strong_but_not_near_certain(): void
    {
        $result = (new Under45Analyser(new MarketStatusResolver()))->analyse($this->evidence());
        $this->assertSame(92, $result->score);
        $this->assertSame(95, $result->dataQuality);
        $this->assertSame(AnalysisStatus::STRONG, $result->status);
        $this->assertStringContainsString('completed matches', $result->positiveSignals[0]);
    }

    public function test_high_variance_competition_is_penalised_in_score_and_quality(): void
    {
        $result = (new Under45Analyser(new MarketStatusResolver()))->analyse($this->evidence(0.0, true));
        $this->assertSame(82, $result->score);
        $this->assertSame(75, $result->dataQuality);
        $this->assertSame(AnalysisStatus::QUALIFIED, $result->status);
        $this->assertNotEmpty($result->contradictions);
    }

    public function test_repeated_five_goal_matches_prevent_qualification(): void
    {
        $result = (new Under45Analyser(new MarketStatusResolver()))->analyse($this->evidence(.30));
        $this->assertLessThan(70, $result->score);
        $this->assertSame(AnalysisStatus::SKIP, $result->status);
    }
}
