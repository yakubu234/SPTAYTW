<?php

namespace App\Services\Analysis\Markets;

use App\DTOs\Football\{MarketAnalysisResult, MatchEvidence};
use App\Enums\MarketType;
use App\Enums\AnalysisStatus;
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
        $status = $this->status->resolve($score, $quality);
        // Sparse venue history is an evidence gap, not evidence that the
        // selected side will lose. Surface strong overall records separately
        // for human review without upgrading the model's qualification.
        if ($splitCount < 6 && $e->sampleSize >= 8 && $overall >= .80
            && $relevant >= .70 && $opponentRelevantWins <= .30
            && !$e->highVarianceCompetition) {
            $positive[] = 'Manual review candidate: strong overall non-loss record, but too few venue matches to qualify.';
        }

        // A short venue split can look perfect by chance. Do not promote a
        // new double-chance market on score alone, especially with a rival
        // that frequently wins its corresponding away/home fixtures.
        $reliableProfile = $relevant >= .80 && $overall >= .80
            && $opponentRelevantWins <= .30 && !$e->highVarianceCompetition && !$e->rotationRisk;
        if (!$reliableProfile || $splitCount < 6 || $e->sampleSize < 8 || $quality < 75) {
            if ($status === AnalysisStatus::STRONG || $status === AnalysisStatus::QUALIFIED) {
                $status = AnalysisStatus::WATCH;
            }
            $contradictions[] = 'Win-or-draw qualification needs at least 8 recent matches, 6 relevant venue matches, 80% non-loss rates, low opponent win rate and DQ 75.';
        } elseif ($status === AnalysisStatus::STRONG && ($splitCount < 7 || $quality < 80)) {
            $status = AnalysisStatus::QUALIFIED;
            $contradictions[] = 'Strong qualification needs at least 7 relevant venue matches and DQ 80.';
        }

        $risk = $contradictions[0] ?? 'The selection loses if the opposing team wins.';
        $risk .= ' New market: observed grading/calibration is still required.';

        return new MarketAnalysisResult(
            $home ? MarketType::HOME_OR_DRAW : MarketType::AWAY_OR_DRAW,
            "{$name} Win or Draw",
            $score,
            $quality,
            $status,
            $positive,
            $contradictions,
            $risk
        );
    }
}
