<?php

use PHPUnit\Framework\TestCase;

final class PersistentHistoryCacheContractTest extends TestCase
{
    public function test_historical_provider_persists_team_season_history_and_filters_cutoff_locally(): void
    {
        $provider = file_get_contents(__DIR__ . '/../../app/Services/Football/Providers/ApiFootballProvider.php');

        $this->assertStringContainsString('Cache::rememberForever', $provider);
        $this->assertStringContainsString('football:provider-history:v1:team:', $provider);
        $this->assertStringContainsString('cachedTeamSeasonFixtures($teamId, $season, $from)', $provider);
        $this->assertStringContainsString('CarbonImmutable::parse($fixtureDate)->gte($to)', $provider);
        $this->assertStringContainsString("'status' => 'FT'", $provider);
        $this->assertStringContainsString('array_slice($rows, 0, max(1, $last))', $provider);
    }
}
