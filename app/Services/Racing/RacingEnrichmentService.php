<?php

namespace App\Services\Racing;

use App\Models\RacingRunner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RacingEnrichmentService
{
     public function __construct(private RacingApiClient $api) {}

    public function enrichRunner(RacingRunner $runner, string $raceDate): array
    {
         $days = (int) config('racing.enrichment.history_days', 730);
        $limit = (int) config('racing.enrichment.history_limit', 10);
        $hours = (int) config('racing.enrichment.cache_hours', 12);
        $end = Carbon::parse($raceDate)->subDay()->toDateString();
        $start = Carbon::parse($end)->subDays($days)->toDateString();

        $history = Cache::remember(
            "racing:history:{$runner->provider_id}:{$start}:{$end}",
            now()->addHours($hours),
            fn () => $this->api->racecardHorseResults($runner->provider_id, [
                'start_date' => $start,
                'end_date' => $end,
                'limit' => $limit,
            ])
        );

        $distance = [];
        try {
            $distance = Cache::remember(
                "racing:distance-times:{$runner->provider_id}:{$start}:{$end}",
                now()->addHours($hours),
                fn () => $this->api->horseDistanceTimes($runner->provider_id, [
                    'start_date' => $start,
                    'end_date' => $end,
                ])
            );
        } catch (Throwable) {
            // History remains usable if optional distance analysis is unavailable.
        }

        return $this->summarise($history, $distance, $limit, (string) $runner->provider_id);
    }

    private function summarise(array $historyPayload, array $distancePayload, int $limit, string $horseId): array
    {
        $rows = array_slice($historyPayload['results'] ?? [], 0, $limit);
        $finishes = [];
        $recentRuns = [];

        foreach ($rows as $raceResult) {
            // The Basic horse-history endpoint returns races; the horse's finish
            // is nested inside each raceResult.runners[].
            $runnerResult = collect($raceResult['runners'] ?? [])->first(
                fn ($item) => (string) ($item['horse_id'] ?? '') !== ''
                    && (string) ($item['horse_id'] ?? '') === $horseId
            );

            if (!$runnerResult) {
                continue;
            }

            $position = $this->position($runnerResult['position'] ?? null);
            if ($position !== null) {
                $finishes[] = $position;
            }

            $recentRuns[] = [
                'date' => $raceResult['date'] ?? null,
                'course' => $raceResult['course'] ?? null,
                'position' => $position,
                'field_size' => count($raceResult['runners'] ?? []),
                'distance_yards' => is_numeric($raceResult['dist_y'] ?? null) ? (int) $raceResult['dist_y'] : null,
                'going' => $raceResult['going'] ?? null,
                'surface' => $raceResult['surface'] ?? null,
                'class' => $raceResult['class'] ?? null,
                'starting_price' => is_numeric($runnerResult['sp_dec'] ?? null) ? (float) $runnerResult['sp_dec'] : null,
            ];
        }

        $runs = count($recentRuns);
        $ratedRuns = count($finishes);
        $nonFinishes = max(0, $runs - $ratedRuns);
        $wins = count(array_filter($finishes, fn ($p) => $p === 1));
        $top3 = count(array_filter($finishes, fn ($p) => $p <= 3));
        $avgFinish = $ratedRuns ? array_sum($finishes) / $ratedRuns : null;
        $completionRate = $runs ? $ratedRuns / $runs : null;

        // Recent form should not treat PU/F/UR/etc. as if the run never happened.
        // Score each run by finishing percentile; a non-finish scores zero.
        $recentFormWeighted = 0.0;
        $recentWeight = 0.0;
        foreach (array_slice($recentRuns, 0, 5) as $index => $run) {
            $weight = 5 - $index;
            $field = max(2, (int) ($run['field_size'] ?? 0));
            $position = $run['position'] ?? null;
            $runScore = $position !== null
                ? max(0, min(100, (($field - (int)$position) / ($field - 1)) * 100))
                : 0;
            $recentFormWeighted += $runScore * $weight;
            $recentWeight += $weight;
        }
        $recentFormScore = $recentWeight > 0 ? $recentFormWeighted / $recentWeight : null;

        return [
            'history_runs' => $runs,
            'rated_history_runs' => $ratedRuns,
            'wins' => $wins,
            'top3' => $top3,
            'win_rate' => $runs ? round($wins / $runs, 4) : null,
            'top3_rate' => $runs ? round($top3 / $runs, 4) : null,
            'average_finish' => $avgFinish !== null ? round($avgFinish, 2) : null,
            'non_finishes' => $nonFinishes,
            'completion_rate' => $completionRate !== null ? round($completionRate, 4) : null,
            'recent_form_score' => $recentFormScore !== null ? round($recentFormScore, 2) : null,
            'recent_runs' => $recentRuns,
            'distance_total_runs' => is_numeric($distancePayload['total_runs'] ?? null)
                ? (int) $distancePayload['total_runs'] : null,
            'distance_bands' => count($distancePayload['distances'] ?? []),
        ];
    }

    private function position(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $position = (int) $value;
            return $position > 0 ? $position : null;
        }

        if (is_string($value) && preg_match('/^(\\d+)/', trim($value), $match)) {
            return (int) $match[1];
        }

        return null;
    }
}
