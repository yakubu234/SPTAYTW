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
            ['Historical evidence populated', $runners->whereNotNull('historical_evidence')->count()],
            ['3+ rated history runs', $runners->filter(fn($r)=>(int) data_get($r->historical_evidence, 'rated_history_runs', 0) >= 3)->count()],
        ]);

        $analyses = $races->flatMap->analyses;
        if ($analyses->isNotEmpty()) {
            $this->newLine();
            $this->table(['Analysis diagnostic','Value'], [
                ['Confidence A', $analyses->where('race_confidence','A')->count()],
                ['Confidence B', $analyses->where('race_confidence','B')->count()],
                ['Confidence C', $analyses->where('race_confidence','C')->count()],
                ['Confidence D', $analyses->where('race_confidence','D')->count()],
                ['Score >= 72', $analyses->where('score','>=',72)->count()],
                ['Score >= 82', $analyses->where('score','>=',82)->count()],
                ['Quality >= 55', $analyses->where('data_quality','>=',55)->count()],
                ['Maximum score', $analyses->max('score')],
            ]);
        }

        $shortlist = $analyses
            ->filter(fn($a) => in_array($a->status, ['strong','candidate','strong_qualified','qualified'], true))
            ->sortByDesc('score')
            ->values();

        if ($shortlist->isNotEmpty()) {
            $this->newLine();
            $this->info('Prediction shortlist');
            $this->table(
                ['Time','Course','Horse','Status','Score','Quality','Conf','Win %','Top 3 %','History','Avg finish'],
                $shortlist->map(function($a) {
                    $race=$a->race; $e=$a->evidence ?? [];
                    return [
                        optional($race->off_time)->format('H:i'),
                        optional($race->meeting)->course,
                        optional($a->runner)->horse,
                        strtoupper(str_replace('_',' ',$a->status)),
                        $a->score,
                        $a->data_quality,
                        $a->race_confidence,
                        number_format((float)$a->win_probability,1),
                        number_format((float)$a->place_probability,1),
                        (int)($e['rated_history_runs']??0),
                        isset($e['average_finish']) ? number_format((float)$e['average_finish'],1) : '-',
                    ];
                })->all()
            );
        }

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

        $sampleRunner = $runners->first(fn($r) => !empty($r->provider_id));
        if ($sampleRunner) {
            $history = $api->racecardHorseResultsSample($sampleRunner->provider_id, ['limit' => 1]);
            $historyRow = ($history['results'] ?? [])[0] ?? [];
            $this->newLine();
            $this->line('First history result keys: '.implode(', ', array_keys($historyRow)));
            $this->line('First history result sample: '.json_encode(
                collect($historyRow)->except(['comment','spotlight','quotes'])->all(),
                JSON_UNESCAPED_SLASHES
            ));
        }

        return self::SUCCESS;
    }
}
