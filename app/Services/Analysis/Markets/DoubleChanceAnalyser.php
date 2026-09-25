<?php

namespace App\Services\Analysis\Markets;

use App\DTOs\Football\{MarketAnalysisResult, MatchEvidence};
use App\Enums\MarketType;
use App\Services\Analysis\MarketStatusResolver;

final class DoubleChanceAnalyser
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
        $relevant = $home ? $e->homeRelevantNonLossRate : $e->awayRelevantNonLossRate;
        $overall = $home ? $e->homeNonLossRate : $e->awayNonLossRate;
        $opponentRelevantWins = $home ? $e->awayRelevantWinRate : $e->homeRelevantWinRate;
        $splitCount = $home ? $e->homeRelevantSampleSize : $e->awayRelevantSampleSize;
        $positive = [
            sprintf('Selected team avoided defeat in %.0f%% of %d relevant home/away matches.', $relevant * 100, $splitCount),
            sprintf('Selected team avoided defeat in %.0f%% of its recent matches.', $overall * 100),
            sprintf('Opponent won %.0f%% of its relevant home/away matches.', $opponentRelevantWins * 100),
        ];
        $contradictions = [];
        $score = (int) round(20 + $relevant * 45 + $overall * 20 + (1 - $opponentRelevantWins) * 15);
        $quality = min(100, 45 + min($e->sampleSize, $splitCount) * 5);

        if ($splitCount < 4 || $e->sampleSize < 7) {
            $contradictions[] = 'Relevant home/away history or overall completed-match history is too limited.';
            $score -= 15;
            $quality = min($quality, 54);
        }
        if ($relevant < .70 || $overall < .70) {
            $contradictions[] = 'Selected team has lost too often in recent or relevant home/away matches.';
            $score -= 12;
        }
        if ($opponentRelevantWins > .40) {
            $contradictions[] = 'Opponent has a meaningful relevant home/away win rate.';
            $score -= 10;
        }
        if ($e->highVarianceCompetition) {
            $contradictions[] = 'Competition or teams are classified as high variance.';
            $score -= 12;
            $quality -= 20;
        }
        if ($e->rotationRisk) {
            $contradictions[] = 'Rotation risk weakens the historical evidence.';
            $score -= 8;
        }

        $score = max(0, min(100, $score));
        $quality = max(0, min(100, $quality));

        return new MarketAnalysisResult(
            $home ? MarketType::HOME_OR_DRAW : MarketType::AWAY_OR_DRAW,
            "{$name} Win or Draw",
            $score,
            $quality,
            $this->status->resolve($score, $quality),
            $positive,
            $contradictions,
            $contradictions[0] ?? 'The selection loses if the opposing team wins. This new market is not yet calibrated against graded results.'
        );
    }
}
