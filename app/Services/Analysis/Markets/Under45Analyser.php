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
            sprintf('Evidence: combined recent 5+ goal rate %.0f%%.', $e->over45Rate * 100),
            sprintf('Evidence: home-team recent 5+ goal rate %.0f%%.', $e->homeTeamOver45Rate * 100),
            sprintf('Evidence: away-team recent 5+ goal rate %.0f%%.', $e->awayTeamOver45Rate * 100),
            sprintf('Evidence: combined recent Over 1.5 rate %.0f%%.', $e->over15Rate * 100),
        ];
        $contradictions = [];

        // Use the two team-specific 5+ goal rates as well as the combined rate.
        // This prevents every clean 10-match sample from collapsing to the same 92.
        $weightedFivePlus = ($e->over45Rate * .50)
            + ($e->homeTeamOver45Rate * .25)
            + ($e->awayTeamOver45Rate * .25);

        $score = (int) round(90 - ($weightedFivePlus * 65));

        if ($e->sampleSize >= 10 && $weightedFivePlus <= .05) {
            $score += 2;
            $positive[] = 'The recent samples contain very little 5+ goal evidence.';
        }
        if ($e->sampleSize < 7) $score -= 8;
        if ($e->sampleSize < 5) $score -= 10;

        $quality = min(100, 45 + $e->sampleSize * 5);

        if ($weightedFivePlus >= .15) {
            $contradictions[] = 'The weighted recent 5+ goal frequency is too high for a conservative under.';
            $score -= 10;
        }
        if ($e->homeTeamOver45Rate >= .30 || $e->awayTeamOver45Rate >= .30) {
            $contradictions[] = 'At least one team has repeated recent 5+ goal matches.';
            $score -= 10;
        }
        if ($e->highVarianceCompetition) {
            $contradictions[] = 'Competition or teams are classified as high variance.';
            $score -= 15;
            $quality -= 25;
        }
        if ($e->rotationRisk) {
            $contradictions[] = 'Rotation can increase match volatility.';
            $score -= 6;
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
            $contradictions[0] ?? 'An early red card or defensive collapse can still create a 5+ goal match.'
        );
    }
}
