<?php
namespace App\Services\Football;

use App\Contracts\Football\FootballDataProvider;
use App\DTOs\Football\MatchEvidence;
use App\Models\Fixture;

final class MatchEvidenceBuilder
{
    public function __construct(private FootballDataProvider $provider) {}

    public function build(Fixture $f, int $sample = 10): MatchEvidence
    {
        $f->loadMissing(['homeTeam', 'awayTeam', 'competition']);
        $h = $this->rows($this->provider->teamFixtures($f->homeTeam->provider_id, max(10, $sample)), $sample);
        $a = $this->rows($this->provider->teamFixtures($f->awayTeam->provider_id, max(10, $sample)), $sample);
        $hv = array_values(array_filter($h, fn ($r) => $this->isHome($r, $f->homeTeam->provider_id)));
        $av = array_values(array_filter($a, fn ($r) => !$this->isHome($r, $f->awayTeam->provider_id)));
        $c = array_merge($h, $a);

        return new MatchEvidence(
            min(count($h), count($a)),
            $this->rate($c, fn ($r) => $this->total($r) >= 5),
            $this->rate($h, fn ($r) => $this->total($r) >= 5),
            $this->rate($a, fn ($r) => $this->total($r) >= 5),
            $this->rate($c, fn ($r) => $this->total($r) >= 2),
            $this->win($h, $f->homeTeam->provider_id),
            $this->win($a, $f->awayTeam->provider_id),
            $this->win($hv, $f->homeTeam->provider_id),
            $this->win($av, $f->awayTeam->provider_id),
            $this->rate($h, fn ($r) => $this->gf($r, $f->homeTeam->provider_id) > 0),
            $this->rate($a, fn ($r) => $this->gf($r, $f->awayTeam->provider_id) > 0),
            $this->rate($h, fn ($r) => $this->ga($r, $f->homeTeam->provider_id) > 0),
            $this->rate($a, fn ($r) => $this->ga($r, $f->awayTeam->provider_id) > 0),
            $this->rate($c, fn ($r) => (int) $r['goals']['home'] > 0 && (int) $r['goals']['away'] > 0),
            false,
            (bool) $f->competition->high_variance,
            $this->rate($c, fn ($r) => $this->total($r) >= 3),
            $this->rate($c, fn ($r) => $this->total($r) >= 4),
            $this->averageGoals($c),
            $this->rate($hv, fn ($r) => $this->total($r) >= 4),
            $this->rate($av, fn ($r) => $this->total($r) >= 4),
            $this->averageGoals($hv),
            $this->averageGoals($av),
        );
    }

    private function rows(array $rows, int $sample): array
    {
        return array_slice(array_values(array_filter($rows, fn ($x) =>
            isset($x['goals']['home'], $x['goals']['away'])
            && ($x['fixture']['status']['short'] ?? 'FT') === 'FT'
        )), 0, $sample);
    }

    private function isHome(array $r, int $id): bool { return (int) ($r['teams']['home']['id'] ?? 0) === $id; }
    private function gf(array $r, int $id): int { return $this->isHome($r, $id) ? (int) $r['goals']['home'] : (int) $r['goals']['away']; }
    private function ga(array $r, int $id): int { return $this->isHome($r, $id) ? (int) $r['goals']['away'] : (int) $r['goals']['home']; }
    private function total(array $r): int { return (int) $r['goals']['home'] + (int) $r['goals']['away']; }
    private function rate(array $r, callable $f): float { return $r ? count(array_filter($r, $f)) / count($r) : 0.0; }
    private function win(array $r, int $id): float { return $this->rate($r, fn ($x) => $this->gf($x, $id) > $this->ga($x, $id)); }
    private function averageGoals(array $r): float { return $r ? array_sum(array_map(fn ($x) => $this->total($x), $r)) / count($r) : 0.0; }
}
