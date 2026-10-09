<?php

namespace App\Services\Analysis\Markets;

use App\DTOs\Football\MarketAnalysisResult;
use App\Enums\{AnalysisStatus, MarketType};

final class CornerAnalyser
{
    /** @return MarketAnalysisResult[] */
    public function analyse(array $e): array
    {
        return [
            $this->market($e, MarketType::CORNERS_OVER_45, 'Over 4.5 Total Corners', fn (int $total) => $total >= 5, fn (int $total) => $total <= 4),
            $this->market($e, MarketType::CORNERS_UNDER_18, 'Under 18 Total Corners (0–17)', fn (int $total) => $total < 18, fn (int $total) => $total >= 18),
        ];
    }

    private function market(array $e, MarketType $market, string $selection, callable $wins, callable $loses): MarketAnalysisResult
    {
        $home = array_values($e['sample']['home']);
        $away = array_values($e['sample']['away']);
        $unique = array_values($e['unique']);
        $homeRelevant = array_values($e['relevant']['home']);
        $awayRelevant = array_values($e['relevant']['away']);
        $opposing = count(array_filter($unique, $loses));
        $positive = [sprintf('Corner data: home team %d/10 recent matches, away team %d/10; %d distinct matches with totals.', count($home), count($away), count($unique))];
        foreach (['home' => $home, 'away' => $away] as $side => $sample) {
            $positive[] = sprintf('%s recent: %d/%d met %s; %d/%d had 0–4 corners; %d/%d had 18+ corners.', ucfirst($side), count(array_filter($sample, $wins)), count($sample), $selection, count(array_filter($sample, fn ($n) => $n <= 4)), count($sample), count(array_filter($sample, fn ($n) => $n >= 18)), count($sample));
        }
        $positive[] = sprintf('Venue sample: home %d, away %d complete matches.', count($homeRelevant), count($awayRelevant));
        $contradictions = [];
        if ($opposing) $contradictions[] = sprintf('%d distinct recent match(es) breached this corner line.', $opposing);
        if (count($home) < 7 || count($away) < 7) $contradictions[] = 'Fewer than seven recent matches per team have complete corner statistics.';
        if (count($homeRelevant) < 3 || count($awayRelevant) < 3) $contradictions[] = 'Venue-specific corner data is limited.';

        $quality = min(100, (int) round(100 * min(count($home), count($away)) / 10));
        if (count($homeRelevant) < 3 || count($awayRelevant) < 3) $quality = min($quality, 54);
        $homeRate = $home ? count(array_filter($home, $wins)) / count($home) : 0;
        $awayRate = $away ? count(array_filter($away, $wins)) / count($away) : 0;
        $venueRate = $homeRelevant && $awayRelevant
            ? min(count(array_filter($homeRelevant, $wins)) / count($homeRelevant), count(array_filter($awayRelevant, $wins)) / count($awayRelevant)) : 0;
        $score = (int) round(100 * (.4 * $homeRate + .4 * $awayRate + .2 * $venueRate));
        $score = min(100, max(0, $score));
        $status = AnalysisStatus::SKIP;
        if (count($home) >= 7 && count($away) >= 7 && count($homeRelevant) >= 3 && count($awayRelevant) >= 3) {
            if ($score >= 95 && $opposing === 0 && count($home) === 10 && count($away) === 10) $status = AnalysisStatus::STRONG;
            elseif ($score >= 90 && $opposing <= 1) $status = AnalysisStatus::QUALIFIED;
            elseif ($score >= 75) $status = AnalysisStatus::WATCH;
        }

        return new MarketAnalysisResult($market, $selection, $score, $quality, $status,
            $positive, $contradictions, $contradictions[0] ?? 'Historical corner totals do not guarantee the next match.');
    }
}
