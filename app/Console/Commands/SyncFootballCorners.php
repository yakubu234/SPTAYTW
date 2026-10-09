<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\Football\{CornerEvidenceBuilder, CornerStatisticsService};
use Illuminate\Console\Command;
use Throwable;

final class SyncFootballCorners extends Command
{
    protected $signature = 'football:sync-corners {date?} {--limit=100 : Maximum statistics API requests in this run} {--retry-missing : Recheck fixtures where the provider previously returned no complete corner totals}';
    protected $description = 'Cache corner statistics for recent completed matches before daily analysis';

    public function handle(CornerEvidenceBuilder $builder, CornerStatisticsService $stats): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 0) {
            $this->error('--limit must be a nonnegative integer.');
            return self::FAILURE;
        }
        $ids = [];
        try {
            foreach (Fixture::with(['homeTeam', 'awayTeam'])->whereDate('kickoff_at', $date)
                ->whereNotIn('status', ['PST', 'CANC', 'ABD', 'AWD', 'WO'])->get() as $fixture) {
                $evidence = $builder->build($fixture);
                foreach (array_merge($evidence['history']['home'], $evidence['history']['away']) as $id) $ids[$id] = true;
            }
        } catch (Throwable $e) {
            $this->error('Could not load recent fixture history: '.$e->getMessage());
            return self::FAILURE;
        }

        $query = Fixture::whereIn('provider_id', array_keys($ids));
        if ($this->option('retry-missing')) {
            $query->where(fn ($q) => $q->whereNull('corner_stats_checked_at')
                ->orWhereNull('home_corners')->orWhereNull('away_corners'));
        } else {
            $query->whereNull('corner_stats_checked_at');
        }
        $matches = $query->orderByDesc('kickoff_at')->get();
        $available = $matches->count();
        $fetched = $complete = $missing = 0;
        foreach ($matches->take($limit) as $match) {
            try {
                if ($this->option('retry-missing') && ($match->home_corners === null || $match->away_corners === null)) {
                    $match->corner_stats_checked_at = null;
                }
                $complete += (int) $stats->fetch($match);
                $missing += (int) ($match->home_corners === null || $match->away_corners === null);
                $fetched++;
            } catch (Throwable $e) {
                $this->error("Statistics request failed for fixture {$match->provider_id}: {$e->getMessage()}");
                $this->warn("Fetched {$fetched} matches; re-run to resume cached progress.");
                return self::FAILURE;
            }
        }
        $this->info("Corner history for {$date}: {$fetched} fetched, {$complete} complete, {$missing} unavailable; ".max(0, $available - $fetched).' unchecked matches remain.');
        return self::SUCCESS;
    }
}
