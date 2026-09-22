<?php
namespace App\Services\Racing;
use App\Models\{RacingAnalysis,RacingRace,RacingRunner};

class RacingAnalysisService {
    public function analyse(RacingRace $race): int {
        $runners=$race->runners->where('non_runner',false)->values(); if($runners->count()<2) return 0;
        $raw=$runners->mapWithKeys(fn($r)=>[$r->id=>$this->rawScore($r)]); $sum=max(0.001,$raw->sum());
        $probs=$raw->map(fn($v)=>$v/$sum); $sorted=$probs->sortDesc()->values();
        $separation=($sorted[0]??0)-($sorted[1]??0);
        $confidence=$this->raceConfidence($separation,$runners->count());
        foreach($runners as $r){
            $win=$probs[$r->id]; $place=min(.97,$win*1.65 + .08);
            $market=$r->decimal_odds ? 1/(float)$r->decimal_odds : null;
            $edge=$market!==null ? ($win-$market)*100 : null;
            $quality=$this->quality($r); $score=(int)round(($win*100*.55)+($quality*.45));
            $status=$this->status($confidence,$quality,$score,$edge);
            RacingAnalysis::updateOrCreate(['race_id'=>$race->id,'runner_id'=>$r->id],[
                'win_probability'=>round($win*100,3),'place_probability'=>round($place*100,3),'fair_odds'=>round(1/$win,3),
                'market_probability'=>$market!==null?round($market*100,3):null,'edge_points'=>$edge!==null?round($edge,3):null,
                'score'=>$score,'data_quality'=>$quality,'race_confidence'=>$confidence,'status'=>$status,
                'evidence'=>['official_rating'=>$r->official_rating,'speed_rating'=>$r->speed_rating,'performance_rating'=>$r->performance_rating,'field_size'=>$runners->count()]
            ]);
        } return $runners->count();
    }
    private function rawScore(RacingRunner $r): float {
        $or=(float)($r->official_rating ?? 50); $speed=(float)($r->speed_rating ?? $or); $perf=(float)($r->performance_rating ?? $or);
        return max(1,($or*.35)+($speed*.35)+($perf*.30));
    }
    private function quality(RacingRunner $r): int {
        $fields=[$r->official_rating,$r->speed_rating,$r->performance_rating,$r->jockey,$r->trainer,$r->decimal_odds];
        return (int)round(count(array_filter($fields,fn($v)=>$v!==null&&$v!==''))/count($fields)*100);
    }
    private function raceConfidence(float $gap,int $field): string {
        if($field>config('racing.thresholds.maximum_field_size',18)) return 'D';
        return match(true){$gap>=.22=>'A',$gap>=.12=>'B',$gap>=.06=>'C',default=>'D'};
    }
    private function status(string $confidence,int $quality,int $score,?float $edge): string {
        if($quality<config('racing.thresholds.minimum_data_quality',45)||in_array($confidence,['C','D'],true)) return 'skip';
        if($score>=config('racing.thresholds.strong',82)&&($edge===null||$edge>=2)) return 'strong_qualified';
        if($score>=config('racing.thresholds.qualified',72)&&($edge===null||$edge>=0)) return 'qualified';
        return 'watch';
    }
}