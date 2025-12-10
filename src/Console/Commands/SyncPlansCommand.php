<?php

namespace Ajooda\AiMetering\Console\Commands;

use Ajooda\AiMetering\Models\AiPlan;
use Illuminate\Console\Command;

class SyncPlansCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-metering:sync-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync plans (placeholder for future integration with billing systems)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing plans...');
        $this->newLine();

        $plans = AiPlan::where('is_active', true)->get();

        $this->info("Found {$plans->count()} active plans:");
        $this->newLine();

        $this->table(
            ['ID', 'Name', 'Slug', 'Token Limit', 'Cost Limit'],
            $plans->map(function ($plan) {
                return [
                    $plan->id,
                    $plan->name,
                    $plan->slug,
                    $plan->monthly_token_limit ? number_format($plan->monthly_token_limit) : 'Unlimited',
                    $plan->monthly_cost_limit ? '$'.number_format($plan->monthly_cost_limit, 2) : 'Unlimited',
                ];
            })->toArray()
        );

        $this->info('Plans synced successfully.');

        return Command::SUCCESS;
    }
}
