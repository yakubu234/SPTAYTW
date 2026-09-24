<?php

namespace App\Console\Commands;

use App\Models\RacingRace;
use App\Services\Racing\RacingApiClient;
use Illuminate\Console\Command;

class InspectRacingData extends Command
{
    protected $signature = 'racing:inspect {date? : YYYY-MM-DD} {--api : Also inspect the live Basic racecard payload}';
    protected $description = 'Inspect racing field coverage and enrichment without exposing credentials';

    public function handle(RacingApiClient $api): int
    {
        $date = $this->argument('date') ?: now()->toDateString();
        $races = RacingRace::with('runners')->whereDate('off_time', $date)->get();
        $runners = $races->flatMap->runners->values();

        $this->table(['Metric','Value'], [
            ['Races', $races->count()],
            ['Runners', $runners->count()],
            ['Official rating populated', $runners->whereNotNull('official_rating')->count()],
            ['Speed rating populated', $runners->whereNotNull('speed_rating')->count()],
            ['Performance rating populated', $runners->whereNotNull('performance_rating')->count()],
            ['Decimal odds populated', $runners->whereNotNull('decimal_odds')->count()],
            ['Evidence cache populated', $runners->filter(fn($r)=>cache()->has("racing:evidence:{$r->race_id}:{$r->id}"))->count()],
        ]);

        if (!$this->option('api')) return self::SUCCESS;

        $payload = $api->racecards($date);
        $race = ($payload['racecards'] ?? [])[0] ?? [];
        $runner = ($race['runners'] ?? [])[0] ?? [];

        $this->newLine();
        $this->line('First race API keys: '.implode(', ', array_keys($race)));
        $this->line('First runner API keys: '.implode(', ', array_keys($runner)));

        $safe = collect($runner)->only([
            'horse','horse_id','jockey','trainer','draw','lbs','weight_lbs','ofr','or',
            'official_rating','speed_rating','performance_rating','decimal_odds'
        ])->all();
        $this->line('Mapped runner sample: '.json_encode($safe, JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
