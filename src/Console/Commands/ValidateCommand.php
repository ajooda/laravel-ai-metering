<?php

namespace Ajooda\AiMetering\Console\Commands;

use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Models\AiUsage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-metering:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate configuration and data integrity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Validating AI Metering configuration and data...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        $this->info('Checking configuration...');
        $defaultProvider = config('ai-metering.default_provider');
        if (! config("ai-metering.providers.{$defaultProvider}")) {
            $errors[] = "Default provider '{$defaultProvider}' is not configured.";
        }

        $this->info('Checking database...');
        try {
            DB::connection(config('ai-metering.storage.connection'))->getPdo();
        } catch (\Exception $e) {
            $errors[] = "Database connection failed: {$e->getMessage()}";
        }

        $this->info('Checking data integrity...');

        $orphanedSubscriptions = AiSubscription::where('billing_mode', 'plan')
            ->whereNull('ai_plan_id')
            ->count();
        if ($orphanedSubscriptions > 0) {
            $warnings[] = "Found {$orphanedSubscriptions} plan mode subscriptions with missing plans.";
        }

        $creditsWithoutPlan = AiSubscription::where('billing_mode', 'credits')
            ->whereNull('ai_plan_id')
            ->count();
        if ($creditsWithoutPlan > 0) {
            $errors[] = "Found {$creditsWithoutPlan} credits mode subscriptions without plans. Credits mode subscriptions must have a plan.";
        }

        $orphanedUsage = AiUsage::whereNull('billable_type')
            ->orWhereNull('billable_id')
            ->count();
        if ($orphanedUsage > 0) {
            $warnings[] = "Found {$orphanedUsage} usage records without billable entity.";
        }

        $this->newLine();

        if (empty($errors) && empty($warnings)) {
            $this->info('✓ All validations passed!');
        } else {
            if (! empty($errors)) {
                $this->error('Errors found:');
                foreach ($errors as $error) {
                    $this->error("  ✗ {$error}");
                }
                $this->newLine();
            }

            if (! empty($warnings)) {
                $this->warn('Warnings:');
                foreach ($warnings as $warning) {
                    $this->warn("  ⚠ {$warning}");
                }
            }
        }

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
