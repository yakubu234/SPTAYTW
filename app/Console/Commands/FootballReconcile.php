<?php

namespace App\Console\Commands;

use App\Models\MarketAnalysis;
use Illuminate\Console\Command;

final class FootballReconcile extends Command
{
    protected $signature = 'football:reconcile
        {date : Fixture date in YYYY-MM-DD format}
        {--minimum=0 : Minimum model score to include}
        {--market= : Only include this market type}
        {--failures : Show only losing selections in the detail table}';

    protected $description = 'Reconcile graded football selections for an exact fixture date';

    public function handle(): int
    {
        $date = (string) $this->argument('date');

        if (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) || ! checkdate(
            (int) substr($date, 5, 2),
            (int) substr($date, 8, 2),
            (int) substr($date, 0, 4)
        )) {
            $this->error('The date must be a valid YYYY-MM-DD date.');

            return self::FAILURE;
        }

        $minimum = max(0, min(100, (int) $this->option('minimum')));
        $market = trim((string) ($this->option('market') ?? ''));

        $query = MarketAnalysis::query()
            ->with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->whereNotNull('result')
            ->where('score', '>=', $minimum)
            ->whereHas('fixture', fn ($fixture) => $fixture->whereDate('kickoff_at', $date));

        if ($market !== '') {
            $query->where('market_type', $market);
        }

        $analyses = $query
            ->orderByDesc('score')
            ->orderBy('market_type')
            ->get();

        if ($analyses->isEmpty()) {
            $this->warn("No graded selections found for {$date} with the requested filters.");

            return self::SUCCESS;
        }

        $summary = $analyses
            ->groupBy('market_type')
            ->map(function ($items, $marketType) {
                $picks = $items->count();
                $won = $items->where('result', 'won')->count();
                $lost = $items->where('result', 'lost')->count();

                return [
                    $marketType,
                    $picks,
                    $won,
                    $lost,
                    round(($won / max(1, $picks)) * 100, 1) . '%',
                ];
            })
            ->values()
            ->all();

        $this->info("Reconciliation for {$date}");
        $this->table(['Market', 'Picks', 'Won', 'Lost', 'Observed win rate'], $summary);

        $details = $this->option('failures')
            ? $analyses->where('result', 'lost')
            : $analyses;

        if ($details->isEmpty()) {
            $this->info($this->option('failures') ? 'No losing selections matched the filters.' : 'No selections matched the filters.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info($this->option('failures') ? 'Failures' : 'Selections');

        $this->table(
            ['Fixture', 'Market', 'Selection', 'Score', 'DQ', 'Result', 'Outcome'],
            $details->map(function (MarketAnalysis $analysis) {
                $fixture = $analysis->fixture;
                $home = $fixture?->homeTeam?->name ?? 'Unknown';
                $away = $fixture?->awayTeam?->name ?? 'Unknown';
                $score = ($fixture?->home_goals !== null && $fixture?->away_goals !== null)
                    ? $fixture->home_goals . '-' . $fixture->away_goals
                    : '-';

                return [
                    "{$home} vs {$away}",
                    $analysis->market_type,
                    $analysis->selection,
                    $analysis->score,
                    $analysis->data_quality_score,
                    $score,
                    strtoupper((string) $analysis->result),
                ];
            })->values()->all()
        );

        return self::SUCCESS;
    }
}
