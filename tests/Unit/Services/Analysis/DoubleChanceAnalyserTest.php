<?php

namespace Tests\Unit\Services\Analysis;

use App\DTOs\Football\MatchEvidence;
use App\Enums\{AnalysisStatus, MarketType};
use App\Services\Analysis\MarketStatusResolver;
use App\Services\Analysis\Markets\DoubleChanceAnalyser;
use PHPUnit\Framework\TestCase;

final class DoubleChanceAnalyserTest extends TestCase
{
    private function evidence(int $splitCount = 8, float $relevant = .875, float $opponentWins = .2): MatchEvidence
    {
        return new MatchEvidence(
            sampleSize: 10, over45Rate: .1, homeTeamOver45Rate: .1, awayTeamOver45Rate: .1,
            over15Rate: .7, homeWinRate: .6, awayWinRate: .3,
            homeRelevantWinRate: .7, awayRelevantWinRate: $opponentWins,
            homeScoredRate: .8, awayScoredRate: .7, homeConcededRate: .5,
            awayConcededRate: .6, bttsRate: .5,
            homeNonLossRate: .9, awayNonLossRate: .5,
            homeRelevantNonLossRate: $relevant, awayRelevantNonLossRate: .4,
            homeRelevantSampleSize: $splitCount, awayRelevantSampleSize: 8,
        );
    }

    public function test_both_sides_are_generated_without_treating_draw_as_a_loss(): void
    {
        [$home, $away] = (new DoubleChanceAnalyser(new MarketStatusResolver()))->analyse('Home FC', 'Away FC', $this->evidence());
        $this->assertSame(MarketType::HOME_OR_DRAW, $home->market);
        $this->assertSame('Home FC Win or Draw', $home->selection);
        $this->assertSame(AnalysisStatus::STRONG, $home->status);
        $this->assertSame(MarketType::AWAY_OR_DRAW, $away->market);
        $this->assertNotSame(AnalysisStatus::STRONG, $away->status);
    }

    public function test_missing_relevant_history_cannot_qualify(): void
    {
        [$home] = (new DoubleChanceAnalyser(new MarketStatusResolver()))->analyse('Home FC', 'Away FC', $this->evidence(2));
        $this->assertSame(AnalysisStatus::SKIP, $home->status);
        $this->assertLessThan(55, $home->dataQuality);
    }

    public function test_repeated_losses_reduce_screening_score(): void
    {
        $analyser = new DoubleChanceAnalyser(new MarketStatusResolver());
        [$strong] = $analyser->analyse('Home FC', 'Away FC', $this->evidence());
        [$weak] = $analyser->analyse('Home FC', 'Away FC', $this->evidence(8, .4, .6));
        $this->assertLessThan($strong->score, $weak->score);
        $this->assertNotEmpty($weak->contradictions);
    }

    public function test_high_score_with_five_venue_matches_is_only_watch(): void
    {
        [$home] = (new DoubleChanceAnalyser(new MarketStatusResolver()))->analyse('Home FC', 'Away FC', $this->evidence(5, 1.0, 0.0));
        $this->assertGreaterThanOrEqual(85, $home->score);
        $this->assertSame(70, $home->dataQuality);
        $this->assertSame(AnalysisStatus::WATCH, $home->status);
    }

    public function test_six_venue_matches_can_qualify_but_cannot_be_strong(): void
    {
        [$home] = (new DoubleChanceAnalyser(new MarketStatusResolver()))->analyse('Home FC', 'Away FC', $this->evidence(6, 1.0, 0.0));
        $this->assertSame(AnalysisStatus::QUALIFIED, $home->status);
    }
}
