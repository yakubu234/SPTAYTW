<?php

namespace Tests\Unit;

use App\Enums\AnalysisStatus;
use App\Services\Football\EarlyGoalAnalyser;
use PHPUnit\Framework\TestCase;

final class EarlyGoalAnalyserTest extends TestCase
{
    public function test_no_goal_watch_requires_complete_history_and_matching_venues(): void
    {
        $allQuiet = array_fill_keys(range(1, 10), false);
        $awayQuiet = array_fill_keys(range(11, 20), false);
        $evidence = ['sample' => ['home' => $allQuiet, 'away' => $awayQuiet],
            'venue' => ['home' => array_slice($allQuiet, 0, 4, true),
                'away' => array_slice($awayQuiet, 0, 4, true)],
            'unique' => $allQuiet + $awayQuiet];
        self::assertSame(AnalysisStatus::WATCH, (new EarlyGoalAnalyser())->analyseNo($evidence)->status);

        $evidence['venue']['away'] = [];
        self::assertSame(AnalysisStatus::SKIP, (new EarlyGoalAnalyser())->analyseNo($evidence)->status);
    }
}
