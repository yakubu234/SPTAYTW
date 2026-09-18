<?php

namespace App\Services\Football;

use App\Models\{Competition, Fixture, FootballHistoryCoverage, Team};
use Carbon\CarbonImmutable;

final class HistoricalFixtureStore
{
    public function hasSeason(int $teamProviderId, int $season): bool
    {
        return FootballHistoryCoverage::query()
            ->where('team_provider_id', $teamProviderId)
            ->where('season', $season)
            ->exists();
    }

    public function markSeasonFetched(int $teamProviderId, int $season): void
    {
        FootballHistoryCoverage::updateOrCreate(
            ['team_provider_id' => $teamProviderId, 'season' => $season],
            ['fetched_at' => now()]
        );
    }

    public function store(array $rows): int
    {
        $stored = 0;

        foreach ($rows as $row) {
            if (!isset($row['fixture']['id'], $row['league']['id'], $row['teams']['home']['id'], $row['teams']['away']['id'])) {
                continue;
            }

            $league = $row['league'];
            $homeName = $row['teams']['home']['name'] ?? 'Unknown';
            $awayName = $row['teams']['away']['name'] ?? 'Unknown';

            $competition = Competition::updateOrCreate(
                ['provider_id' => $league['id']],
                [
                    'name' => $league['name'] ?? 'Unknown',
                    'country' => $league['country'] ?? null,
                    'type' => $league['type'] ?? null,
                    'high_variance' => $this->isHighVariance($league['name'] ?? '', $league['type'] ?? '', $homeName, $awayName),
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
            $stored++;
        }

        return $stored;
    }

    public function teamFixturesBefore(int $teamProviderId, CarbonImmutable $before, int $last): array
    {
        return Fixture::query()
            ->with(['homeTeam', 'awayTeam', 'competition'])
            ->where('status', 'FT')
            ->whereNotNull('home_goals')
            ->whereNotNull('away_goals')
            ->where('kickoff_at', '<', $before)
            ->where(function ($query) use ($teamProviderId) {
                $query->whereHas('homeTeam', fn ($q) => $q->where('provider_id', $teamProviderId))
                    ->orWhereHas('awayTeam', fn ($q) => $q->where('provider_id', $teamProviderId));
            })
            ->orderByDesc('kickoff_at')
            ->limit(max(1, $last))
            ->get()
            ->map(fn (Fixture $fixture) => [
                'fixture' => [
                    'id' => $fixture->provider_id,
                    'date' => $fixture->kickoff_at->toIso8601String(),
                    'status' => ['short' => $fixture->status],
                ],
                'league' => [
                    'id' => $fixture->competition->provider_id,
                    'name' => $fixture->competition->name,
                    'country' => $fixture->competition->country,
                    'type' => $fixture->competition->type,
                ],
                'teams' => [
                    'home' => ['id' => $fixture->homeTeam->provider_id, 'name' => $fixture->homeTeam->name],
                    'away' => ['id' => $fixture->awayTeam->provider_id, 'name' => $fixture->awayTeam->name],
                ],
                'goals' => [
                    'home' => $fixture->home_goals,
                    'away' => $fixture->away_goals,
                ],
            ])
            ->all();
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
