<?php

namespace App\Console\Commands;

use App\Models\MarketAnalysis;
use Illuminate\Console\Command;

final class ShowFootballCorners extends Command
{
    protected $signature = 'football:corners {date?}';
    protected $description = 'Show both corner market assessments for every analysed fixture on a date';

    public function handle(): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $rows = MarketAnalysis::with(['fixture.homeTeam', 'fixture.awayTeam', 'fixture.competition'])
            ->whereIn('market_type', ['corners_over_4_5', 'corners_under_18'])
            ->whereHas('fixture', fn ($q) => $q->whereDate('kickoff_at', $date))
            ->get()->sortBy(fn ($a) => $a->fixture->kickoff_at->format('Y-m-d H:i').'|'.$a->market_type);

        $this->table(['Kickoff', 'Fixture', 'Competition', 'Selection', 'Score / Quality', 'Status', 'History'],
            $rows->map(fn ($a) => [
                $a->fixture->kickoff_at->format('Y-m-d H:i'),
                $a->fixture->homeTeam->name.' vs '.$a->fixture->awayTeam->name,
                $a->fixture->competition->name,
                $a->selection,
                $a->score.' / '.$a->data_quality_score,
                $a->status,
                implode(' ', array_slice($a->positive_signals ?? [], 0, 3)),
            ])->all());
        return self::SUCCESS;
    }
}
