<?php
namespace App\Console\Commands;
use Illuminate\Console\Command; use App\Models\MarketAnalysis; use App\Services\Football\MarketGrader;
class GradeFootballSelections extends Command { protected $signature='football:grade {date?}'; protected $description='Grade completed football market analyses';
 public function handle(MarketGrader $grader):int { $date=$this->argument('date')?:now()->toDateString();$rows=MarketAnalysis::with(['fixture.homeTeam','fixture.awayTeam'])->whereNull('result')->whereHas('fixture',fn($q)=>$q->whereDate('kickoff_at',$date)->whereNotNull('home_goals')->whereNotNull('away_goals'))->get();$bar=$this->output->createProgressBar($rows->count());foreach($rows as $a){if($r=$grader->grade($a)){$a->update(['result'=>$r,'graded_at'=>now()]);}$bar->advance();}$bar->finish();$this->newLine();$this->info("Graded {$rows->count()} selections.");return self::SUCCESS; } }
