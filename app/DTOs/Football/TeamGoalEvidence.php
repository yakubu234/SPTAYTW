<?php
namespace App\DTOs\Football;
final readonly class TeamGoalEvidence {
    public function __construct(
        public int $sampleSize,
        public float $teamScoredRate,
        public float $venueScoredRate,
        public float $opponentConcededRate,
        public float $opponentVenueConcededRate,
        public float $opponentCleanSheetRate,
        public float $teamFailedToScoreRate,
        public bool $majorAttackerMissing = false,
        public bool $rotationRisk = false,
        public bool $highVarianceCompetition = false,
    ) {}
}
