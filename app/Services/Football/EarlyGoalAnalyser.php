<?php

namespace App\Services\Football;

use App\DTOs\Football\MarketAnalysisResult;
use App\Enums\{AnalysisStatus, MarketType};

final class EarlyGoalAnalyser
{
    public function analyse(array $evidence): MarketAnalysisResult
    {
        $home = $evidence['sample']['home'];
        $away = $evidence['sample']['away'];
        $homeVenue = $evidence['venue']['home'];
        $awayVenue = $evidence['venue']['away'];
        $unique = $evidence['unique'];
        $hits = count(array_filter($unique));
        $observed = count($unique);
        $quality = (int) round(100 * min(count($home), count($away)) / 10);
        $score = $observed ? (int) round(100 * ($hits + 1) / ($observed + 8)) : 0;
        $supported = count($home) >= 8 && count($away) >= 8
            && count($homeVenue) >= 3 && count($awayVenue) >= 3
            && $hits >= 3 && (count(array_filter($homeVenue)) + count(array_filter($awayVenue))) >= 1;

        $signals = [sprintf('Home team: %d/%d recent matches had a goal by minute 5; at home %d/%d.',
            count(array_filter($home)), count($home), count(array_filter($homeVenue)), count($homeVenue)),
            sprintf('Away team: %d/%d recent matches had a goal by minute 5; away %d/%d.',
                count(array_filter($away)), count($away), count(array_filter($awayVenue)), count($awayVenue))];
        $contradictions = [sprintf('%d/%d distinct matches had no goal in the first five minutes.',
            $observed - $hits, $observed)];
        if (count($home) < 8 || count($away) < 8) $contradictions[] = 'Fewer than eight complete recent event histories for at least one team.';
        if (count($homeVenue) < 3 || count($awayVenue) < 3) $contradictions[] = 'Limited matching home or away event history.';

        return new MarketAnalysisResult(MarketType::GOAL_FIRST_5, 'Goal in first 5 minutes — Yes',
            $score, $quality, $supported ? AnalysisStatus::WATCH : AnalysisStatus::SKIP,
            $signals, $contradictions,
            'An early goal is a rare timing event; recent observations do not guarantee one in this fixture.');
    }
}
