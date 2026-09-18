<?php

use PHPUnit\Framework\TestCase;

final class FixtureStatusScreeningContractTest extends TestCase
{
    public function test_daily_analysis_excludes_non_playable_fixture_statuses(): void
    {
        $command = file_get_contents(__DIR__ . '/../../app/Console/Commands/AnalyseFootballFixtures.php');

        $this->assertStringContainsString("['PST', 'CANC', 'ABD', 'AWD', 'WO']", $command);
        $this->assertStringContainsString('NON_PLAYABLE_STATUSES', $command);
        $this->assertStringContainsString('Non-playable excluded', $command);
        $this->assertStringContainsString('Fixtures analysed', $command);
    }
}
