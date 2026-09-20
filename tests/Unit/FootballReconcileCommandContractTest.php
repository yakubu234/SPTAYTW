<?php

use PHPUnit\Framework\TestCase;

final class FootballReconcileCommandContractTest extends TestCase
{
    public function test_reconcile_command_has_required_daily_forensic_filters(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Console/Commands/FootballReconcile.php');

        $this->assertStringContainsString('football:reconcile', $source);
        $this->assertStringContainsString('{date : Fixture date in YYYY-MM-DD format}', $source);
        $this->assertStringContainsString('{--minimum=0', $source);
        $this->assertStringContainsString('{--market=', $source);
        $this->assertStringContainsString('{--failures', $source);
        $this->assertStringContainsString("whereDate('kickoff_at', \$date)", $source);
        $this->assertStringContainsString("whereNotNull('result')", $source);
        $this->assertStringContainsString("where('score', '>=', \$minimum)", $source);
        $this->assertStringContainsString("'data_quality_score'", file_get_contents(__DIR__ . '/../../app/Models/MarketAnalysis.php'));
    }

    public function test_reconcile_reports_summary_and_fixture_level_failure_context(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Console/Commands/FootballReconcile.php');

        $this->assertStringContainsString("['Market', 'Picks', 'Won', 'Lost', 'Observed win rate']", $source);
        $this->assertStringContainsString("['Fixture', 'Market', 'Selection', 'Score', 'DQ', 'Result', 'Outcome']", $source);
        $this->assertStringContainsString("where('result', 'lost')", $source);
        $this->assertStringContainsString("fixture.homeTeam", $source);
        $this->assertStringContainsString("fixture.awayTeam", $source);
    }
}
