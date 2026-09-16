<?php

namespace App\Services\Analysis;

use App\Enums\AnalysisStatus;

class MarketStatusResolver
{
    public function __construct(private int $minimumDataQuality = 55) {}

    public function resolve(int $score, int $quality, int $qualified = 80, int $strong = 85): AnalysisStatus
    {
        if ($quality < $this->minimumDataQuality) {
            return AnalysisStatus::SKIP;
        }

        if ($score >= $strong && $quality >= 70) {
            return AnalysisStatus::STRONG;
        }

        if ($score >= $qualified) {
            return AnalysisStatus::QUALIFIED;
        }

        if ($score >= 70) {
            return AnalysisStatus::WATCH;
        }

        return AnalysisStatus::SKIP;
    }
}
