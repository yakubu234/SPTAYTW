<?php

namespace App\Services\Football;

use App\Contracts\Football\FootballDataProvider;
use App\DTOs\Football\TeamGoalEvidence;
use App\Models\Fixture;
use Carbon\CarbonImmutable;

final class TeamGoalEvidenceBuilder
{
    public function __construct(private FootballDataProvider $provider) {}

    public function build(Fixture $fixture, bool $forHomeTeam, int $sample = 10): TeamGoalEvidence
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam', 'competition']);
        $team = $forHomeTeam ? $fixture->homeTeam : $fixture->awayTeam;
        $opp = $forHomeTeam ? $fixture->awayTeam : $fixture->homeTeam;
        $fetch = max(20, $sample * 2);
        $before = CarbonImmutable::parse($fixture->kickoff_at)->subDay()->toDateString();

        $teamRows = $this->completedBefore($this->provider->teamFixturesBefore($team->provider_id, $before, $fetch), $fixture, $sample);
        $oppRows = $this->completedBefore($this->provider->teamFixturesBefore($opp->provider_id, $before, $fetch), $fixture, $sample);
        $teamVenue = array_values(array_filter($teamRows, fn ($r) => $this->isHome($r, $team->provider_id) === $forHomeTeam));
        $oppRelevantHome = !$forHomeTeam;
        $oppVenue = array_values(array_filter($oppRows, fn ($r) => $this->isHome($r, $opp->provider_id) === $oppRelevantHome));

        return new TeamGoalEvidence(
            min(count($teamRows), count($oppRows)),
            $this->rate($teamRows, fn ($r) => $this->gf($r, $team->provider_id) > 0),
            $this->rate($teamVenue, fn ($r) => $this->gf($r, $team->provider_id) > 0),
            $this->rate($oppRows, fn ($r) => $this->ga($r, $opp->provider_id) > 0),
            $this->rate($oppVenue, fn ($r) => $this->ga($r, $opp->provider_id) > 0),
            $this->rate($oppRows, fn ($r) => $this->ga($r, $opp->provider_id) === 0),
            $this->rate($teamRows, fn ($r) => $this->gf($r, $team->provider_id) === 0),
            false,
            false,
            (bool) $fixture->competition->high_variance,
        );
    }

    private function completedBefore(array $rows, Fixture $fixture, int $sample): array
    {
        $kickoff = CarbonImmutable::parse($fixture->kickoff_at);
        $eligible = array_values(array_filter($rows, function ($row) use ($fixture, $kickoff) {
            if (!isset($row['goals']['home'], $row['goals']['away']) || ($row['fixture']['status']['short'] ?? null) !== 'FT') return false;
            if ((int) ($row['fixture']['id'] ?? 0) === (int) $fixture->provider_id) return false;
            $date = $row['fixture']['date'] ?? null;
            return $date && CarbonImmutable::parse($date)->lt($kickoff);
        }));
        usort($eligible, fn ($a, $b) => strcmp((string) ($b['fixture']['date'] ?? ''), (string) ($a['fixture']['date'] ?? '')));
        return array_slice($eligible, 0, $sample);
    }

    private function isHome(array $r, int $id): bool { return (int) ($r['teams']['home']['id'] ?? 0) === $id; }
    private function gf(array $r, int $id): int { return $this->isHome($r, $id) ? (int) $r['goals']['home'] : (int) $r['goals']['away']; }
    private function ga(array $r, int $id): int { return $this->isHome($r, $id) ? (int) $r['goals']['away'] : (int) $r['goals']['home']; }
    private function rate(array $r, callable $f): float { return $r ? count(array_filter($r, $f)) / count($r) : 0.0; }
}
