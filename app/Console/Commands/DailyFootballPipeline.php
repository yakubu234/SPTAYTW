<?php
namespace App\Console\Commands;
use App\Models\Fixture;
use App\Services\Football\{FixtureImporter,FixtureAnalysisService,MarketGrader};
use Illuminate\Console\Command;
final class DailyFootballPipeline extends Command {
    protected $signature='football:daily {date? : YYYY-MM-DD}';
    protected $description='Sync fixtures, analyse upcoming matches and grade completed selections for a date';
    public function handle(FixtureImporter $importer,FixtureAnalysisService $analysis,MarketGrader $grader): int {
        $date=$this->argument('date') ?: now()->toDateString();
        $count=$importer->importDate($date); $analysed=0; $graded=0;
        foreach(Fixture::with(['homeTeam','awayTeam','competition'])->whereDate('kickoff_at',$date)->get() as $fixture){
            if(in_array($fixture->status,['FT','AET','PEN'],true)) { $fixture->loadMissing(['homeTeam','awayTeam']); foreach($fixture->analyses()->whereNull('result')->get() as $item){ $result=$grader->grade($item); if($result){$item->update(['result'=>$result,'graded_at'=>now()]); $graded++;} } continue; }
            if(in_array($fixture->status,['CANC','PST'],true)) continue;
            $analysed += count($analysis->analyse($fixture));
        }
        $this->info("{$date}: synced {$count}, generated/updated {$analysed} analyses, graded {$graded} selections.");
        return self::SUCCESS;
    }
}
