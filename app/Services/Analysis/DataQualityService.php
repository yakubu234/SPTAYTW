<?php
namespace App\Services\Analysis;
use App\DTOs\Football\TeamGoalEvidence;
class DataQualityService { public function score(TeamGoalEvidence $e): int { $score=match(true){$e->sampleSize>=10=>95,$e->sampleSize>=7=>85,$e->sampleSize>=5=>72,default=>45}; if($e->highVarianceCompetition)$score-=20; return max(0,min(100,$score)); } }
