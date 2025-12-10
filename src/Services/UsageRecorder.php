<?php

namespace Ajooda\AiMetering\Services;

use Ajooda\AiMetering\Models\AiUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsageRecorder
{
    /**
     * Record usage to the database.
     */
    public function record(array $data): ?AiUsage
    {
        $queueName = config('ai-metering.performance.queue_usage_recording');

        if ($queueName) {
            $job = new \Ajooda\AiMetering\Jobs\RecordAiUsage($data);

            if (is_string($queueName)) {
                $job->onQueue($queueName);
            }

            dispatch($job);

            return null;
        }

        return $this->recordSynchronously($data);
    }

    /**
     * Record usage synchronously.
     */
    public function recordSynchronously(array $data): AiUsage
    {
        $connection = config('ai-metering.storage.connection');

        return DB::connection($connection)->transaction(function () use ($data, $connection) {
            return $this->createUsageRecord($data, $connection);
        });
    }

    /**
     * Create the usage record.
     */
    protected function createUsageRecord(array $data, ?string $connection = null): AiUsage
    {
        if (isset($data['idempotency_key'])) {
            $existing = AiUsage::on($connection)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        try {
            $usage = AiUsage::on($connection)->create([
                'billable_type' => $data['billable_type'] ?? null,
                'billable_id' => $data['billable_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? null,
                'provider' => $data['provider'],
                'model' => $data['model'],
                'feature' => $data['feature'] ?? null,
                'input_tokens' => $data['input_tokens'] ?? null,
                'output_tokens' => $data['output_tokens'] ?? null,
                'total_tokens' => $data['total_tokens'] ?? null,
                'input_cost' => $data['input_cost'] ?? 0.0,
                'output_cost' => $data['output_cost'] ?? 0.0,
                'total_cost' => $data['total_cost'] ?? 0.0,
                'currency' => $data['currency'] ?? 'usd',
                'meta' => $data['meta'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            if (config('ai-metering.logging.enabled', true)) {
                Log::log(
                    config('ai-metering.logging.level', 'info'),
                    'AI usage recorded',
                    [
                        'usage_id' => $usage->id,
                        'billable_type' => $data['billable_type'] ?? null,
                        'billable_id' => $data['billable_id'] ?? null,
                        'provider' => $data['provider'],
                        'model' => $data['model'],
                        'tokens' => $data['total_tokens'] ?? 0,
                        'cost' => $data['total_cost'] ?? 0.0,
                    ]
                );
            }

            return $usage;
        } catch (\Exception $e) {
            if (config('ai-metering.logging.log_failures', true)) {
                Log::error('Failed to record AI usage', [
                    'data' => $data,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Record multiple usages in batch.
     */
    public function recordBatch(array $usages): array
    {
        $connection = config('ai-metering.storage.connection');
        $recorded = [];

        DB::connection($connection)->transaction(function () use ($usages, $connection, &$recorded) {
            foreach ($usages as $data) {
                $recorded[] = $this->createUsageRecord($data, $connection);
            }
        });

        return $recorded;
    }
}
