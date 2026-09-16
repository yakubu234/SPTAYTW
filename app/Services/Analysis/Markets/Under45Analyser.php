<?php

namespace App\Services\Analysis\Markets;

use App\DTOs\Football\{MarketAnalysisResult, MatchEvidence};
use App\Enums\MarketType;
use App\Services\Analysis\MarketStatusResolver;

final class Under45Analyser
{
    public function __construct(private MarketStatusResolver $status) {}

    public function analyse(MatchEvidence $e): MarketAnalysisResult
    {
        $positive = [
            sprintf('Evidence: %d completed matches per team available.', $e->sampleSize),
            sprintf('Evidence: average total goals %.2f.', $e->averageTotalGoals),
            sprintf('Evidence: combined Over 2.5 rate %.0f%%.', $e->over25Rate * 100),
            sprintf('Evidence: combined Over 3.5 rate %.0f%%.', $e->over35Rate * 100),
            sprintf('Evidence: combined 5+ goal rate %.0f%%.', $e->over45Rate * 100),
            sprintf('Evidence: home relevant Over 3.5 rate %.0f%%; average goals %.2f.', $e->homeRelevantOver35Rate * 100, $e->homeRelevantAverageGoals),
            sprintf('Evidence: away relevant Over 3.5 rate %.0f%%; average goals %.2f.', $e->awayRelevantOver35Rate * 100, $e->awayRelevantAverageGoals),
        ];
        $contradictions = [];

        // Start conservatively and reward several independent low-scoring indicators.
        // A zero 5+ rate alone is no longer enough to produce a Strong score.
        $score = 62;
        $score += (int) round((1 - min(1, $e->over45Rate)) * 10);
        $score += (int) round((1 - min(1, $e->over35Rate)) * 8);
        $score += (int) round((1 - min(1, $e->over25Rate)) * 5);

        if ($e->averageTotalGoals > 0 && $e->averageTotalGoals <= 2.4) $score += 6;
        elseif ($e->averageTotalGoals >= 3.2) { $score -= 8; $contradictions[] = 'Recent matches have a high average total-goal profile.'; }

        if ($e->homeRelevantOver35Rate <= .20 && $e->awayRelevantOver35Rate <= .20
            && $e->homeRelevantAverageGoals > 0 && $e->awayRelevantAverageGoals > 0
            && $e->homeRelevantAverageGoals <= 2.7 && $e->awayRelevantAverageGoals <= 2.7) {
            $score += 5;
            $positive[] = 'Relevant home and away samples both support a lower-volatility goal profile.';
        }

        if ($e->over45Rate >= .15) { $score -= 10; $contradictions[] = 'Recent 5+ goal frequency is too high for a conservative under.'; }
        if ($e->over35Rate >= .35) { $score -= 8; $contradictions[] = 'Recent 4+ goal matches are too frequent.'; }
        if ($e->homeRelevantOver35Rate >= .40 || $e->awayRelevantOver35Rate >= .40) { $score -= 8; $contradictions[] = 'At least one venue-relevant sample has frequent 4+ goal matches.'; }

        if ($e->sampleSize < 7) { $score -= 10; $contradictions[] = 'The completed-match sample is limited.'; }
        if ($e->sampleSize < 5) $score -= 10;

        $quality = min(100, 45 + $e->sampleSize * 5);
        if ($e->homeRelevantAverageGoals <= 0 || $e->awayRelevantAverageGoals <= 0) {
            $quality -= 10;
            $contradictions[] = 'Venue-relevant history is missing or insufficient.';
        }
        if ($e->highVarianceCompetition) {
            $score -= 15;
            $quality -= 25;
            $contradictions[] = 'Competition or teams are classified as high variance.';
        }
        if ($e->rotationRisk) {
            $score -= 6;
            $contradictions[] = 'Rotation can increase match volatility.';
        }

        $score = max(0, min(100, $score));
        $quality = max(0, min(100, $quality));

        return new MarketAnalysisResult(
            MarketType::UNDER_45,
            'Under 4.5 Match Goals',
            $score,
            $quality,
            $this->status->resolve($score, $quality),
            $positive,
            $contradictions,
            $contradictions[0] ?? 'A red card, mismatch or defensive collapse can still create a 5+ goal match.'
        );
    }
}
