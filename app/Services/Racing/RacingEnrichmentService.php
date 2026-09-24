<?php

namespace App\Services\Racing;

use App\Models\RacingRunner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RacingEnrichmentService
{
    private ?string $currentHorseId = null;

    public function __construct(private RacingApiClient $api) {}

    public function enrichRunner(RacingRunner $runner, string $raceDate): array
    {
        $this->currentHorseId = (string) $runner->provider_id;
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

        return $this->summarise($history, $distance, $limit);
    }

    private function summarise(array $historyPayload, array $distancePayload, int $limit): array
    {
        $rows = array_slice($historyPayload['results'] ?? [], 0, $limit);
        $finishes = [];

        foreach ($rows as $raceResult) {
            // The Basic horse-history endpoint returns races; the horse's finish
            // is nested inside each raceResult.runners[].
            $runnerResult = collect($raceResult['runners'] ?? [])->first(
                fn ($item) => (string) ($item['horse_id'] ?? '') !== ''
                    && (string) ($item['horse_id'] ?? '') === (string) ($this->currentHorseId ?? '')
            );

            if (!$runnerResult) {
                continue;
            }

            $position = $this->position($runnerResult['position'] ?? null);
            if ($position !== null) {
                $finishes[] = $position;
            }
        }

        $runs = count($rows);
        $ratedRuns = count($finishes);
        $wins = count(array_filter($finishes, fn ($p) => $p === 1));
        $top3 = count(array_filter($finishes, fn ($p) => $p <= 3));
        $avgFinish = $ratedRuns ? array_sum($finishes) / $ratedRuns : null;

        return [
            'history_runs' => $runs,
            'rated_history_runs' => $ratedRuns,
            'wins' => $wins,
            'top3' => $top3,
            'win_rate' => $ratedRuns ? round($wins / $ratedRuns, 4) : null,
            'top3_rate' => $ratedRuns ? round($top3 / $ratedRuns, 4) : null,
            'average_finish' => $avgFinish !== null ? round($avgFinish, 2) : null,
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
