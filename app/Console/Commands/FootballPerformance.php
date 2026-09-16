<?php
namespace App\Console\Commands;
use Illuminate\Console\Command; use App\Models\MarketAnalysis;
class FootballPerformance extends Command { protected $signature='football:performance {--days=30}'; protected $description='Show observed performance by market and score band';
 public function handle():int { $since=now()->subDays((int)$this->option('days'));$rows=MarketAnalysis::whereNotNull('result')->where('graded_at','>=',$since)->get()->groupBy(fn($a)=>$a->market_type.'|'.$this->band($a->score));$out=[];foreach($rows as $key=>$g){[$market,$band]=explode('|',$key);$wins=$g->where('result','won')->count();$out[]=[$market,$band,$g->count(),$wins,round($wins/max(1,$g->count())*100,1).'%'];}$this->table(['Market','Score band','Picks','Won','Observed win rate'],$out);return self::SUCCESS; }
 private function band(int $s):string{return $s>=90?'90-100':($s>=85?'85-89':($s>=80?'80-84':($s>=70?'70-79':'<70')));}
}
