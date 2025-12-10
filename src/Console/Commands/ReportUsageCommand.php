<?php

namespace Ajooda\AiMetering\Console\Commands;

use Ajooda\AiMetering\Console\Concerns\ResolvesBillable;
use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Support\Period;
use Illuminate\Console\Command;

class ReportUsageCommand extends Command
{
    use ResolvesBillable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-metering:report
                            {--month= : The month to report (YYYY-MM)}
                            {--billable= : The billable ID to filter by}
                            {--billable-type= : The billable type to filter by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print summary usage report';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $month = $this->option('month');
        $billableId = $this->option('billable');
        $billableType = $this->option('billable-type');

        $billable = null;
        $billableLabel = 'All Entities';

        if ($billableId && $billableType) {
            [$success, $billable, $error] = $this->resolveBillable($billableType, $billableId);
            if (! $success) {
                return Command::FAILURE;
            }

            $billableLabel = $this->getBillableLabel($billable);
        }

        if ($month) {
            $periodStart = \Carbon\Carbon::parse($month)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth()->addSecond();
        } else {
            $period = Period::fromConfig(config('ai-metering.period', []));
            $periodStart = $period->getStart();
            $periodEnd = $period->getEnd();
        }

        $query = AiUsage::query()
            ->where('occurred_at', '>=', $periodStart)
            ->where('occurred_at', '<', $periodEnd);

        if ($billable) {
            $query->where('billable_type', $billableType)
                ->where('billable_id', $billableId);
        }

        $usage = $query->selectRaw('
                SUM(total_tokens) as tokens,
                SUM(total_cost) as cost,
                COUNT(*) as count,
                COUNT(DISTINCT billable_id) as billable_count
            ')
            ->first();

        $this->info('AI Usage Report');
        $this->info('================');
        $this->newLine();

        $this->info("Entity: {$billableLabel}");
        if ($billable) {
            $this->info("Type: {$billableType}");
            $this->info("ID: {$billableId}");
        }
        $this->newLine();

        $this->info("Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}");
        $this->newLine();

        if (($usage->tokens ?? 0) == 0 && ($usage->count ?? 0) == 0) {
            if ($billable) {
                $this->warn('No usage found for this entity in the specified period.');
            } else {
                $this->warn('No usage found in the specified period.');
            }
            $this->newLine();
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tokens', number_format($usage->tokens ?? 0)],
                ['Total Cost', '$'.number_format($usage->cost ?? 0, 6)],
                ['API Calls', number_format($usage->count ?? 0)],
                ['Billable Entities', number_format($usage->billable_count ?? 0)],
            ]
        );

        $breakdown = $query->selectRaw('
                provider,
                model,
                SUM(total_tokens) as tokens,
                SUM(total_cost) as cost,
                COUNT(*) as count
            ')
            ->groupBy('provider', 'model')
            ->orderByDesc('tokens')
            ->get();

        if ($breakdown->isNotEmpty()) {
            $this->newLine();
            $this->info('Breakdown by Provider/Model:');
            $this->newLine();

            $this->table(
                ['Provider', 'Model', 'Tokens', 'Cost', 'Calls'],
                $breakdown->map(function ($item) {
                    return [
                        $item->provider,
                        $item->model,
                        number_format($item->tokens),
                        '$'.number_format($item->cost, 2),
                        number_format($item->count),
                    ];
                })->toArray()
            );
        }

        return Command::SUCCESS;
    }
}
