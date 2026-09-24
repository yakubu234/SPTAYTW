<?php

namespace App\Console\Commands;

use App\Models\RacingAnalysis;
use App\Services\Racing\RacingGrader;
use Illuminate\Console\Command;

class GradeRacing extends Command
{
    protected $signature='racing:grade {date? : YYYY-MM-DD}';
    protected $description='Grade horse racing analyses against results';

    public function handle(RacingGrader $g): int
    {
        $d=$this->argument('date')?:now()->toDateString();
        $n=$g->gradeDate($d);
        $this->info("Graded {$n} runner analyses for {$d}.");

        $shortlist=RacingAnalysis::with(['runner.race.meeting'])
            ->whereHas('race', fn($q)=>$q->whereDate('off_time',$d))
            ->whereIn('status',['strong','candidate','strong_qualified','qualified'])
            ->orderByDesc('score')->get();

        if($shortlist->isEmpty()){
            $this->warn('No prediction-qualified runners were recorded for this date.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Prediction shortlist results');
        $this->table(
            ['Time','Course','Horse','Status','Score','Finish','Win','Top 3','Graded'],
            $shortlist->map(function($a){
                $race=$a->runner->race;
                return [
                    $race->off_time?->format('H:i'),
                    $race->meeting?->course,
                    $a->runner?->horse,
                    strtoupper(str_replace('_',' ',$a->status)),
                    $a->score,
                    $a->actual_finish ?? '-',
                    $a->actual_finish !== null ? ($a->win_hit ? 'YES' : 'NO') : '-',
                    $a->actual_finish !== null ? ($a->place_hit ? 'YES' : 'NO') : '-',
                    $a->actual_finish !== null ? 'YES' : 'NO',
                ];
            })->all()
        );

        $graded=$shortlist->whereNotNull('actual_finish');
        if($graded->isNotEmpty()){
            $this->line(sprintf(
                'Shortlist graded: %d/%d | Wins: %d | Top 3: %d',
                $graded->count(),$shortlist->count(),
                $graded->where('win_hit',true)->count(),
                $graded->where('place_hit',true)->count()
            ));
        }

        return self::SUCCESS;
    }
}
