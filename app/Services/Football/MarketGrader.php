<?php

namespace App\Services\Football;

use App\Models\MarketAnalysis;

final class MarketGrader
{
    public function grade(MarketAnalysis $analysis): ?string
    {
        $fixture = $analysis->fixture;
        if ($fixture->home_goals === null || $fixture->away_goals === null) return null;

        $home = (int) $fixture->home_goals;
        $away = (int) $fixture->away_goals;

        return match ($analysis->market_type) {
            'corners_over_4_5' => $this->corners($fixture->home_corners, $fixture->away_corners, 5, true),
            'corners_under_18' => $this->corners($fixture->home_corners, $fixture->away_corners, 18, false),
            'team_over_0_5' => $this->team05($analysis->selection, $fixture->homeTeam->name, $home, $away),
            'under_4_5' => $home + $away < 5 ? 'won' : 'lost',
            'home' => $home > $away ? 'won' : 'lost',
            'away' => $away > $home ? 'won' : 'lost',
            'home_or_draw' => $home >= $away ? 'won' : 'lost',
            'away_or_draw' => $away >= $home ? 'won' : 'lost',
            'team_or_gg' => $this->teamOrGg($analysis->selection, $fixture->homeTeam->name, $home, $away),
            default => null,
        };
    }

    private function corners(?int $home, ?int $away, int $line, bool $over): ?string
    {
        if ($home === null || $away === null) return null;
        $total = $home + $away;
        return ($over ? $total >= $line : $total < $line) ? 'won' : 'lost';
    }

    private function team05(string $selection, string $homeName, int $home, int $away): string
    {
        return str_starts_with($selection, $homeName)
            ? ($home > 0 ? 'won' : 'lost')
            : ($away > 0 ? 'won' : 'lost');
    }

    private function teamOrGg(string $selection, string $homeName, int $home, int $away): string
    {
        $bothScore = $home > 0 && $away > 0;
        $selectedHome = str_starts_with($selection, $homeName);
        $selectedWins = $selectedHome ? $home > $away : $away > $home;

        return $selectedWins || $bothScore ? 'won' : 'lost';
    }
}
