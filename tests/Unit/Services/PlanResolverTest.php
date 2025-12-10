<?php

namespace Ajooda\AiMetering\Tests\Unit\Services;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Services\PlanResolver;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlanResolverTest extends TestCase
{
    use RefreshDatabase;

    protected PlanResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new PlanResolver;
    }

    public function test_resolves_active_subscription(): void
    {
        $plan = AiPlan::factory()->create(['is_active' => true]);
        $user = $this->createUser();

        $subscription = AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        $resolved = $this->resolver->resolveSubscription($user);

        $this->assertNotNull($resolved);
        $this->assertEquals($subscription->id, $resolved->id);
    }

    public function test_resolves_active_plan(): void
    {
        $plan = AiPlan::factory()->create(['is_active' => true]);
        $user = $this->createUser();

        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        $resolved = $this->resolver->resolvePlan($user);

        $this->assertNotNull($resolved);
        $this->assertEquals($plan->id, $resolved->id);
    }

    public function test_returns_null_for_expired_subscription(): void
    {
        $plan = AiPlan::factory()->create();
        $user = $this->createUser();

        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => now()->subDay(),
        ]);

        $resolved = $this->resolver->resolveSubscription($user);

        $this->assertNull($resolved);
    }

    public function test_returns_null_when_no_subscription(): void
    {
        $user = $this->createUser();

        $resolved = $this->resolver->resolveSubscription($user);

        $this->assertNull($resolved);
    }

    public function test_gets_billing_mode(): void
    {
        $plan = AiPlan::factory()->create();
        $user = $this->createUser();

        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'billing_mode' => 'credits',
            'ends_at' => null,
        ]);

        $mode = $this->resolver->getBillingMode($user);

        $this->assertEquals('credits', $mode);
    }

    protected function createUser()
    {
        return new class
        {
            public $id = 1;

            public function getMorphClass(): string
            {
                return 'App\Models\User';
            }
        };
    }
}
