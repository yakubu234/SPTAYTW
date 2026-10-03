<?php

namespace Tests\Unit;

use App\Services\Racing\RacingPlaceEvidence;
use PHPUnit\Framework\TestCase;

class RacingPlaceEvidenceTest extends TestCase
{
    public function test_top_three_and_top_four_are_independent_review_flags(): void
    {
        $result = (new RacingPlaceEvidence)->assess([
            'history_runs' => 10, 'rated_history_runs' => 10,
            'top3' => 6, 'top4' => 9, 'recent_form_score' => 80,
        ], 90, 10);
        $this->assertSame('NO BET', $result['top3_status']);
        $this->assertSame('REVIEW', $result['top4_status']);
        $this->assertSame(0.6, $result['top3_rate']);
        $this->assertSame(0.9, $result['top4_rate']);
    }

    public function test_missing_top_four_or_insufficient_history_cannot_qualify(): void
    {
        $result = (new RacingPlaceEvidence)->assess([
            'history_runs' => 4, 'rated_history_runs' => 4,
            'top3' => 4, 'recent_form_score' => 95,
        ], 100, 10);
        $this->assertSame('NO BET', $result['top3_status']);
        $this->assertSame('NO BET', $result['top4_status']);
        $this->assertNull($result['top4_rate']);
    }

    public function test_non_finishes_are_in_denominator_and_bad_counts_are_rejected(): void
    {
        $audit = new RacingPlaceEvidence;
        $result = $audit->assess([
            'history_runs' => 10, 'rated_history_runs' => 8,
            'top3' => 7, 'top4' => 8, 'recent_form_score' => 80,
        ], 90, 10);
        $this->assertSame(0.7, $result['top3_rate']);
        $this->assertSame(0.8, $result['top4_rate']);
        $bad = $audit->assess([
            'history_runs' => 10, 'rated_history_runs' => 8,
            'top3' => 9, 'top4' => 8, 'recent_form_score' => 80,
        ], 90, 10);
        $this->assertSame('NO BET', $bad['top3_status']);
        $this->assertSame('NO BET', $bad['top4_status']);
    }
}
