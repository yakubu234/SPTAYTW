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
        $this->assertStringContainsString('minimum_recent_form', $source);
        $this->assertStringContainsString("rated_history_runs']??0)<3", $source);

        // Market-value qualification still requires a known edge.
        $this->assertStringContainsString('if($edge===null)', $source);
        $this->assertStringContainsString("return 'strong';", $source);
        $this->assertStringContainsString("return 'candidate';", $source);
        $this->assertStringContainsString("&&\$edge>=2) return 'strong_qualified'", $source);
        $this->assertStringContainsString("&&\$edge>=0) return 'qualified'", $source);
        $this->assertStringContainsString('$relativeStrength', $source);
        $this->assertStringNotContainsString('(\$win*100*.45)', $source);
    }
}
