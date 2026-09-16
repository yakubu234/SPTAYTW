<?php
namespace App\Console\Commands;
use Illuminate\Console\Command; use App\Models\Fixture; use App\Services\Football\FixtureAnalysisService;
class AnalyseFootballFixtures extends Command { protected $signature='football:analyse {date?} {--minimum=70}'; protected $description='Analyse all supported markets for fixtures on a date';
 public function handle(FixtureAnalysisService $service):int { $date=$this->argument('date')?:now()->toDateString();$min=(int)$this->option('minimum');$fixtures=Fixture::with(['homeTeam','awayTeam','competition'])->whereDate('kickoff_at',$date)->get();$rows=[];foreach($fixtures as $f){foreach($service->analyse($f) as $a){if($a->score>=$min)$rows[]=[$f->homeTeam->name.' vs '.$f->awayTeam->name,$a->selection,$a->score,$a->data_quality_score,strtoupper($a->status)];}}usort($rows,fn($a,$b)=>$b[2]<=>$a[2]);$this->table(['Match','Selection','Score','Quality','Status'],$rows);return self::SUCCESS; } }
