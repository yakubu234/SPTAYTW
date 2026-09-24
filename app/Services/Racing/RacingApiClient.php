<?php

namespace App\Services\Racing;

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
        return $this->request('racecards/standard', ['date' => $date]);
    }

    public function results(string $date): array
    {
        return $this->request('results', ['date' => $date]);
    }
}
