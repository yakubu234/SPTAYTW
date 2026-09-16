<?php
namespace App\DTOs\Football;
final readonly class MatchEvidence {
 public function __construct(
  public int $sampleSize,
  public float $over45Rate,
  public float $homeTeamOver45Rate,
  public float $awayTeamOver45Rate,
  public float $over15Rate,
  public float $homeWinRate,
  public float $awayWinRate,
  public float $homeRelevantWinRate,
  public float $awayRelevantWinRate,
  public float $homeScoredRate,
  public float $awayScoredRate,
  public float $homeConcededRate,
  public float $awayConcededRate,
  public float $bttsRate,
  public bool $rotationRisk=false,
  public bool $highVarianceCompetition=false,
  public float $over25Rate=0.0,
  public float $over35Rate=0.0,
  public float $averageTotalGoals=0.0,
  public float $homeRelevantOver35Rate=0.0,
  public float $awayRelevantOver35Rate=0.0,
  public float $homeRelevantAverageGoals=0.0,
  public float $awayRelevantAverageGoals=0.0,
 ) {}
}
