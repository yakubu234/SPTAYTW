<?php
namespace App\Console\Commands;
use App\Services\Racing\RacingImporter; use Illuminate\Console\Command;
class SyncRacing extends Command {
 protected $signature='racing:sync {date? : YYYY-MM-DD}'; protected $description='Import horse racing cards for a date';
 public function handle(RacingImporter $importer): int {$d=$this->argument('date')?:now()->toDateString();$c=$importer->importDate($d);$this->info("Imported/updated {$c['races']} races and {$c['runners']} runners for {$d}.");return self::SUCCESS;}
}