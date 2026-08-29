<?php

namespace App\Services\Admin\System\Queues;

final class QueueSafetyPolicy
{
    public function maximumWorkerTimeoutSeconds(): int
    {
        return max(
            1,
            (int) config(
                'simpledesk-queues.worker.max_timeout_seconds',
                300,
            ),
        );
    }

    public function retrySafetyMarginSeconds(): int
    {
        return max(
            0,
            (int) config(
                'simpledesk-queues.worker.retry_safety_margin_seconds',
                30,
            ),
        );
    }

    public function minimumRetryAfterSeconds(): int
    {
        return $this->maximumWorkerTimeoutSeconds()
            + $this->retrySafetyMarginSeconds();
    }

    /**
     * @return array<int, string>
     */
    public function retryAfterRules(): array
    {
        return [
            'required',
            'integer',
            'min:'.$this->minimumRetryAfterSeconds(),
            'max:86400',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function retryAfterMessages(
        string $attribute = 'retry_after',
    ): array {
        return [
            $attribute.'.min' => 'Retry after must be at least '
                .$this->minimumRetryAfterSeconds()
                .' seconds so it remains safely above the configured worker timeout.',
        ];
    }
}
