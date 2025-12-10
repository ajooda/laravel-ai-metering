<?php

namespace Ajooda\AiMetering\Console\Commands;

use Ajooda\AiMetering\Console\Concerns\ResolvesBillable;
use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Illuminate\Console\Command;

class MigratePlanCommand extends Command
{
    use ResolvesBillable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-metering:migrate-plan 
                            {billable-type : The billable type (e.g. App\\Models\\User)}
                            {billable-id : The billable ID}
                            {to-plan : The plan ID or slug to migrate to}
                            {--from-plan= : The current plan ID or slug (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate a billable entity from one plan to another';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $billableType = $this->argument('billable-type');
        $billableId = $this->argument('billable-id');
        $toPlan = $this->argument('to-plan');
        $fromPlan = $this->option('from-plan');

        [$success, $billable, $error] = $this->resolveBillable($billableType, $billableId);
        if (! $success) {
            return Command::FAILURE;
        }

        $billableLabel = $this->getBillableLabel($billable);

        $plan = is_numeric($toPlan)
            ? AiPlan::find($toPlan)
            : AiPlan::where('slug', $toPlan)->first();

        if (! $plan) {
            $this->error("Plan '{$toPlan}' not found.");

            return Command::FAILURE;
        }

        $subscription = AiSubscription::where('billable_type', $billableType)
            ->where('billable_id', $billableId)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->first();

        if (! $subscription) {
            $this->error("No active subscription found for {$billableLabel}.");

            return Command::FAILURE;
        }

        $subscription->load('plan');

        if ($fromPlan) {
            $currentPlan = is_numeric($fromPlan)
                ? AiPlan::find($fromPlan)
                : AiPlan::where('slug', $fromPlan)->first();

            if (! $currentPlan || $subscription->ai_plan_id !== $currentPlan->id) {
                $this->error("Current plan does not match '{$fromPlan}'. Current plan is '{$subscription->plan->name}'.");

                return Command::FAILURE;
            }
        }

        if ($subscription->ai_plan_id === $plan->id) {
            $this->warn("{$billableLabel} is already on plan '{$plan->name}'.");

            return Command::SUCCESS;
        }

        $this->info("Entity: {$billableLabel}");
        $this->info("Current Plan: {$subscription->plan->name}");
        $this->info("Target Plan: {$plan->name}");
        $this->newLine();

        if (! $this->confirm("Migrate {$billableLabel} from plan '{$subscription->plan->name}' to '{$plan->name}'?")) {
            $this->info('Migration cancelled.');

            return Command::SUCCESS;
        }

        $oldPlanId = $subscription->ai_plan_id;
        $subscription->update([
            'ai_plan_id' => $plan->id,
            'previous_plan_id' => $oldPlanId,
        ]);

        $this->info("Successfully migrated {$billableLabel} to plan '{$plan->name}'.");

        return Command::SUCCESS;
    }
}
