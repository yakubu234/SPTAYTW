<?php

namespace App\Console\Commands;

use App\Models\RacingRace;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RacingRaceReport extends Command
{
    protected $signature = 'racing:race-report {date? : YYYY-MM-DD}';
    protected $description = 'Rank the first four horses in every stored race without forcing a betting selection';

    public function handle(): int
    {
        $date = Carbon::parse($this->argument('date') ?: now()->toDateString())->toDateString();
        $races = RacingRace::with(['meeting','runners','analyses'])
            ->whereDate('off_time',$date)->orderBy('off_time')->get();
        $this->info("Race-by-race evidence report: {$date} ({$races->count()} races)");
        foreach ($races as $race) {
            $this->newLine();
            $this->line($race->off_time->format('H:i').' '.($race->meeting?->course ?? 'Unknown').' — '.$race->name);
            $eligibleIds = $race->runners->where('non_runner',false)->pluck('id')->all();
            $ranked = $race->analyses->filter(fn($a)=>in_array($a->runner_id,$eligibleIds,true))
                ->sort(fn($a,$b)=>($b->score <=> $a->score) ?: ($b->win_probability <=> $a->win_probability))->values();
            if ($ranked->isEmpty()) {
                $this->warn('NO BET — no completed analysis for active runners.');
                continue;
            }
            $this->table(['Rank','Horse','Score','Quality','History','Historic Top 3','Historic Top 4','Recent form','Existing status','Warnings'],
                $ranked->take(4)->map(function($a,$i) use($race) {
                    $e=$a->evidence ?? [];
                    $warnings=[];
                    if((int)($e['rated_history_runs']??0)<3) $warnings[]='Limited history';
                    if(($e['recent_form_score']??null)===null) $warnings[]='Recent form unavailable';
                    elseif((float)$e['recent_form_score']<config('racing.thresholds.minimum_recent_form',30)) $warnings[]='Weak recent form';
                    if($a->data_quality<config('racing.thresholds.minimum_data_quality',55)) $warnings[]='Low quality';
                    if(in_array($a->race_confidence,['C','D'],true)) $warnings[]='Weak race separation';
                    return [$i+1,$race->runners->firstWhere('id',$a->runner_id)?->horse ?? 'Unknown',
                        $a->score,$a->data_quality,(int)($e['rated_history_runs']??0),
                        isset($e['top3_rate'])?number_format(100*(float)$e['top3_rate'],1).'%':'-',
                        isset($e['top4_rate'])?number_format(100*(float)$e['top4_rate'],1).'%':'-',
                        isset($e['recent_form_score'])?number_format((float)$e['recent_form_score'],1):'-',
                        strtoupper($a->status),implode('; ',$warnings) ?: '-'];
                })->all());
            $qualified=$ranked->filter(fn($a)=>in_array($a->status,['strong','candidate','strong_qualified','qualified'],true));
            $this->line($qualified->isEmpty()
                ? 'NO BET — no runner passed the existing predictive gates.'
                : 'REVIEW ONLY — '.$qualified->count().' predictive qualifier(s); no validated Top-3/Top-4 betting recommendation.');
        }
        $this->warn('Historic Top 3/4 frequencies are descriptive, not calibrated forecasts; previously enriched records may lack Top 4 until refreshed. Ranking is not a finishing-position guarantee. Existing place_probability is heuristic, not validated Top-3 or Top-4 odds. Check bookmaker terms and non-runners.');
        return self::SUCCESS;
    }
}
