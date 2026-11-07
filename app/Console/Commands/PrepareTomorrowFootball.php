<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class PrepareTomorrowFootball extends Command
{
    protected $signature = 'football:prepare-tomorrow {--date= : Override the target date (YYYY-MM-DD)} {--history-limit=100 : Event/statistics requests per market}';
    protected $description = 'Prepare tomorrow’s fixtures and both regular and early-goal analyses';

    public function handle(): int
    {
        $date = $this->option('date') ?: CarbonImmutable::now('Africa/Lagos')->addDay()->toDateString();
        if (!CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            $this->error('--date must be YYYY-MM-DD.');
            return self::FAILURE;
        }
        $limit = filter_var($this->option('history-limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 0) {
            $this->error('--history-limit must be a nonnegative integer.');
            return self::FAILURE;
        }

        foreach ([
            ['football:sync-fixtures', ['date' => $date]],
            ['football:sync-corners', ['date' => $date, '--limit' => $limit]],
            ['football:sync-early-goals', ['date' => $date, '--limit' => $limit]],
            ['football:analyse', ['date' => $date]],
            ['football:early-goals', ['date' => $date]],
        ] as [$command, $arguments]) {
            $this->info("Running {$command} for {$date}...");
            $code = Artisan::call($command, $arguments, $this->output);
            if ($code !== self::SUCCESS) {
                $this->error("{$command} failed; preparation stopped.");
                return self::FAILURE;
            }
        }
        $this->info("Prepared {$date}. History limits may leave unchecked matches; rerun the sync commands if needed.");
        return self::SUCCESS;
    }
}
