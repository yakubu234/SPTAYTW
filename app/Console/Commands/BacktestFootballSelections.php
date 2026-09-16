<?php
namespace App\Console\Commands;
use App\Models\MarketAnalysis;
use Illuminate\Console\Command;
class BacktestFootballSelections extends Command {
 protected $signature='football:backtest {--days=90} {--minimum=80} {--quality=70}';
 protected $description='Summarise observed results for historical graded selections';
 public function handle(): int { $days=max(1,(int)$this->option('days'));$rows=MarketAnalysis::whereNotNull('result')->where('graded_at','>=',now()->subDays($days))->where('score','>=',(int)$this->option('minimum'))->where('data_quality_score','>=',(int)$this->option('quality'))->get()->groupBy(fn($a)=>$a->market_type.'|'.(int)(floor($a->score/5)*5))->map(function($g,$key){[$market,$floor]=explode('|',$key);$n=$g->count();$w=$g->where('result','won')->count();return [$market,$floor.'-'.min(100,(int)$floor+4),$n,$w,$g->where('result','lost')->count(),$n?round($w/$n*100,1):0,$n>=50?'usable':'insufficient sample'];})->values()->all();$this->table(['Market','Score band','N','Won','Lost','Observed %','Sample'], $rows);return self::SUCCESS; }
}
