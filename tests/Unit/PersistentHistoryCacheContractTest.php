<?php

use PHPUnit\Framework\TestCase;

final class PersistentHistoryCacheContractTest extends TestCase
{
    public function test_historical_provider_persists_team_season_history_and_filters_cutoff_locally(): void
    {
        $provider = file_get_contents(__DIR__ . '/../../app/Services/Football/Providers/ApiFootballProvider.php');
        $store = file_get_contents(__DIR__ . '/../../app/Services/Football/HistoricalFixtureStore.php');
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/2026_09_18_000005_create_football_history_coverages_table.php');

        $this->assertStringContainsString('historyStore->hasSeason', $provider);
        $this->assertStringContainsString('historyStore->store', $provider);
        $this->assertStringContainsString('historyStore->markSeasonFetched', $provider);
        $this->assertStringContainsString('historyStore->teamFixturesBefore', $provider);
        $this->assertStringContainsString("'status' => 'FT'", $provider);
        $this->assertStringNotContainsString('Cache::rememberForever', $provider);

        $this->assertStringContainsString("where('kickoff_at', '<', \$before)", $store);
        $this->assertStringContainsString("where('status', 'FT')", $store);
        $this->assertStringContainsString("unique(['team_provider_id', 'season'])", $migration);
    }
}
