<?php

namespace App\Services\Football;

use App\Models\Fixture;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class CornerStatisticsService
{
    public function fetch(Fixture $fixture): bool
    {
        if ($fixture->corner_stats_checked_at !== null) {
            return $fixture->home_corners !== null && $fixture->away_corners !== null;
        }

        $key = config('football.api_football.key');
        if (!$key) throw new RuntimeException('API_FOOTBALL_KEY is not configured.');

        $payload = Http::baseUrl(config('football.api_football.base_url'))
            ->withHeaders(['x-apisports-key' => $key])
            ->timeout(config('football.api_football.timeout', 15))
            ->retry(2, 300)
            ->get('/fixtures/statistics', ['fixture' => $fixture->provider_id])
            ->throw()->json();

        if (!empty($payload['errors'])) {
            throw new RuntimeException('API-Football statistics error: '.json_encode($payload['errors']));
        }

        $fixture->loadMissing(['homeTeam', 'awayTeam']);
        $corners = [];
        foreach ($payload['response'] ?? [] as $team) {
            $id = $team['team']['id'] ?? null;
            foreach ($team['statistics'] ?? [] as $stat) {
                if (($stat['type'] ?? '') === 'Corner Kicks' && is_numeric($stat['value'] ?? null)
                    && (int) $stat['value'] >= 0) {
                    $corners[$id] = (int) $stat['value'];
                }
            }
        }

        $home = $corners[$fixture->homeTeam->provider_id] ?? null;
        $away = $corners[$fixture->awayTeam->provider_id] ?? null;
        $fixture->update([
            'home_corners' => $home,
            'away_corners' => $away,
            'corner_stats_checked_at' => now(),
        ]);

        return $home !== null && $away !== null;
    }
}
