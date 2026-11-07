<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\Football\{EarlyGoalEvidenceBuilder, EarlyGoalEventService};
use Illuminate\Console\Command;
use Throwable;

final class SyncFootballEarlyGoals extends Command
{
    protected $signature = 'football:sync-early-goals {date?} {--limit=100 : Maximum event API requests} {--upcoming : Prepare only matches that have not kicked off} {--retry-missing : Recheck previously incomplete event timelines}';
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
        $fixtures = Fixture::with(['homeTeam', 'awayTeam'])->whereDate('kickoff_at', $date)
            ->whereNotIn('status', ['PST', 'CANC', 'ABD', 'AWD', 'WO'])
            ->when($this->option('upcoming'), fn ($q) => $q->where('kickoff_at', '>', now())->whereIn('status', ['NS', 'TBD']))
            ->orderBy('kickoff_at')->get();
        $this->info("Collecting recent history for {$fixtures->count()} fixtures on {$date}...");
        $discovery = $this->output->createProgressBar($fixtures->count());
        $discovery->start();
        try {
            foreach ($fixtures as $fixture) {
                $history = $builder->build($fixture)['history'];
                // Keep first-seen order: finish both teams' recent history for
                // the earliest kickoff before spending requests on later games.
                foreach (array_merge($history['home'], $history['away']) as $id) $ids[$id] = true;
                $discovery->advance();
            }
        } catch (Throwable $e) {
            $discovery->finish();
            $this->newLine();
            $this->error('Could not load recent fixture history: '.$e->getMessage());
            return self::FAILURE;
        }
        $discovery->finish();
        $this->newLine(2);
        $this->info('Recent finished matches found: '.count($ids).'. Fetching up to '.$limit.' unchecked event timelines...');

        $query = Fixture::whereIn('provider_id', array_keys($ids))->where('status', 'FT');
        if ($this->option('retry-missing')) {
            $query->where(fn ($q) => $q->whereNull('early_goal_events_checked_at')->orWhereNull('first_five_goal'));
        } else {
            $query->whereNull('early_goal_events_checked_at');
        }
        $byProviderId = $query->get()->keyBy('provider_id');
        $matches = collect(array_keys($ids))->map(fn ($id) => $byProviderId->get($id))->filter()->values();
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
