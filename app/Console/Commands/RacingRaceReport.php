<?php

namespace App\Console\Commands;

use App\Models\RacingRace;
use App\Services\Racing\RacingPlaceEvidence;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RacingRaceReport extends Command
{
    protected $signature = 'racing:race-report {date? : YYYY-MM-DD}';
    protected $description = 'Rank the first four horses in every stored race without forcing a betting selection';

    public function handle(RacingPlaceEvidence $placeAudit): int
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
            $this->table(['Rank','Horse','Score','Quality','History','Historic Top 3','Historic Top 4','Top 3 audit','Top 4 audit','Recent form','Existing status','Warnings'],
                $ranked->take(4)->map(function($a,$i) use($race,$placeAudit) {
                    $e=$a->evidence ?? [];
                    $audit=$placeAudit->assess($e,(int)$a->data_quality,(int)$race->runners->where('non_runner',false)->count());
                    $warnings=[];
                    if((int)($e['rated_history_runs']??0)<3) $warnings[]='Limited history';
                    if(($e['recent_form_score']??null)===null) $warnings[]='Recent form unavailable';
                    elseif((float)$e['recent_form_score']<config('racing.thresholds.minimum_recent_form',30)) $warnings[]='Weak recent form';
                    if($a->data_quality<config('racing.thresholds.minimum_data_quality',55)) $warnings[]='Low quality';
                    if(in_array($a->race_confidence,['C','D'],true)) $warnings[]='Weak win separation (not a place-market gate)';
                    $warnings=array_merge($warnings,$audit['issues']);
                    return [$i+1,$race->runners->firstWhere('id',$a->runner_id)?->horse ?? 'Unknown',
                        $a->score,$a->data_quality,(int)($e['rated_history_runs']??0),
                        $audit['runs'] ? $audit['top3'].'/'.$audit['runs'].' ('.number_format(100*$audit['top3_rate'],1).'%)' : '-',
                        $audit['top4_rate'] !== null ? $audit['top4'].'/'.$audit['runs'].' ('.number_format(100*$audit['top4_rate'],1).'%)' : '-',
                        $audit['top3_status'],$audit['top4_status'],
                        isset($e['recent_form_score'])?number_format((float)$e['recent_form_score'],1):'-',
                        strtoupper($a->status),implode('; ',$warnings) ?: '-'];
                })->all());
            $qualified=$ranked->filter(fn($a)=>in_array($a->status,['strong','candidate','strong_qualified','qualified'],true));
            $this->line($qualified->isEmpty()
                ? 'NO WIN-MODEL QUALIFIER — see separate Top-3/Top-4 audits above; REVIEW does not mean approved stake.'
                : 'WIN MODEL REVIEW ONLY — '.$qualified->count().' predictive qualifier(s); place audits above are independent and NOT validated betting recommendations.');
        }
        $this->warn('Top-3/Top-4 REVIEW is a research flag, NEVER a stake approval. Counts use all recorded runs, including non-finishes. Verify failed enrichment, off time, non-runners and actual bookmaker terms. Historic Top 3/4 frequencies are descriptive, not calibrated forecasts; previously enriched records may lack Top 4 until refreshed. Ranking is not a finishing-position guarantee. Existing place_probability is heuristic, not validated Top-3 or Top-4 odds. Check bookmaker terms and non-runners.');
        return self::SUCCESS;
    }
}
