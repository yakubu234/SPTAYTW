<?php

namespace App\Services\Racing;

use Carbon\Carbon;
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

        $response = Http::timeout($cfg['timeout'])
            ->withBasicAuth($user, $pass)
            ->get(rtrim($cfg['base_url'], '/').'/'.ltrim($path, '/'), $query);

        $response->throw();

        return $response->json() ?: [];
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
                'The Racing API Free plan only supports today or tomorrow racecards. '
                .'Use today/tomorrow, or upgrade before requesting arbitrary dates.'
            );
        }

        return $this->request('racecards/free', ['day' => $day]);
    }

    public function results(string $date): array
    {
        throw new RuntimeException(
            'Historical results grading is disabled on the Free plan. '
            .'Do not upgrade until the free racecard import has been validated.'
        );
    }
}
