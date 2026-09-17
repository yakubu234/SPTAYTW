<?php

namespace App\Services\Football\Providers;

use App\Contracts\Football\FootballDataProvider;
use Carbon\CarbonImmutable;
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

        $payload = Http::baseUrl(config('football.api_football.base_url'))
            ->withHeaders(['x-apisports-key' => $key])
            ->timeout(config('football.api_football.timeout', 15))
            ->retry(2, 300)
            ->get($path, $query)
            ->throw()
            ->json();

        $errors = $payload['errors'] ?? [];
        if (!empty($errors)) {
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
            $from = $to->subDays(400);
            $rows = [];

            // API-Football date-range fixture queries require both `from` and
            // `to`, plus a season. Try the target calendar year first (works
            // for calendar-year leagues and seasons beginning that year), then
            // the previous season when more history is needed.
            foreach ([$to->year, $to->year - 1] as $season) {
                $seasonRows = $this->get('/fixtures', [
                    'team' => $teamId,
                    'season' => $season,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'status' => 'FT',
                ]);

                foreach ($seasonRows as $row) {
                    $fixtureId = (int) ($row['fixture']['id'] ?? 0);
                    if ($fixtureId > 0) {
                        $rows[$fixtureId] = $row;
                    }
                }

                if (count($rows) >= $last) {
                    break;
                }
            }

            $rows = array_values($rows);
            usort($rows, fn (array $a, array $b) => strcmp(
                (string) ($b['fixture']['date'] ?? ''),
                (string) ($a['fixture']['date'] ?? '')
            ));

            $this->teamFixtureCache[$cacheKey] = array_slice($rows, 0, max(1, $last));
        }

        return $this->teamFixtureCache[$cacheKey];
    }

    public function injuries(int $fixtureId): array
    {
        return $this->get('/injuries', ['fixture' => $fixtureId]);
    }
}
