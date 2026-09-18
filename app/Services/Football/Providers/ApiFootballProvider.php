<?php

namespace App\Services\Football\Providers;

use App\Contracts\Football\FootballDataProvider;
use App\Services\Football\HistoricalFixtureStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ApiFootballProvider implements FootballDataProvider
{
    /** @var array<string,array> */
    private array $teamFixtureCache = [];

    private array $historyStats = [
        'local_hits' => 0,
        'api_requests' => 0,
        'fixtures_fetched' => 0,
        'fixtures_stored' => 0,
        'provider_failures' => 0,
    ];

    public function __construct(private HistoricalFixtureStore $historyStore) {}

    private function get(string $path, array $query = [], bool $historical = false): array
    {
        $key = config('football.api_football.key');
        if (!$key) {
            throw new RuntimeException('API_FOOTBALL_KEY is not configured.');
        }

        if ($historical) {
            $this->historyStats['api_requests']++;
        }

        try {
            $payload = Http::baseUrl(config('football.api_football.base_url'))
                ->withHeaders(['x-apisports-key' => $key])
                ->timeout(config('football.api_football.timeout', 15))
                ->retry(2, 300)
                ->get($path, $query)
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            if ($historical) {
                $this->historyStats['provider_failures']++;
            }
            throw $e;
        }

        $errors = $payload['errors'] ?? [];
        if (!empty($errors)) {
            if ($historical) {
                $this->historyStats['provider_failures']++;
            }
            $message = is_array($errors) ? json_encode($errors, JSON_UNESCAPED_SLASHES) : (string) $errors;
            throw new RuntimeException('API-Football returned an error: ' . $message);
        }

        return is_array($payload['response'] ?? null) ? $payload['response'] : [];
    }

    public function fixtures(string $date): array
    {
        return $this->get('/fixtures', ['date' => $date]);
    }

    public function teamFixtures(int $teamId, int $last = 10): array
    {
        $cacheKey = 'last:' . $teamId . ':' . $last;

        if (!array_key_exists($cacheKey, $this->teamFixtureCache)) {
            $this->teamFixtureCache[$cacheKey] = $this->get('/fixtures', [
                'team' => $teamId,
                'last' => $last,
                'status' => 'FT',
            ]);
        }

        return $this->teamFixtureCache[$cacheKey];
    }

    public function teamFixturesBefore(int $teamId, string $before, int $last = 10): array
    {
        $cacheKey = 'before:' . $teamId . ':' . $before . ':' . $last;

        if (!array_key_exists($cacheKey, $this->teamFixtureCache)) {
            $to = CarbonImmutable::parse($before)->startOfDay();

            foreach ([$to->year, $to->year - 1] as $season) {
                if ($this->historyStore->hasSeason($teamId, $season)) {
                    $this->historyStats['local_hits']++;
                } else {
                    $rows = $this->get('/fixtures', [
                        'team' => $teamId,
                        'season' => $season,
                        'status' => 'FT',
                    ], true);

                    $this->historyStats['fixtures_fetched'] += count($rows);
                    $this->historyStats['fixtures_stored'] += $this->historyStore->store($rows);
                    $this->historyStore->markSeasonFetched($teamId, $season);
                }

                $localRows = $this->historyStore->teamFixturesBefore($teamId, $to, $last);
                if (count($localRows) >= $last) {
                    break;
                }
            }

            $this->teamFixtureCache[$cacheKey] = $this->historyStore->teamFixturesBefore($teamId, $to, $last);
        }

        return $this->teamFixtureCache[$cacheKey];
    }

    public function injuries(int $fixtureId): array
    {
        return $this->get('/injuries', ['fixture' => $fixtureId]);
    }

    public function historyStats(): array
    {
        return $this->historyStats;
    }
}
