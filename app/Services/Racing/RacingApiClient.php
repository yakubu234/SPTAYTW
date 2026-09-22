<?php
namespace App\Services\Racing;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RacingApiClient {
    private function request(string $path, array $query=[]): array {
        $cfg=config('racing.api'); $user=$cfg['username']; $pass=$cfg['password'];
        if (!$user || !$pass) throw new RuntimeException('RACING_API_USERNAME and RACING_API_PASSWORD are required.');
        $response=Http::timeout($cfg['timeout'])->withBasicAuth($user,$pass)->get(rtrim($cfg['base_url'],'/').'/'.ltrim($path,'/'),$query);
        $response->throw(); return $response->json() ?: [];
    }
    public function racecards(string $date): array { return $this->request('racecards/standard',['date'=>$date]); }
    public function results(string $date): array { return $this->request('results',['date'=>$date]); }
}