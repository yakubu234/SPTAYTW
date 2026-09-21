<?php
namespace App\Services\Racing;
use App\Models\RacingRace;
class RacingGrader {
    public function __construct(private RacingApiClient $api){}
    public function gradeDate(string $date): int {
        $payload=$this->api->results($date); $rows=$payload['results'] ?? $payload['data'] ?? []; $graded=0;
        foreach($rows as $row){
            $race=RacingRace::where('provider_id',(string)($row['race_id'] ?? $row['id'] ?? ''))->with(['runners','analyses'])->first();
            if(!$race) continue;
            foreach(($row['runners'] ?? $row['results'] ?? []) as $rr){
                $runner=$race->runners->firstWhere('provider_id',(string)($rr['horse_id'] ?? $rr['id'] ?? '')); if(!$runner) continue;
                $pos=(int)($rr['position'] ?? $rr['finish_position'] ?? 0); if($pos<1) continue;
                $runner->update(['finish_position'=>$pos]);
                $analysis=$race->analyses->firstWhere('runner_id',$runner->id); if(!$analysis) continue;
                $analysis->update(['actual_finish'=>$pos,'win_hit'=>$pos===1,'place_hit'=>$pos<=3]); $graded++;
            }
        } return $graded;
    }
}