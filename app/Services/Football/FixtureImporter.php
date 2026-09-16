<?php

namespace App\Services\Football;

use App\Contracts\Football\FootballDataProvider;
use App\Models\{Competition, Fixture, Team};
use Carbon\CarbonImmutable;

final class FixtureImporter
{
    public function __construct(private FootballDataProvider $provider) {}

    public function importDate(string $date): int
    {
        $rows = $this->provider->fixtures($date);
        $count = 0;

        foreach ($rows as $row) {
            if (!isset($row['fixture']['id'], $row['league']['id'], $row['teams']['home']['id'], $row['teams']['away']['id'])) {
                continue;
            }

            $league = $row['league'];
            $homeName = $row['teams']['home']['name'] ?? 'Unknown';
            $awayName = $row['teams']['away']['name'] ?? 'Unknown';

            // Some providers use a generic competition name (for example "Asian Games")
            // while the age group is only present in the team names. Include both team
            // names so U17-U23/youth/reserve fixtures cannot escape the variance guard.
            $competition = Competition::updateOrCreate(
                ['provider_id' => $league['id']],
                [
                    'name' => $league['name'] ?? 'Unknown',
                    'country' => $league['country'] ?? null,
                    'type' => $league['type'] ?? null,
                    'high_variance' => $this->isHighVariance(
                        $league['name'] ?? '',
                        $league['type'] ?? '',
                        $homeName,
                        $awayName,
                    ),
                ]
            );

            $home = Team::updateOrCreate(
                ['provider_id' => $row['teams']['home']['id']],
                ['name' => $homeName, 'country' => $league['country'] ?? null]
            );
            $away = Team::updateOrCreate(
                ['provider_id' => $row['teams']['away']['id']],
                ['name' => $awayName, 'country' => $league['country'] ?? null]
            );

            Fixture::updateOrCreate(
                ['provider_id' => $row['fixture']['id']],
                [
                    'competition_id' => $competition->id,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'kickoff_at' => CarbonImmutable::parse($row['fixture']['date']),
                    'status' => $row['fixture']['status']['short'] ?? null,
                    'home_goals' => $row['goals']['home'] ?? null,
                    'away_goals' => $row['goals']['away'] ?? null,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function isHighVariance(string ...$parts): bool
    {
        $value = strtolower(implode(' ', $parts));

        foreach (['u17', 'u18', 'u19', 'u20', 'u21', 'u22', 'u23', 'under 17', 'under 18', 'under 19', 'under 20', 'under 21', 'under 22', 'under 23', 'youth', 'reserve', 'reserves', 'friendly', 'friendlies'] as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }
}
