<?php
namespace App\Services\Analysis\Markets;
use App\DTOs\Football\{MatchEvidence,MarketAnalysisResult};
use App\Enums\MarketType;
use App\Services\Analysis\MarketStatusResolver;
class Under45Analyser { public function __construct(private MarketStatusResolver $resolver){} public function analyse(MatchEvidence $e): MarketAnalysisResult { $score=(int)round(30+$e->under45Rate*40+$e->venueUnder45Rate*20-$e->fivePlusRate*25-($e->highVarianceCompetition?12:0));$score=max(0,min(100,$score));$c=[];if($e->fivePlusRate>=.2)$c[]='5+ goals occur too frequently in the recent sample.';if($e->highVarianceCompetition)$c[]='High-variance competition.';$p=[];if($e->under45Rate>=.8)$p[]='At least 80% of recent matches stayed under 4.5.';return new MarketAnalysisResult(MarketType::UNDER_45,'Under 4.5',$score,$e->dataQuality,$this->resolver->resolve($score,$e->dataQuality),$p,$c,$c[0]??'Extreme scoring event.'); } }
