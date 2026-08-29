<?php

namespace App\Data\Admin\System\Queues;

use App\Models\Admin\System\QueueDriverSettings;

final readonly class QueueActivationResultData
{
    public function __construct(
        public QueueDriverSettings $settings,
        public array $backlog,
        public bool $forceRequested,
        public bool $backlogOverrideUsed,
        public bool $workloadRoutingOverrideUsed,
        public array $pinnedWorkloads,
        public bool $restartSignaled,
    ) {}
}
