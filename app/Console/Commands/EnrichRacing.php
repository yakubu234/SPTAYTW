<?php

namespace App\Console\Commands;

use App\Models\RacingRace;
use App\Services\Racing\RacingEnrichmentService;
use Illuminate\Console\Command;
use Throwable;

class EnrichRacing extends Command
{
    protected $signature = 'racing:enrich {date? : YYYY-MM-DD} {--limit= : Maximum runners to enrich}';
    protected $description = 'Fetch Basic-plan horse history and distance evidence for imported racecards';

    public function handle(RacingEnrichmentService $service): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        $races = RacingRace::with('runners')
            ->whereDate('off_time', $date)
            ->orderBy('off_time')
            ->get();

        $runners = $races->flatMap->runners
            ->where('non_runner', false)
            ->values();

        if ($limit !== null) {
            $runners = $runners->take($limit);
        }

        $ok = 0; $failed = 0;
        $bar = $this->output->createProgressBar($runners->count());
        $bar->start();

        foreach ($runners as $runner) {
            try {
                $summary = $service->enrichRunner($runner, $date);
                cache()->put(
                    "racing:evidence:{$runner->race_id}:{$runner->id}",
                    $summary,
                    now()->addHours((int) config('racing.enrichment.cache_hours', 12))
                );
                $ok++;
            } catch (Throwable $e) {
                $failed++;
                report($e);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Enriched {$ok} runners for {$date}; {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
