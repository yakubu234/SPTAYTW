<?php

namespace App\Services\Football;

use App\Models\Fixture;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class EarlyGoalEventService
{
    /** Null means the provider did not supply a complete, auditable timeline. */
    public function fetch(Fixture $fixture): ?bool
    {
        if ($fixture->early_goal_events_checked_at !== null) return $fixture->first_five_goal;
        if ($fixture->status !== 'FT' || $fixture->home_goals === null || $fixture->away_goals === null) return null;

        $key = config('football.api_football.key');
        if (!$key) throw new RuntimeException('API_FOOTBALL_KEY is not configured.');

        $payload = Http::baseUrl(config('football.api_football.base_url'))
            ->withHeaders(['x-apisports-key' => $key])
            ->timeout(config('football.api_football.timeout', 15))
            ->retry(2, 300)
            ->get('/fixtures/events', ['fixture' => $fixture->provider_id])
            ->throw()->json();

        if (!empty($payload['errors'])) {
            throw new RuntimeException('API-Football events error: '.json_encode($payload['errors']));
        }

        $classification = self::classify($payload['response'] ?? [], (int) $fixture->home_goals + (int) $fixture->away_goals);
        $fixture->update([
            'first_five_goal' => $classification['complete'] ? $classification['early_minute'] !== null : null,
            'first_five_goal_minute' => $classification['early_minute'],
            'early_goal_events_checked_at' => now(),
        ]);

        return $fixture->first_five_goal;
    }

    public static function classify(array $events, int $totalGoals): array
    {
        $goals = [];
        foreach ($events as $event) {
            if (($event['type'] ?? null) !== 'Goal'
                || !in_array($event['detail'] ?? null, ['Normal Goal', 'Own Goal', 'Penalty'], true)) continue;
            $minute = $event['time']['elapsed'] ?? null;
            if (!is_int($minute) || $minute < 0) {
                $goals[] = null;
            } else {
                $goals[] = $minute;
            }
        }

        $complete = count($goals) === $totalGoals && !in_array(null, $goals, true);
        $first = $complete && $goals ? min($goals) : null;
        return ['complete' => $complete, 'early_minute' => $first !== null && $first <= 5 ? $first : null];
    }
}
