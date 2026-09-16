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
 ) {}
}
