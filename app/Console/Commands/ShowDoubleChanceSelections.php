<?php

namespace App\Console\Commands;

use App\Models\MarketAnalysis;
use Illuminate\Console\Command;

final class ShowDoubleChanceSelections extends Command
{
    protected $signature = 'football:double-chance {date? : YYYY-MM-DD} {--minimum=0 : Minimum screening score}';
    protected $description = 'Show both win-or-draw analyses for every fixture, including risks and low-quality rows';

    public function handle(): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $rows = MarketAnalysis::with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->whereHas('fixture', fn ($query) => $query->whereDate('kickoff_at', $date))
            ->whereIn('market_type', ['home_or_draw', 'away_or_draw'])
            ->where('score', '>=', (int) $this->option('minimum'))
            ->get()
            ->sortBy(fn ($a) => $a->fixture->kickoff_at->format('Y-m-d H:i:s') . '|' . $a->fixture_id . '|' . $a->market_type);

        $this->table(['Kickoff', 'Fixture', 'Market', 'Selection', 'Score', 'DQ', 'Status', 'Main risk'],
            $rows->map(fn ($a) => [
                $a->fixture->kickoff_at->format('Y-m-d H:i'),
                $a->fixture->homeTeam->name . ' vs ' . $a->fixture->awayTeam->name,
                $a->market_type,
                $a->selection,
                $a->score,
                $a->data_quality_score,
                $a->status,
                $a->main_risk,
            ])->all());
        $this->info("{$rows->count()} win-or-draw analyses for {$date}. Scores are not probabilities; check fixture identity, availability and price before use.");
        return self::SUCCESS;
    }
}
