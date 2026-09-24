<?php

namespace App\Services\Racing;

use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RacingApiClient
{
    private function request(string $path, array $query = []): array
    {
        $cfg = config('racing.api');
        $user = $cfg['username'];
        $pass = $cfg['password'];

        if (!$user || !$pass) {
            throw new RuntimeException('RACING_API_USERNAME and RACING_API_PASSWORD are required.');
        }

        try {
            $response = Http::timeout($cfg['timeout'])
                ->retry(2, 500)
                ->withBasicAuth($user, $pass)
                ->acceptJson()
                ->get(rtrim($cfg['base_url'], '/').'/'.ltrim($path, '/'), $query);

            $response->throw();

            return $response->json() ?: [];
        } catch (RequestException $e) {
            $status = $e->response?->status();
            $detail = $e->response?->json('detail') ?: $e->getMessage();

            throw new RuntimeException(
                "The Racing API request failed ({$status}) for {$path}: {$detail}",
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
            throw new RuntimeException(
                'The Racing API Basic racecards endpoint supports today and tomorrow only.'
            );
        }

        return $this->request('racecards/basic', [
            'day' => $day,
            'limit' => 500,
        ]);
    }

    public function racecardHorseResults(string $horseId, array $query = []): array
    {
        return $this->request('racecards/'.rawurlencode($horseId).'/results', $query);
    }

    public function horseDistanceTimes(string $horseId, array $query = []): array
    {
        return $this->request('horses/'.rawurlencode($horseId).'/analysis/distance-times', $query);
    }

    public function results(string $date): array
    {
        if (!Carbon::parse($date)->isToday()) {
            throw new RuntimeException(
                'The Racing API Basic results endpoint exposes today only. Grade the race day on the same date.'
            );
        }

        return $this->request('results/today', ['limit' => 500]);
    }
}
