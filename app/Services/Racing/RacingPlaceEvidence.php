<?php

namespace App\Services\Racing;

/**
 * Descriptive place-market audit only. This is NOT a calibrated probability
 * or a staking recommendation. All rates use recorded runs as denominator,
 * including non-finishes; missing history is never treated as success.
 */
class RacingPlaceEvidence
{
    public function assess(array $evidence, int $quality, int $fieldSize): array
    {
        $runs = max(0, (int) ($evidence['history_runs'] ?? 0));
        $rated = max(0, (int) ($evidence['rated_history_runs'] ?? 0));
        $top3 = max(0, (int) ($evidence['top3'] ?? 0));
        $top4 = max(0, (int) ($evidence['top4'] ?? 0));
        $form = $evidence['recent_form_score'] ?? null;
        $issues = [];
        if ($runs < 5 || $rated < 5) $issues[] = 'Insufficient completed history (minimum five)';
        if ($runs > 0 && $rated < $runs) $issues[] = 'Historical non-finishes: '.($runs - $rated);
        if ($quality < 55) $issues[] = 'Low data quality';
        if ($form === null) $issues[] = 'Recent form unavailable';
        elseif ((float) $form < 50) $issues[] = 'Recent form below review threshold';
        if ($fieldSize < 4) $issues[] = 'Field size below four: verify bookmaker place terms';
        if ($fieldSize > 18) $issues[] = 'Large field: review required';
        if ($top3 > $top4 || $top4 > $rated || $rated > $runs) $issues[] = 'Inconsistent historical counts';
        if (!array_key_exists('top4', $evidence)) $issues[] = 'Top-4 history missing';
        $baseReview = $runs >= 5 && $rated >= 5 && $quality >= 55
            && $form !== null && (float) $form >= 50 && $fieldSize >= 4
            && $fieldSize <= 18 && $top3 <= $top4 && $top4 <= $rated && $rated <= $runs;
        return [
            'runs' => $runs, 'completed' => $rated,
            'top3' => $top3, 'top4' => $top4,
            'top3_rate' => $runs ? $top3 / $runs : null,
            'top4_rate' => $runs && array_key_exists('top4', $evidence) ? $top4 / $runs : null,
            'top3_status' => $baseReview && $top3 / max(1, $runs) >= .7 ? 'REVIEW' : 'NO BET',
            'top4_status' => $baseReview && array_key_exists('top4', $evidence)
                && $top4 / max(1, $runs) >= .8 ? 'REVIEW' : 'NO BET',
            'issues' => $issues,
        ];
    }
}
