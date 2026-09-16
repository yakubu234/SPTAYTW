<?php

namespace App\Services\Analysis\Markets;

use App\DTOs\Football\{MarketAnalysisResult, MatchEvidence};
use App\Enums\MarketType;
use App\Services\Analysis\MarketStatusResolver;

final class TeamOrGGAnalyser
{
    public function __construct(private MarketStatusResolver $status) {}

    public function analyse(string $home, string $away, MatchEvidence $e): array
    {
        return [
            $this->side(true, $home, $e),
            $this->side(false, $away, $e),
        ];
    }

    private function side(bool $home, string $name, MatchEvidence $e): MarketAnalysisResult
    {
        $win = $home ? $e->homeRelevantWinRate : $e->awayRelevantWinRate;
        $scored = $home ? $e->homeScoredRate : $e->awayScoredRate;
        $other = $home ? $e->awayScoredRate : $e->homeScoredRate;
        $gg = min($scored, $other);

        $positive = [
            sprintf('Evidence: selected-team relevant win rate %.0f%%.', $win * 100),
            sprintf('Evidence: selected-team scoring rate %.0f%%.', $scored * 100),
            sprintf('Evidence: opponent scoring rate %.0f%%.', $other * 100),
            sprintf('Evidence: recent BTTS rate %.0f%%.', $e->bttsRate * 100),
        ];
        $contradictions = [];

        // Team-or-GG has two routes to win, but neither route should be allowed to
        // hide weak evidence in the other. It was historically a costly market, so
        // demand both a useful win route and independently strong scoring evidence.
        $score = (int) round(15 + ($win * 30) + ($gg * 30) + ($e->bttsRate * 15));
        $quality = min(100, 45 + $e->sampleSize * 5);

        if ($win >= .55) $positive[] = 'Selected team has a useful relevant-split win rate.';
        if ($gg >= .70 && $e->bttsRate >= .60) $positive[] = 'The GG route has independent recent scoring support.';

        if ($win < .45) {
            $contradictions[] = 'The selected-team win route is not strong enough.';
            $score -= 12;
        }
        if ($gg < .70 || $e->bttsRate < .60) {
            $contradictions[] = 'The GG route lacks independently strong evidence.';
            $score -= 15;
        }
        if ($e->sampleSize < 7) {
            $contradictions[] = 'Recent sample is too small for a compound market.';
            $score -= 10;
        }
        if ($e->highVarianceCompetition) {
            $contradictions[] = 'Competition or teams are classified as high variance.';
            $score -= 15;
            $quality -= 25;
        }
        if ($e->rotationRisk) {
            $contradictions[] = 'Rotation risk.';
            $score -= 7;
        }

        $score = max(0, min(100, $score));
        $quality = max(0, min(100, $quality));

        // Compound Team-or-GG selections require a higher screening threshold.
        return new MarketAnalysisResult(
            MarketType::TEAM_OR_GG,
            "{$name} or GG",
            $score,
            $quality,
            $this->status->resolve($score, $quality, 90),
            $positive,
            $contradictions,
            $contradictions[0] ?? 'The bet fails if the selected team does not win and both teams do not score.'
        );
    }
}
