<?php
namespace App\Enums;
enum AnalysisStatus: string { case STRONG='strong_qualified'; case QUALIFIED='qualified'; case WATCH='watch'; case SKIP='skip'; }
