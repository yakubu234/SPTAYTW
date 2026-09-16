<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Football\MatchEvidence;
use PHPUnit\Framework\TestCase;

final class MatchEvidenceCompatibilityTest extends TestCase
{
    public function test_existing_constructor_arguments_remain_compatible(): void
    {
        $evidence = new MatchEvidence(10, .1, .1, .1, .7, .5, .4, .5, .4, .8, .7, .6, .7, .55);

        $this->assertSame(10, $evidence->sampleSize);
        $this->assertSame(0.0, $evidence->over25Rate);
        $this->assertSame(0.0, $evidence->averageTotalGoals);
    }
}
