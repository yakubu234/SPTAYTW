<?php
namespace App\Services\Racing;
use App\Models\{RacingAnalysis,RacingRace,RacingRunner};

class RacingAnalysisService {
    public function analyse(RacingRace $race): int {
        $runners=$race->runners->where('non_runner',false)->values(); if($runners->count()<2) return 0;
        $raw=$runners->mapWithKeys(fn($r)=>[$r->id=>$this->rawScore($r)]); $sum=max(0.001,$raw->sum());
        $probs=$raw->map(fn($v)=>$v/$sum); $sorted=$probs->sortDesc()->values();
        $top=(float)($sorted[0]??0); $second=(float)($sorted[1]??0);
        $separation=$top-$second;
        // Relative separation is meaningful across different field sizes.
        // The previous absolute 6/12/22 percentage-point gaps made virtually
        // every normal multi-runner race confidence D.
        $relativeSeparation=$top>0 ? $separation/$top : 0;
        $confidence=$this->raceConfidence($relativeSeparation,$runners->count());
        foreach($runners as $r){
            $evidence=$this->evidence($r);
            $win=$probs[$r->id]; $place=min(.97,$win*1.65 + .08);
            $market=$r->decimal_odds ? 1/(float)$r->decimal_odds : null;
            $edge=$market!==null ? ($win-$market)*100 : null;
            $quality=$this->quality($r,$evidence);
            $form=$this->formScore($evidence);
            $score=(int)round(($win*100*.45)+($quality*.30)+($form*.25));
            $status=$this->status($confidence,$quality,$score,$edge,$evidence);
            RacingAnalysis::updateOrCreate(['race_id'=>$race->id,'runner_id'=>$r->id],[
                'win_probability'=>round($win*100,3),'place_probability'=>round($place*100,3),'fair_odds'=>round(1/$win,3),
                'market_probability'=>$market!==null?round($market*100,3):null,'edge_points'=>$edge!==null?round($edge,3):null,
                'score'=>$score,'data_quality'=>$quality,'race_confidence'=>$confidence,'status'=>$status,
                'evidence'=>array_merge(['official_rating'=>$r->official_rating,'speed_rating'=>$r->speed_rating,'performance_rating'=>$r->performance_rating,'field_size'=>$runners->count()],$evidence)
            ]);
        } return $runners->count();
    }
    private function rawScore(RacingRunner $r): float {
        $or=(float)($r->official_rating ?? 50); $speed=(float)($r->speed_rating ?? $or); $perf=(float)($r->performance_rating ?? $or);
        $form=$this->formScore($this->evidence($r));
        return max(1,($or*.28)+($speed*.28)+($perf*.24)+($form*.20));
    }
    private function evidence(RacingRunner $r): array {
        return $r->historical_evidence ?? [];
    }
    private function formScore(array $e): float {
        $runs=(int)($e['rated_history_runs']??0); if($runs<3) return 50;
        $top3=(float)($e['top3_rate']??0); $win=(float)($e['win_rate']??0);
        $avg=$e['average_finish']??null; $finish=$avg!==null?max(0,100-(((float)$avg-1)*12)):50;
        return min(100,max(0,($top3*100*.45)+($win*100*.25)+($finish*.30)));
    }
    private function quality(RacingRunner $r,array $e): int {
        $base=[$r->official_rating,$r->speed_rating,$r->performance_rating,$r->jockey,$r->trainer];
        $present=count(array_filter($base,fn($v)=>$v!==null&&$v!==''));
        $history=min(1,((int)($e['rated_history_runs']??0))/5);
        return (int)round((($present/count($base))*.65+$history*.35)*100);
    }
    private function raceConfidence(float $relativeGap,int $field): string {
        if($field>config('racing.thresholds.maximum_field_size',18)) return 'D';
        return match(true){
            $relativeGap>=.30=>'A',
            $relativeGap>=.20=>'B',
            $relativeGap>=.10=>'C',
            default=>'D'
        };
    }
    private function status(string $confidence,int $quality,int $score,?float $edge,array $e): string {
        if((int)($e['rated_history_runs']??0)<3) return 'skip';
        if($quality<config('racing.thresholds.minimum_data_quality',55)||in_array($confidence,['C','D'],true)) return 'skip';
        // Basic does not expose today's odds. In that case classify predictive
        // strength only; bookmaker-value qualification remains unavailable.
        if($edge===null){
            if($score>=config('racing.thresholds.strong',82)) return 'strong';
            if($score>=config('racing.thresholds.qualified',72)) return 'candidate';
            return 'watch';
        }
        if($score>=config('racing.thresholds.strong',82)&&$edge>=2) return 'strong_qualified';
        if($score>=config('racing.thresholds.qualified',72)&&$edge>=0) return 'qualified';
        return 'watch';
    }
}
