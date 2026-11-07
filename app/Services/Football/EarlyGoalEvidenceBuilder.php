<?php

namespace App\Services\Football;

use App\Contracts\Football\FootballDataProvider;
use App\Models\Fixture;
use Carbon\CarbonImmutable;

final class EarlyGoalEvidenceBuilder
{
    public function __construct(private FootballDataProvider $provider) {}

    public function build(Fixture $fixture): array
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam']);
        $before = CarbonImmutable::parse($fixture->kickoff_at)->subDay()->toDateString();
        $ids = [];
        foreach (['home' => $fixture->homeTeam->provider_id, 'away' => $fixture->awayTeam->provider_id] as $side => $teamId) {
            $rows = $this->provider->teamFixturesBefore($teamId, $before, 20);
            $rows = array_values(array_filter($rows, fn ($row) => ($row['fixture']['status']['short'] ?? null) === 'FT'
                && ($row['fixture']['id'] ?? null) != $fixture->provider_id
                && !empty($row['fixture']['date'])
                && CarbonImmutable::parse($row['fixture']['date'])->lt($fixture->kickoff_at)));
            usort($rows, fn ($a, $b) => strcmp($b['fixture']['date'], $a['fixture']['date']));
            $ids[$side] = array_values(array_unique(array_map(fn ($row) => (int) $row['fixture']['id'], array_slice($rows, 0, 10))));
        }

        $matches = Fixture::whereIn('provider_id', array_unique(array_merge($ids['home'], $ids['away'])))
            ->get()->keyBy('provider_id');
        $sample = $venue = [];
        foreach ($ids as $side => $matchIds) {
            $sample[$side] = $venue[$side] = [];
            foreach ($matchIds as $id) {
                $match = $matches->get($id);
                if (!$match || $match->first_five_goal === null) continue;
                $sample[$side][$id] = (bool) $match->first_five_goal;
                $teamId = $side === 'home' ? $fixture->home_team_id : $fixture->away_team_id;
                if (($side === 'home' && $match->home_team_id === $teamId)
                    || ($side === 'away' && $match->away_team_id === $teamId)) {
                    $venue[$side][$id] = (bool) $match->first_five_goal;
                }
            }
        }

        return ['history' => $ids, 'sample' => $sample, 'venue' => $venue,
            'unique' => $sample['home'] + $sample['away']];
    }
}
