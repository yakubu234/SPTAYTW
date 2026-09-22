<?php
namespace App\Console\Commands;
use App\Services\Racing\RacingGrader; use Illuminate\Console\Command;
class GradeRacing extends Command {
 protected $signature='racing:grade {date? : YYYY-MM-DD}'; protected $description='Grade horse racing analyses against results';
 public function handle(RacingGrader $g): int {$d=$this->argument('date')?:now()->subDay()->toDateString();$n=$g->gradeDate($d);$this->info("Graded {$n} runner analyses for {$d}.");return self::SUCCESS;}
}