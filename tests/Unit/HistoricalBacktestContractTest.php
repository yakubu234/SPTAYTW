<?php

use PHPUnit\Framework\TestCase;

final class HistoricalBacktestContractTest extends TestCase
{
    public function test_provider_and_evidence_builders_use_date_bound_history(): void
    {
        $contract = file_get_contents(__DIR__ . '/../../app/Contracts/Football/FootballDataProvider.php');
        $provider = file_get_contents(__DIR__ . '/../../app/Services/Football/Providers/ApiFootballProvider.php');
        $team = file_get_contents(__DIR__ . '/../../app/Services/Football/TeamGoalEvidenceBuilder.php');
        $match = file_get_contents(__DIR__ . '/../../app/Services/Football/MatchEvidenceBuilder.php');

        $this->assertStringContainsString('teamFixturesBefore', $contract);
        $this->assertStringContainsString("'to' => \$before", $provider);
        $this->assertStringContainsString("\$payload['errors']", $provider);
        $this->assertStringContainsString('array_slice($rows, 0, max(1, $last))', $provider);
        $this->assertStringNotContainsString("'to' => \$before,\n                'last' => \$last", $provider);
        $this->assertStringContainsString('teamFixturesBefore', $team);
        $this->assertStringContainsString('teamFixturesBefore', $match);
    }

    public function test_historical_backtest_requires_completed_range(): void
    {
        $command = file_get_contents(__DIR__ . '/../../app/Console/Commands/HistoricalFootballBacktest.php');

        $this->assertStringContainsString('football:historical-backtest', $command);
        $this->assertStringContainsString("\$to->gte(CarbonImmutable::today())", $command);
        $this->assertStringContainsString("whereNotNull('home_goals')", $command);
        $this->assertStringContainsString("whereNotNull('away_goals')", $command);
    }
}
