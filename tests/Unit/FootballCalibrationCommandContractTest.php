<?php

use App\Console\Commands\FootballCalibration;
use App\Services\Football\PerformanceCalibrationService;
use PHPUnit\Framework\TestCase;

final class FootballCalibrationCommandContractTest extends TestCase
{
    public function test_command_uses_performance_report_keys(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Console/Commands/FootballCalibration.php');

        $this->assertStringContainsString("\$r['score_band']", $source);
        $this->assertStringContainsString("\$r['samples']", $source);
        $this->assertStringContainsString("\$r['observed_rate']", $source);
        $this->assertStringContainsString("\$r['sample_status']", $source);
        $this->assertStringNotContainsString("\$r['band']", $source);
        $this->assertStringNotContainsString("\$r['graded']", $source);
        $this->assertStringNotContainsString("\$r['rate']", $source);
    }
}
