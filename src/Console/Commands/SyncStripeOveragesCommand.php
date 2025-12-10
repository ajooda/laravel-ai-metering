<?php

namespace Ajooda\AiMetering\Console\Commands;

use Ajooda\AiMetering\Console\Concerns\ResolvesBillable;
use Ajooda\AiMetering\Models\AiOverage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncStripeOveragesCommand extends Command
{
    use ResolvesBillable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-metering:sync-stripe-overages 
                            {--limit=100 : Maximum number of overages to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync pending overages to Stripe';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! class_exists(\Laravel\Cashier\Cashier::class)) {
            $this->error('Laravel Cashier is not installed. Cannot sync overages to Stripe.');

            return Command::FAILURE;
        }

        if (! class_exists(\Stripe\Stripe::class)) {
            $this->error('Stripe PHP SDK is not installed. Cannot sync overages to Stripe.');

            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');

        $overages = AiOverage::whereNull('synced_at')
            ->limit($limit)
            ->get();

        if ($overages->isEmpty()) {
            $this->info('No pending overages to sync.');

            return Command::SUCCESS;
        }

        $this->info("Syncing {$overages->count()} overages to Stripe...");
        $this->newLine();

        $synced = 0;
        $failed = 0;

        \Stripe\Stripe::setApiKey(config('cashier.secret'));

        $bar = $this->output->createProgressBar($overages->count());
        $bar->start();

        foreach ($overages as $overage) {
            try {
                $billable = $overage->billable;

                if (! $billable || ! method_exists($billable, 'asStripeCustomer')) {
                    $this->newLine();
                    $billableLabel = $billable ? $this->getBillableLabel($billable) : "Unknown ({$overage->billable_type} #{$overage->billable_id})";
                    $this->warn("Skipping overage {$overage->id} for {$billableLabel}: Billable does not implement Cashier interface.");
                    $failed++;
                    $bar->advance();

                    continue;
                }

                $billableLabel = $this->getBillableLabel($billable);
                $stripeCustomer = $billable->asStripeCustomer();
                $idempotencyKey = 'ai-overage-'.$overage->id.'-'.$overage->created_at->timestamp;

                $invoiceItem = \Stripe\InvoiceItem::create([
                    'customer' => $stripeCustomer->id,
                    'amount' => (int) ($overage->cost * 100),
                    'currency' => $overage->currency,
                    'description' => "AI Usage Overage - {$overage->tokens} tokens ({$overage->period_start->format('Y-m-d')} to {$overage->period_end->format('Y-m-d')})",
                ], [
                    'idempotency_key' => $idempotencyKey,
                ]);

                $overage->markAsSynced($invoiceItem->id);
                $synced++;
            } catch (\Exception $e) {
                $this->newLine();
                $billableLabel = $billable ? $this->getBillableLabel($billable) : "Unknown ({$overage->billable_type} #{$overage->billable_id})";
                $this->error("Failed to sync overage {$overage->id} for {$billableLabel}: {$e->getMessage()}");
                if (config('ai-metering.logging.log_failures', true)) {
                    Log::error('Failed to sync overage to Stripe', [
                        'overage_id' => $overage->id,
                        'billable_type' => $overage->billable_type,
                        'billable_id' => $overage->billable_id,
                        'error' => $e->getMessage(),
                    ]);
                }
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully synced {$synced} overages to Stripe.");
        if ($failed > 0) {
            $this->warn("Failed to sync {$failed} overages. Check logs for details.");
        }

        return Command::SUCCESS;
    }
}
