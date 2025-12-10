<?php

namespace Ajooda\AiMetering\Http\Middleware;

use Ajooda\AiMetering\Contracts\TenantResolver;
use Ajooda\AiMetering\Services\UsageLimiter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceAiQuota
{
    public function __construct(
        protected UsageLimiter $usageLimiter,
        protected TenantResolver $tenantResolver
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $tenant = $this->tenantResolver->resolve($request);

        $billable = $tenant ?? $user;

        if (! $billable) {
            return $next($request);
        }

        $limitCheck = $this->usageLimiter->checkLimit($billable);

        if ($limitCheck->hardLimitReached) {
            $overageBehavior = config('ai-metering.billing.overage_behavior', 'block');

            if ($overageBehavior === 'block') {
                return $this->handleLimitExceeded($request, $limitCheck);
            }
        }

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-Remaining-Tokens', (string) ($limitCheck->remainingTokens ?? 'unlimited'));
            $response->headers->set('X-Remaining-Cost', (string) ($limitCheck->remainingCost ?? 'unlimited'));
            $response->headers->set('X-Usage-Percentage', (string) round($limitCheck->usagePercentage, 2));
        }

        return $response;
    }

    /**
     * Handle limit exceeded response.
     */
    protected function handleLimitExceeded(Request $request, $limitCheck): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'AI usage limit exceeded',
                'message' => 'You have reached your AI usage limit for this period.',
                'remaining_tokens' => $limitCheck->remainingTokens ?? 0,
                'remaining_cost' => $limitCheck->remainingCost ?? 0.0,
                'usage_percentage' => $limitCheck->usagePercentage,
            ], 429);
        }

        abort(429, 'AI usage limit exceeded. Please upgrade your plan or wait for the next billing period.');
    }
}
