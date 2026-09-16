<?php
namespace App\DTOs\Football;
use App\Enums\AnalysisStatus;
use App\Enums\MarketType;
final readonly class MarketAnalysisResult {
    public function __construct(
        public MarketType $market,
        public string $selection,
        public int $score,
        public int $dataQuality,
        public AnalysisStatus $status,
        public array $positiveSignals,
        public array $contradictions,
        public string $mainRisk,
    ) {}
}
