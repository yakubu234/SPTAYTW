<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RacingAnalysisContractTest extends TestCase
{
    public function test_racing_engine_is_conservative_by_contract(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/Racing/RacingAnalysisService.php');

        $this->assertStringContainsString("in_array(\$confidence,['C','D'],true)", $source);
        $this->assertStringContainsString('minimum_data_quality', $source);
        $this->assertStringContainsString('edge>=2', $source);
    }
}
