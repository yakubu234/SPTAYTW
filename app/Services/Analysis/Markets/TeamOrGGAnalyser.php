<?php
namespace App\Services\Analysis\Markets;
use App\DTOs\Football\{MatchEvidence,MarketAnalysisResult};
use App\Enums\MarketType;
use App\Services\Analysis\MarketStatusResolver;
class TeamOrGGAnalyser { public function __construct(private MarketStatusResolver $resolver){} public function analyse(MatchEvidence $e,bool $home): MarketAnalysisResult { $teamResult=$home?$e->homeAvoidLossRate:$e->awayAvoidLossRate;$btts=$e->bttsRate;$weak=min($teamResult,$btts);$score=(int)round(20+$teamResult*30+$btts*25+$weak*20-($e->highVarianceCompetition?15:0));$score=max(0,min(100,$score));$side=$home?'Home':'Away';$c=[];if($teamResult<.65)$c[]='Result-protection route is not independently strong.';if($btts<.55)$c[]='GG route is not independently strong.';if($e->highVarianceCompetition)$c[]='High-variance competition.';$p=[];if($teamResult>=.75)$p[]="$side result route has strong recent support.";if($btts>=.65)$p[]='GG route has strong recent support.';return new MarketAnalysisResult(MarketType::TEAM_OR_GG,"$side or GG",$score,$e->dataQuality,$this->resolver->resolve($score,$e->dataQuality,88,92),$p,$c,$c[0]??'Both independent routes fail together.'); } }
