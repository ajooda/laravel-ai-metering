<?php

namespace Ajooda\AiMetering\Console\Commands;

use Ajooda\AiMetering\Models\AiUsage;
use Illuminate\Console\Command;

class CleanupOldUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-metering:cleanup 
                            {--days= : Number of days to keep (default from config)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete/prune old usage records beyond configured retention';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('ai-metering.storage.prune_after_days', 365);
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $query = AiUsage::where('occurred_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No old usage records to clean up.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} usage records older than {$days} days (before {$cutoffDate->format('Y-m-d')}).");
            $this->info('Run without --dry-run to actually delete the records.');

            return Command::SUCCESS;
        }

        if (! $this->confirm("This will delete {$count} usage records older than {$days} days. Continue?")) {
            $this->info('Cleanup cancelled.');

            return Command::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} usage records.");

        return Command::SUCCESS;
    }
}
