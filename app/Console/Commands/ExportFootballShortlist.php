<?php
namespace App\Console\Commands;
use App\Models\MarketAnalysis;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
class ExportFootballShortlist extends Command {
 protected $signature='football:export-shortlist {date?} {--minimum=85} {--quality=80} {--output=}';
 protected $description='Export qualified football selections to CSV';
 public function handle(): int { $date=Carbon::parse($this->argument('date')?:now()->toDateString())->toDateString();$min=(int)$this->option('minimum');$quality=(int)$this->option('quality');$path=$this->option('output')?:storage_path("app/football-shortlist-$date.csv");$rows=MarketAnalysis::with(['fixture.homeTeam','fixture.awayTeam','fixture.competition'])->whereHas('fixture',fn($q)=>$q->whereDate('kickoff_at',$date))->where('score','>=',$min)->where('data_quality_score','>=',$quality)->whereIn('status',['strong_qualified','qualified'])->orderByDesc('score')->get()->unique(fn($a)=>$a->fixture_id.'|'.$a->market_type.'|'.$a->selection);$h=fopen($path,'w');fputcsv($h,['fixture','competition','market','selection','score','data_quality','status','kickoff']);foreach($rows as $a){$f=$a->fixture;fputcsv($h,[$f->homeTeam->name.' vs '.$f->awayTeam->name,$f->competition->name,$a->market_type,$a->selection,$a->score,$a->data_quality_score,$a->status,$f->kickoff_at?->toIso8601String()]);}fclose($h);$this->info("Exported {$rows->count()} selections to $path");return self::SUCCESS; }
}
