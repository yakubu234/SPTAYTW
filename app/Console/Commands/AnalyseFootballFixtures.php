<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\Football\FixtureAnalysisService;
use Illuminate\Console\Command;

class AnalyseFootballFixtures extends Command
{
    protected $signature = 'football:analyse {date?} {--minimum=70}';
    protected $description = 'Analyse all supported markets for fixtures on a date';

    public function handle(FixtureAnalysisService $service): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $fixtures = Fixture::with(['homeTeam','awayTeam','competition'])->whereDate('kickoff_at', $date)->get();
        $counts = ['strong_qualified'=>0,'qualified'=>0,'watch'=>0,'skip'=>0];
        $analyses = 0;

        $this->info("Analysing {$fixtures->count()} fixtures for {$date}...");
        $bar = $this->output->createProgressBar($fixtures->count());
        $bar->start();
        foreach ($fixtures as $fixture) {
            foreach ($service->analyse($fixture) as $analysis) {
                $analyses++;
                $counts[$analysis->status] = ($counts[$analysis->status] ?? 0) + 1;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->table(['Metric','Count'], [
            ['Fixtures', $fixtures->count()],
            ['Market analyses', $analyses],
            ['Strong Qualified', $counts['strong_qualified']],
            ['Qualified', $counts['qualified']],
            ['Watch', $counts['watch']],
            ['Skip', $counts['skip']],
        ]);
        $this->info('Analysis complete. Dashboard shows the single best-ranked market per fixture.');
        return self::SUCCESS;
    }
}
