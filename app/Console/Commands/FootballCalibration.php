<?php

namespace App\Console\Commands;

use App\Services\Football\PerformanceCalibrationService;
use Illuminate\Console\Command;

final class FootballCalibration extends Command
{
    protected $signature = 'football:calibration {--days=90}';
    protected $description = 'Observed win rates by market and model-score band';

    public function handle(PerformanceCalibrationService $service): int
    {
        $rows = $service->report(max(1, (int) $this->option('days')));

        $this->table(
            ['Market', 'Band', 'Graded', 'Won', 'Observed %', 'Sample'],
            array_map(fn ($r) => [
                $r['market'],
                $r['score_band'],
                $r['samples'],
                $r['won'],
                $r['observed_rate'],
                $r['sample_status'],
            ], $rows)
        );

        return self::SUCCESS;
    }
}
