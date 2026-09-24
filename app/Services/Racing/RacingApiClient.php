<?php

namespace App\Services\Racing;

use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RacingApiClient
{
    private static float $lastRequestAt = 0.0;

    private function request(string $path, array $query = [], int $requestsPerSecond = 5): array
    {
        $cfg = config('racing.api');
        $user = $cfg['username'];
        $pass = $cfg['password'];

        if (!$user || !$pass) {
            throw new RuntimeException('RACING_API_USERNAME and RACING_API_PASSWORD are required.');
        }

        $minimumGap = 1 / max(1, $requestsPerSecond);
        $elapsed = microtime(true) - self::$lastRequestAt;
        if ($elapsed < $minimumGap) {
            usleep((int)(($minimumGap - $elapsed) * 1_000_000));
        }

        try {
            $response = Http::timeout($cfg['timeout'])
                ->retry(2, 750)
                ->withBasicAuth($user, $pass)
                ->acceptJson()
                ->get(rtrim($cfg['base_url'], '/').'/'.ltrim($path, '/'), $query);

            self::$lastRequestAt = microtime(true);
            $response->throw();

            return $response->json() ?: [];
        } catch (RequestException $e) {
            self::$lastRequestAt = microtime(true);
            $status = $e->response?->status();
            $detail = $e->response?->json('detail') ?: $e->getMessage();
            if (is_array($detail) || is_object($detail)) {
                $detail = json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            throw new RuntimeException(
                "The Racing API request failed ({$status}) for {$path}: ".(string) $detail,
                previous: $e
            );
        }
    }

    public function racecards(string $date): array
    {
        $requested = Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();

        if ($requested->equalTo($today)) {
            $day = 'today';
        } elseif ($requested->equalTo($today->copy()->addDay())) {
            $day = 'tomorrow';
        } else {
            throw new RuntimeException('The Racing API Basic racecards endpoint supports today and tomorrow only.');
        }

        return $this->request('racecards/basic', ['day' => $day, 'limit' => 500], 2);
    }

    public function racecardHorseResults(string $horseId, array $query = []): array
    {
        return $this->request('racecards/'.rawurlencode($horseId).'/results', $query, 5);
    }

    public function racecardHorseResultsSample(string $horseId, array $query = []): array
    {
        return $this->racecardHorseResults($horseId, $query);
    }

    public function horseDistanceTimes(string $horseId, array $query = []): array
    {
        return $this->request('horses/'.rawurlencode($horseId).'/analysis/distance-times', $query, 5);
    }

    public function results(string $date): array
    {
        if (!Carbon::parse($date)->isToday()) {
            throw new RuntimeException('The Racing API Basic results endpoint exposes today only.');
        }

        return $this->request('results/today', ['limit' => 500], 5);
    }
}
