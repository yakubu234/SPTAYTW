<?php

namespace App\Services\Football;

use App\Models\{Fixture, MarketAnalysis};
use App\Services\Analysis\Markets\{TeamOver05Analyser, Under45Analyser, MatchWinnerAnalyser, TeamOrGGAnalyser, DoubleChanceAnalyser};

final class FixtureAnalysisService
{
    public function __construct(
        private TeamGoalEvidenceBuilder $teamEvidence,
        private MatchEvidenceBuilder $matchEvidence,
        private TeamOver05Analyser $team05,
        private Under45Analyser $under45,
        private MatchWinnerAnalyser $winner,
        private TeamOrGGAnalyser $teamOrGg,
        private DoubleChanceAnalyser $doubleChance,
    ) {}

    public function analyse(Fixture $fixture): array
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam']);
        $results = [];

        foreach ([[true, $fixture->homeTeam->name], [false, $fixture->awayTeam->name]] as [$home, $name]) {
            $results[] = $this->team05->analyse($name, $this->teamEvidence->build($fixture, $home));
        }

        $matchEvidence = $this->matchEvidence->build($fixture);
        $results[] = $this->under45->analyse($matchEvidence);
        array_push($results, ...$this->winner->analyse($fixture->homeTeam->name, $fixture->awayTeam->name, $matchEvidence));
        array_push($results, ...$this->doubleChance->analyse($fixture->homeTeam->name, $fixture->awayTeam->name, $matchEvidence));
        array_push($results, ...$this->teamOrGg->analyse($fixture->homeTeam->name, $fixture->awayTeam->name, $matchEvidence));

        return array_map(function ($result) use ($fixture) {
            $analysis = MarketAnalysis::firstOrNew([
                'fixture_id' => $fixture->id,
                'market_type' => $result->market->value,
                'selection' => $result->selection,
            ]);

            $analysis->fill([
                'score' => $result->score,
                'data_quality_score' => $result->dataQuality,
                'status' => $result->status->value,
                'positive_signals' => $result->positiveSignals,
                'contradictions' => $result->contradictions,
                'main_risk' => $result->mainRisk,
                'analysed_at' => now(),
            ]);

            // Grading is historical evidence. Re-running analysis must never erase it.
            $analysis->save();

            return $analysis;
        }, $results);
    }
}
