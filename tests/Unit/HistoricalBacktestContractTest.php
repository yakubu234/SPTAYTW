<?php

use PHPUnit\Framework\TestCase;

final class HistoricalBacktestContractTest extends TestCase
{
    public function test_provider_and_evidence_builders_use_date_bound_history(): void
    {
        $contract = file_get_contents(__DIR__ . '/../../app/Contracts/Football/FootballDataProvider.php');
        $provider = file_get_contents(__DIR__ . '/../../app/Services/Football/Providers/ApiFootballProvider.php');
        $store = file_get_contents(__DIR__ . '/../../app/Services/Football/HistoricalFixtureStore.php');
        $team = file_get_contents(__DIR__ . '/../../app/Services/Football/TeamGoalEvidenceBuilder.php');
        $match = file_get_contents(__DIR__ . '/../../app/Services/Football/MatchEvidenceBuilder.php');

        $this->assertStringContainsString('teamFixturesBefore', $contract);
        $this->assertStringContainsString('historyStats', $contract);
        $this->assertStringContainsString('historyStore->hasSeason', $provider);
        $this->assertStringContainsString("'season' => \$season", $provider);
        $this->assertStringContainsString("'status' => 'FT'", $provider);
        $this->assertStringContainsString("where('kickoff_at', '<', \$before)", $store);
        $this->assertStringContainsString('teamFixturesBefore', $team);
        $this->assertStringContainsString('teamFixturesBefore', $match);
    }

    public function test_historical_backtest_requires_completed_range_and_full_time_fixtures(): void
    {
        $command = file_get_contents(__DIR__ . '/../../app/Console/Commands/HistoricalFootballBacktest.php');

        $this->assertStringContainsString('football:historical-backtest', $command);
        $this->assertStringContainsString("\$to->gte(CarbonImmutable::today())", $command);
        $this->assertStringContainsString("where('status', 'FT')", $command);
        $this->assertStringContainsString("whereNotNull('home_goals')", $command);
        $this->assertStringContainsString("whereNotNull('away_goals')", $command);
        $this->assertStringContainsString("\$failed = true", $command);
        $this->assertStringContainsString('Local history hits', $command);
        $this->assertStringContainsString('Provider requests', $command);
        $this->assertStringContainsString('return $failed ? self::FAILURE : self::SUCCESS;', $command);
    }

    public function test_rolling_backtest_uses_fixture_kickoff_not_grading_timestamp(): void
    {
        $command = file_get_contents(__DIR__ . '/../../app/Console/Commands/BacktestFootballSelections.php');

        $this->assertStringContainsString("whereHas('fixture'", $command);
        $this->assertStringContainsString("'kickoff_at'", $command);
        $this->assertStringNotContainsString("where('graded_at'", $command);
    }
}
