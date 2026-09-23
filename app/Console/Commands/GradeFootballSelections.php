<?php

namespace App\Console\Commands;

use App\Models\MarketAnalysis;
use App\Services\Football\FixtureImporter;
use App\Services\Football\MarketGrader;
use Illuminate\Console\Command;
use Throwable;

class GradeFootballSelections extends Command
{
    protected $signature = 'football:grade
        {date? : Fixture date in YYYY-MM-DD format}
        {--no-sync : Grade only results already stored locally}';

    protected $description = 'Refresh fixture results and grade completed football market analyses';

    public function handle(FixtureImporter $importer, MarketGrader $grader): int
    {
        $date = $this->argument('date') ?: now()->toDateString();

        if (!$this->option('no-sync')) {
            try {
                $count = $importer->importDate($date);
                $this->info("Refreshed {$count} fixtures for {$date} before grading.");
            } catch (Throwable $e) {
                $this->error("Could not refresh fixture results for {$date}: {$e->getMessage()}");
                $this->warn('No selections were graded. Re-run with --no-sync only if you intentionally want to grade locally stored results.');
                return self::FAILURE;
            }
        }

        $rows = MarketAnalysis::with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->whereNull('result')
            ->whereHas('fixture', fn ($query) => $query
                ->whereDate('kickoff_at', $date)
                ->whereIn('status', ['FT', 'AET', 'PEN'])
                ->whereNotNull('home_goals')
                ->whereNotNull('away_goals'))
            ->get();

        $graded = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $analysis) {
            $result = $grader->grade($analysis);

            if ($result) {
                $analysis->update([
                    'result' => $result,
                    'graded_at' => now(),
                ]);
                $graded++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Graded {$graded} selections.");

        return self::SUCCESS;
    }
}
