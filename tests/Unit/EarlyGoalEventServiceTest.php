<?php

namespace Tests\Unit;

use App\Services\Football\EarlyGoalEventService;
use PHPUnit\Framework\TestCase;

final class EarlyGoalEventServiceTest extends TestCase
{
    public function test_counted_goal_at_minute_five_is_early_but_minute_six_is_not(): void
    {
        $atFive = [['type' => 'Goal', 'detail' => 'Normal Goal', 'time' => ['elapsed' => 5]]];
        $atSix = [['type' => 'Goal', 'detail' => 'Penalty', 'time' => ['elapsed' => 6]]];
        self::assertSame(['complete' => true, 'early_minute' => 5], EarlyGoalEventService::classify($atFive, 1));
        self::assertSame(['complete' => true, 'early_minute' => null], EarlyGoalEventService::classify($atSix, 1));
    }

    public function test_missing_timeline_is_unknown_but_goalless_match_is_complete(): void
    {
        self::assertSame(['complete' => false, 'early_minute' => null], EarlyGoalEventService::classify([], 1));
        self::assertSame(['complete' => true, 'early_minute' => null], EarlyGoalEventService::classify([], 0));
    }

    public function test_missed_penalty_does_not_count_as_a_goal(): void
    {
        $events = [['type' => 'Goal', 'detail' => 'Missed Penalty', 'time' => ['elapsed' => 2]]];
        self::assertSame(['complete' => true, 'early_minute' => null], EarlyGoalEventService::classify($events, 0));
    }
}
