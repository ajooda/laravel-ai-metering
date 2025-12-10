<?php

namespace Ajooda\AiMetering\Tests\Unit\Services;

use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Tests\TestCase;

class CostCalculatorTest extends TestCase
{
    protected CostCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new CostCalculator;

        config([
            'ai-metering.providers.openai.models.gpt-4o-mini' => [
                'input_price_per_1k' => 0.00015,
                'output_price_per_1k' => 0.00060,
            ],
        ]);
    }

    public function test_calculates_cost_from_input_and_output_tokens(): void
    {
        $result = $this->calculator->calculate(
            'openai',
            'gpt-4o-mini',
            inputTokens: 1000,
            outputTokens: 500
        );

        $this->assertEquals(0.00015, $result['input_cost']);
        $this->assertEquals(0.00030, $result['output_cost']); // 500 * 0.00060 / 1000
        $this->assertEquals(0.00045, $result['total_cost']);
    }

    public function test_calculates_cost_from_total_tokens_only(): void
    {
        $result = $this->calculator->calculate(
            'openai',
            'gpt-4o-mini',
            totalTokens: 1500
        );

        $this->assertGreaterThan(0, $result['input_cost']);
        $this->assertGreaterThan(0, $result['output_cost']);
        $this->assertGreaterThan(0, $result['total_cost']);
    }

    public function test_handles_zero_tokens(): void
    {
        $result = $this->calculator->calculate(
            'openai',
            'gpt-4o-mini',
            inputTokens: 0,
            outputTokens: 0
        );

        $this->assertEquals(0.0, $result['input_cost']);
        $this->assertEquals(0.0, $result['output_cost']);
        $this->assertEquals(0.0, $result['total_cost']);
    }

    public function test_handles_missing_model_config(): void
    {
        $result = $this->calculator->calculate(
            'openai',
            'unknown-model',
            inputTokens: 1000,
            outputTokens: 500
        );

        $this->assertEquals(0.0, $result['input_cost']);
        $this->assertEquals(0.0, $result['output_cost']);
        $this->assertEquals(0.0, $result['total_cost']);
    }

    public function test_calculates_from_usage_array_with_costs(): void
    {
        $usage = [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'input_tokens' => 1000,
            'output_tokens' => 500,
            'input_cost' => 0.00015,
            'output_cost' => 0.00030,
            'total_cost' => 0.00045,
        ];

        $result = $this->calculator->calculateFromUsage($usage);

        $this->assertEquals(0.00015, $result['input_cost']);
        $this->assertEquals(0.00030, $result['output_cost']);
        $this->assertEquals(0.00045, $result['total_cost']);
    }

    public function test_calculates_from_usage_array_without_costs(): void
    {
        $usage = [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ];

        $result = $this->calculator->calculateFromUsage($usage);

        $this->assertGreaterThan(0, $result['total_cost']);
    }
}
