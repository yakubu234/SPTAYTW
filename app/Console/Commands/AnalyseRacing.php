<?php
namespace App\Console\Commands;
use App\Models\{RacingAnalysis,RacingRace};
use App\Services\Racing\RacingAnalysisService;
use Illuminate\Console\Command;

class AnalyseRacing extends Command {
 protected $signature='racing:analyse {date? : YYYY-MM-DD}'; protected $description='Analyse horse races for a date';
 public function handle(RacingAnalysisService $s): int {
  $d=$this->argument('date')?:now()->toDateString();
  $r=RacingRace::with('runners')->whereDate('off_time',$d)->get();$n=0;
  foreach($r as $race)$n+=$s->analyse($race);
  $counts=RacingAnalysis::whereIn('race_id',$r->pluck('id'))->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total','status');
  $this->info("Analysed {$r->count()} races / {$n} runners for {$d}.");
  $this->table(['Status','Runners'],[
   ['Strong Qualified',(int)($counts['strong_qualified']??0)],
   ['Qualified',(int)($counts['qualified']??0)],
   ['Strong (prediction only)',(int)($counts['strong']??0)],
   ['Candidate (prediction only)',(int)($counts['candidate']??0)],
   ['Watch',(int)($counts['watch']??0)],
   ['Skip',(int)($counts['skip']??0)],
  ]);
  return self::SUCCESS;
 }
}
