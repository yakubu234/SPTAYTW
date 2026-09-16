<?php

namespace App\Services\Analysis\Markets;

use App\DTOs\Football\{MarketAnalysisResult, TeamGoalEvidence};
use App\Enums\MarketType;
use App\Services\Analysis\{DataQualityService, MarketStatusResolver};

final class TeamOver05Analyser
{
    public function __construct(private DataQualityService $quality, private MarketStatusResolver $status) {}

    public function analyse(string $teamName, TeamGoalEvidence $evidence): MarketAnalysisResult
    {
        $dataQuality = $this->quality->score($evidence);
        $score = 20 + ($evidence->teamScoredRate * 22) + ($evidence->venueScoredRate * 18) + ($evidence->opponentConcededRate * 17) + ($evidence->opponentVenueConcededRate * 13) - ($evidence->opponentCleanSheetRate * 12) - ($evidence->teamFailedToScoreRate * 12) - ($evidence->majorAttackerMissing ? 7 : 0) - ($evidence->rotationRisk ? 6 : 0) - ($evidence->highVarianceCompetition ? 8 : 0);
        $score = (int) round(max(0, min(100, $score)));
        $positive = [
            sprintf('Evidence: %d completed matches per team available.', $evidence->sampleSize),
            sprintf('Evidence: team scored in %.0f%% of recent matches.', $evidence->teamScoredRate * 100),
            sprintf('Evidence: team scored in %.0f%% of relevant venue matches.', $evidence->venueScoredRate * 100),
            sprintf('Evidence: opponent conceded in %.0f%% of recent matches.', $evidence->opponentConcededRate * 100),
            sprintf('Evidence: opponent conceded in %.0f%% of relevant venue matches.', $evidence->opponentVenueConcededRate * 100),
            sprintf('Evidence: opponent clean-sheet rate %.0f%%; team failed-to-score rate %.0f%%.', $evidence->opponentCleanSheetRate * 100, $evidence->teamFailedToScoreRate * 100),
        ];
        $contradictions = [];
        if ($evidence->teamScoredRate >= .8) $positive[] = 'Team scored in at least 80% of the overall sample.';
        if ($evidence->venueScoredRate >= .8) $positive[] = 'Team scored in at least 80% of the relevant venue sample.';
        if ($evidence->opponentConcededRate >= .8) $positive[] = 'Opponent conceded in at least 80% of the overall sample.';
        if ($evidence->opponentVenueConcededRate >= .8) $positive[] = 'Opponent conceded in at least 80% of the relevant venue sample.';
        if ($evidence->opponentCleanSheetRate >= .4) $contradictions[] = 'Opponent has a material clean-sheet rate.';
        if ($evidence->teamFailedToScoreRate >= .3) $contradictions[] = 'Team has failed to score too often recently.';
        if ($evidence->majorAttackerMissing) $contradictions[] = 'Important attacking absence is flagged.';
        if ($evidence->rotationRisk) $contradictions[] = 'Rotation risk is flagged.';
        if ($evidence->highVarianceCompetition) $contradictions[] = 'Competition is classified as high variance.';
        return new MarketAnalysisResult(MarketType::TEAM_OVER_05, $teamName.' Over 0.5 Goals', $score, $dataQuality, $this->status->resolve($score, $dataQuality), $positive, $contradictions, $contradictions[0] ?? 'Normal football variance.');
    }
}
