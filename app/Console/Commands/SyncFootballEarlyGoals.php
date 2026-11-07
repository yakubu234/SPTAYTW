<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\Football\{EarlyGoalEvidenceBuilder, EarlyGoalEventService};
use Illuminate\Console\Command;
use Throwable;

final class SyncFootballEarlyGoals extends Command
{
    protected $signature = 'football:sync-early-goals {date?} {--limit=100 : Maximum event API requests} {--retry-missing : Recheck previously incomplete event timelines}';
    protected $description = 'Cache first-five-minute goal evidence from recent completed fixtures';

    public function handle(EarlyGoalEvidenceBuilder $builder, EarlyGoalEventService $events): int
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
                $history = $builder->build($fixture)['history'];
                foreach (array_merge($history['home'], $history['away']) as $id) $ids[$id] = true;
            }
        } catch (Throwable $e) {
            $this->error('Could not load recent fixture history: '.$e->getMessage());
            return self::FAILURE;
        }

        $query = Fixture::whereIn('provider_id', array_keys($ids))->where('status', 'FT');
        if ($this->option('retry-missing')) {
            $query->where(fn ($q) => $q->whereNull('early_goal_events_checked_at')->orWhereNull('first_five_goal'));
        } else {
            $query->whereNull('early_goal_events_checked_at');
        }
        $matches = $query->orderByDesc('kickoff_at')->get();
        $checked = $complete = 0;
        $bar = $this->output->createProgressBar(min($limit, $matches->count()));
        $bar->start();
        foreach ($matches->take($limit) as $match) {
            try {
                if ($this->option('retry-missing') && $match->first_five_goal === null) {
                    $match->early_goal_events_checked_at = null;
                }
                $events->fetch($match);
                $complete += (int) ($match->first_five_goal !== null);
                $checked++;
                $bar->advance();
            } catch (Throwable $e) {
                $bar->finish();
                $this->newLine();
                $this->error("Event request failed for fixture {$match->provider_id}: {$e->getMessage()}");
                $this->warn("Checked {$checked} matches; re-run to resume cached progress.");
                return self::FAILURE;
            }
        }
        $bar->finish();
        $this->newLine(2);
        $this->info("Early-goal history for {$date}: {$checked} checked, {$complete} complete, ".($checked - $complete).' incomplete; '.max(0, $matches->count() - $checked).' unchecked remain.');
        return self::SUCCESS;
    }
}
