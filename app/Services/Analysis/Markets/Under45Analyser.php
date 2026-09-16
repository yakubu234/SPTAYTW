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

        // A clean 10-match sample should support a strong screening score, but never a near-certain 96/100 by itself.
        $score = (int) round(88 - ($e->over45Rate * 50));
        if ($e->sampleSize >= 10 && $e->over45Rate === 0.0) $score += 4;
        if ($e->sampleSize < 7) $score -= 8;
        if ($e->sampleSize < 5) $score -= 10;

        $quality = min(100, 45 + $e->sampleSize * 5);
        if ($e->over45Rate <= .10) $positive[] = 'Five-or-more-goal matches are rare in the recent samples.';
        if ($e->over45Rate >= .20) { $contradictions[] = 'Recent 5+ goal frequency is too high for a conservative under.'; $score -= 10; }
        if ($e->homeTeamOver45Rate >= .30 || $e->awayTeamOver45Rate >= .30) { $contradictions[] = 'At least one team has repeated recent 5+ goal matches.'; $score -= 10; }
        if ($e->highVarianceCompetition) { $contradictions[] = 'Competition is classified as high variance.'; $score -= 10; $quality -= 20; }
        if ($e->rotationRisk) { $contradictions[] = 'Rotation can increase match volatility.'; $score -= 6; }

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
