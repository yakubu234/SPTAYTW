<?php
namespace App\Console\Commands;
use App\Services\Football\FixtureImporter;
use Illuminate\Console\Command;
class SyncFootballFixtures extends Command {
 protected $signature='football:sync-fixtures {date? : YYYY-MM-DD}'; protected $description='Import football fixtures for a date';
 public function handle(FixtureImporter $importer): int { $date=$this->argument('date') ?: now()->toDateString(); $count=$importer->importDate($date); $this->info("Imported/updated {$count} fixtures for {$date}."); return self::SUCCESS; }
}
