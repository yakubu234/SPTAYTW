<?php

namespace App\Console\Commands;

use App\Models\{Fixture, MarketAnalysis};
use App\Services\Football\{EarlyGoalAnalyser, EarlyGoalEvidenceBuilder};
use Illuminate\Console\Command;
use Throwable;

final class AnalyseFootballEarlyGoals extends Command
{
    protected $signature = 'football:early-goals {date?}';
    protected $description = 'Report first-five-minute goal history for every playable fixture separately from goal-total markets';

    public function handle(EarlyGoalEvidenceBuilder $builder, EarlyGoalAnalyser $analyser): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'competition'])->whereDate('kickoff_at', $date)
            ->whereNotIn('status', ['PST', 'CANC', 'ABD', 'AWD', 'WO'])->orderBy('kickoff_at')->get();
        $rows = [];
        try {
            foreach ($fixtures as $fixture) {
                $result = $analyser->analyse($builder->build($fixture));
                $analysis = MarketAnalysis::firstOrNew(['fixture_id' => $fixture->id,
                    'market_type' => $result->market->value, 'selection' => $result->selection]);
                $analysis->fill(['score' => $result->score, 'data_quality_score' => $result->dataQuality,
                    'status' => $result->status->value, 'positive_signals' => $result->positiveSignals,
                    'contradictions' => $result->contradictions, 'main_risk' => $result->mainRisk,
                    'analysed_at' => now()]);
                $analysis->save();
                $rows[] = [$fixture->kickoff_at->format('Y-m-d H:i'),
                    $fixture->homeTeam->name.' vs '.$fixture->awayTeam->name,
                    $fixture->competition->name, $result->status->value,
                    $result->score.'/'.$result->dataQuality,
                    implode(' | ', $result->positiveSignals)];
            }
        } catch (Throwable $e) {
            $this->error('Early-goal analysis failed: '.$e->getMessage());
            return self::FAILURE;
        }
        $this->table(['Kickoff (app timezone)', 'Fixture', 'Competition', 'Status', 'Score/Quality', 'Recent evidence'], $rows);
        $this->info("Analysed {$fixtures->count()} playable fixtures. WATCH is a research signal, not a guaranteed outcome.");
        return self::SUCCESS;
    }
}
