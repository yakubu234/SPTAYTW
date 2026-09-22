<?php
namespace App\Console\Commands;
use App\Models\RacingRace; use App\Services\Racing\RacingAnalysisService; use Illuminate\Console\Command;
class AnalyseRacing extends Command {
 protected $signature='racing:analyse {date? : YYYY-MM-DD}'; protected $description='Analyse horse races for a date';
 public function handle(RacingAnalysisService $s): int {$d=$this->argument('date')?:now()->toDateString();$r=RacingRace::with('runners')->whereDate('off_time',$d)->get();$n=0;foreach($r as $race)$n+=$s->analyse($race);$this->info("Analysed {$r->count()} races / {$n} runners for {$d}.");return self::SUCCESS;}
}