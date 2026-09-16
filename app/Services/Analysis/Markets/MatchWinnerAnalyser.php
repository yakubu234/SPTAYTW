<?php
namespace App\Services\Analysis\Markets;
use App\DTOs\Football\{MatchEvidence,MarketAnalysisResult};
use App\Enums\MarketType;
use App\Services\Analysis\MarketStatusResolver;
class MatchWinnerAnalyser { public function __construct(private MarketStatusResolver $resolver){} public function analyse(MatchEvidence $e,bool $home): MarketAnalysisResult { $win=$home?$e->homeWinRate:$e->awayWinRate;$oppLoss=$home?$e->awayLossRate:$e->homeLossRate;$score=(int)round(20+$win*38+$oppLoss*25+abs($e->formDifferential)*12-($e->highVarianceCompetition?12:0));$score=max(0,min(100,$score));$selection=$home?'Home':'Away';$market=$home?MarketType::HOME:MarketType::AWAY;$c=[];if($win<.6)$c[]='Selected side does not have a strong recent win frequency.';if($e->highVarianceCompetition)$c[]='High-variance competition.';$p=[];if($win>=.7)$p[]='Selected side has won at least 70% of the relevant sample.';return new MarketAnalysisResult($market,$selection,$score,$e->dataQuality,$this->resolver->resolve($score,$e->dataQuality,84,90),$p,$c,$c[0]??'Draw or opponent win.'); } }
