<?php

namespace App\Services\Football\Providers;

use App\Contracts\Football\FootballDataProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ApiFootballProvider implements FootballDataProvider
{
    /** @var array<string,array> */
    private array $teamFixtureCache = [];

    private function get(string $path, array $query = []): array
    {
        $key = config('football.api_football.key');
        if (!$key) {
            throw new RuntimeException('API_FOOTBALL_KEY is not configured.');
        }

        return Http::baseUrl(config('football.api_football.base_url'))
            ->withHeaders(['x-apisports-key' => $key])
            ->timeout(config('football.api_football.timeout', 15))
            ->retry(2, 300)
            ->get($path, $query)
            ->throw()
            ->json('response') ?? [];
    }

    public function fixtures(string $date): array
    {
        return $this->get('/fixtures', ['date' => $date]);
    }

    public function teamFixtures(int $teamId, int $last = 10): array
    {
        $cacheKey = $teamId . ':' . $last;

        if (!array_key_exists($cacheKey, $this->teamFixtureCache)) {
            $this->teamFixtureCache[$cacheKey] = $this->get('/fixtures', [
                'team' => $teamId,
                'last' => $last,
                'status' => 'FT',
            ]);
        }

        return $this->teamFixtureCache[$cacheKey];
    }

    public function injuries(int $fixtureId): array
    {
        return $this->get('/injuries', ['fixture' => $fixtureId]);
    }
}
