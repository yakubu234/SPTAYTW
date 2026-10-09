<?php

namespace App\Services\Football;

use App\Contracts\Football\FootballDataProvider;
use App\Models\Fixture;
use Carbon\CarbonImmutable;

final class CornerEvidenceBuilder
{
    public function __construct(private FootballDataProvider $provider) {}

    public function build(Fixture $fixture): array
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam']);
        $before = CarbonImmutable::parse($fixture->kickoff_at)->subDay()->toDateString();
        $ids = [];
        foreach (['home' => $fixture->homeTeam->provider_id, 'away' => $fixture->awayTeam->provider_id] as $side => $teamId) {
            $rows = $this->provider->teamFixturesBefore($teamId, $before, 20);
            $rows = array_values(array_filter($rows, fn ($r) => ($r['fixture']['status']['short'] ?? null) === 'FT'
                && ($r['fixture']['id'] ?? null) != $fixture->provider_id
                && !empty($r['fixture']['date'])
                && CarbonImmutable::parse($r['fixture']['date'])->lt($fixture->kickoff_at)));
            usort($rows, fn ($a, $b) => strcmp($b['fixture']['date'], $a['fixture']['date']));
            $ids[$side] = array_values(array_unique(array_map(fn ($r) => (int) $r['fixture']['id'], array_slice($rows, 0, 10))));
        }

        $history = Fixture::whereIn('provider_id', array_unique(array_merge($ids['home'], $ids['away'])))
            ->get()->keyBy('provider_id');
        $sample = [];
        $relevant = [];
        foreach ($ids as $side => $matchIds) {
            $sample[$side] = [];
            $relevant[$side] = [];
            foreach ($matchIds as $id) {
                $match = $history->get($id);
                if (!$match || $match->home_corners === null || $match->away_corners === null) continue;
                $total = (int) $match->home_corners + (int) $match->away_corners;
                $sample[$side][$id] = $total;
                $teamId = $side === 'home' ? $fixture->home_team_id : $fixture->away_team_id;
                if (($side === 'home' && $match->home_team_id === $teamId)
                    || ($side === 'away' && $match->away_team_id === $teamId)) {
                    $relevant[$side][$id] = $total;
                }
            }
        }

        return ['history' => $ids, 'sample' => $sample, 'relevant' => $relevant,
            'unique' => $sample['home'] + $sample['away']];
    }
}
