<?php
namespace App\Services\Football;
class CalibrationGuard { public function __construct(private int $minimumSample=50){} public function assess(int $sampleSize): array { return ['sample_size'=>$sampleSize,'minimum_sample'=>$this->minimumSample,'usable'=>$sampleSize >= $this->minimumSample,'message'=>$sampleSize >= $this->minimumSample?'Sample is large enough for descriptive calibration.':'Insufficient sample: do not change thresholds automatically.']; } }
