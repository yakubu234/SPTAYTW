<?php

namespace Tests\Unit\Services\Analysis;

use App\DTOs\Football\TeamGoalEvidence;
use App\Enums\AnalysisStatus;
use App\Services\Analysis\{DataQualityService, MarketStatusResolver};
use App\Services\Analysis\Markets\TeamOver05Analyser;
use PHPUnit\Framework\TestCase;

final class TeamOver05AnalyserTest extends TestCase
{
    private function analyser(): TeamOver05Analyser
    {
        return new TeamOver05Analyser(new DataQualityService(), new MarketStatusResolver());
    }

    public function test_consistent_scoring_evidence_qualifies(): void
    {
        $result = $this->analyser()->analyse('Example FC', new TeamGoalEvidence(
            sampleSize: 10,
            teamScoredRate: .9,
            venueScoredRate: .9,
            opponentConcededRate: .9,
            opponentVenueConcededRate: .8,
            opponentCleanSheetRate: .1,
            teamFailedToScoreRate: .1,
        ));

        $this->assertGreaterThanOrEqual(80, $result->score);
        $this->assertContains($result->status, [AnalysisStatus::STRONG, AnalysisStatus::QUALIFIED]);
    }

    public function test_small_high_variance_sample_is_skipped(): void
    {
        $result = $this->analyser()->analyse('Reserve FC', new TeamGoalEvidence(
            sampleSize: 3,
            teamScoredRate: 1,
            venueScoredRate: 1,
            opponentConcededRate: 1,
            opponentVenueConcededRate: 1,
            opponentCleanSheetRate: 0,
            teamFailedToScoreRate: 0,
            highVarianceCompetition: true,
        ));

        $this->assertSame(AnalysisStatus::SKIP, $result->status);
        $this->assertLessThan(55, $result->dataQuality);
    }
}
