<?php

namespace Tests\Unit\Services\Analysis;

use App\Enums\AnalysisStatus;
use App\Services\Analysis\Markets\CornerAnalyser;
use PHPUnit\Framework\TestCase;

final class CornerAnalyserTest extends TestCase
{
    public function test_full_history_with_no_breaches_qualifies_both_corner_lines(): void
    {
        $evidence = $this->evidence(array_fill(1, 10, 9), array_fill(11, 10, 10));
        [$over, $under] = (new CornerAnalyser())->analyse($evidence);
        $this->assertSame(AnalysisStatus::STRONG, $over->status);
        $this->assertSame(AnalysisStatus::STRONG, $under->status);
        $this->assertSame('Under 18 Total Corners (0–17)', $under->selection);
    }

    public function test_past_extremes_are_counted_and_weaken_the_respective_line(): void
    {
        $evidence = $this->evidence([1 => 4, 2 => 18] + array_fill_keys(range(3, 10), 9), array_fill_keys(range(11, 20), 9));
        [$over, $under] = (new CornerAnalyser())->analyse($evidence);
        $this->assertNotSame(AnalysisStatus::STRONG, $over->status);
        $this->assertNotSame(AnalysisStatus::STRONG, $under->status);
        $this->assertStringContainsString('1/10 had 0–4 corners; 1/10 had 18+ corners', implode(' ', $over->positiveSignals));
        $this->assertCount(1, $under->contradictions);
    }

    public function test_absent_corner_data_never_qualifies(): void
    {
        [$over, $under] = (new CornerAnalyser())->analyse($this->evidence([], []));
        $this->assertSame(AnalysisStatus::SKIP, $over->status);
        $this->assertSame(AnalysisStatus::SKIP, $under->status);
        $this->assertSame(0, $over->dataQuality);
    }

    private function evidence(array $home, array $away): array
    {
        return ['sample' => ['home' => $home, 'away' => $away],
            'relevant' => ['home' => array_slice($home, 0, 5, true), 'away' => array_slice($away, 0, 5, true)],
            'unique' => $home + $away];
    }
}
