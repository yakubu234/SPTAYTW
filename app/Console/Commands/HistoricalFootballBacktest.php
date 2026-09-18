<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\Football\FixtureAnalysisService;
use App\Services\Football\FixtureImporter;
use App\Services\Football\MarketGrader;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

final class HistoricalFootballBacktest extends Command
{
    protected $signature = 'football:historical-backtest {--from=} {--to=} {--minimum=80} {--quality=70}';
    protected $description = 'Import, analyse and grade a historical date range using only pre-fixture evidence';

    public function handle(FixtureImporter $importer, FixtureAnalysisService $analysis, MarketGrader $grader): int
    {
        if (!$this->option('from') || !$this->option('to')) {
            $this->error('Both --from and --to are required.');
            return self::FAILURE;
        }

        try {
            $from = CarbonImmutable::parse((string) $this->option('from'))->startOfDay();
            $to = CarbonImmutable::parse((string) $this->option('to'))->startOfDay();
        } catch (Throwable) {
            $this->error('Invalid --from or --to date. Use YYYY-MM-DD.');
            return self::FAILURE;
        }

        if ($from->gt($to) || $to->gte(CarbonImmutable::today())) {
            $this->error('Use a completed historical range where --from <= --to and --to is before today.');
            return self::FAILURE;
        }

        $minimum = (int) $this->option('minimum');
        $quality = (int) $this->option('quality');
        $summary = [];
        $failed = false;

        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $day = $date->toDateString();
            $this->info('Backtesting ' . $day . '...');

            try {
                $imported = $importer->importDate($day);
                $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'competition'])
                    ->whereDate('kickoff_at', $day)
                    ->where('status', 'FT')
                    ->whereNotNull('home_goals')
                    ->whereNotNull('away_goals')
                    ->get();

                $graded = 0;
                foreach ($fixtures as $fixture) {
                    foreach ($analysis->analyse($fixture) as $marketAnalysis) {
                        $result = $grader->grade($marketAnalysis);
                        if ($result === null) continue;
                        $marketAnalysis->result = $result;
                        $marketAnalysis->graded_at = now();
                        $marketAnalysis->save();
                        $graded++;
                    }
                }

                $summary[] = [$day, $imported, $fixtures->count(), $graded, 'OK'];
            } catch (Throwable $e) {
                $failed = true;
                $summary[] = [$day, 0, 0, 0, 'FAILED'];
                $this->error($day . ': ' . $e->getMessage());
                break;
            }
        }

        $this->table(['Date', 'Imported', 'Completed fixtures', 'Graded analyses', 'Status'], $summary);

        $rows = \App\Models\MarketAnalysis::query()
            ->whereHas('fixture', fn ($q) => $q->whereBetween('kickoff_at', [$from, $to->endOfDay()]))
            ->whereNotNull('result')
            ->where('score', '>=', $minimum)
            ->where('data_quality_score', '>=', $quality)
            ->get()
            ->groupBy(fn ($a) => $a->market_type . '|' . (int) (floor($a->score / 5) * 5))
            ->map(function ($items, $key) {
                [$market, $floor] = explode('|', $key);
                $n = $items->count();
                $won = $items->where('result', 'won')->count();
                return [$market, $floor . '-' . min(100, (int) $floor + 4), $n, $won, $items->where('result', 'lost')->count(), $n ? round($won / $n * 100, 1) : 0, $n >= 50 ? 'usable' : 'insufficient'];
            })
            ->sortBy(fn ($row) => $row[0] . '|' . str_pad((string) (100 - (int) explode('-', $row[1])[0]), 3, '0', STR_PAD_LEFT))
            ->values()
            ->all();

        $this->table(['Market', 'Score band', 'N', 'Won', 'Lost', 'Observed %', 'Sample'], $rows);
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
