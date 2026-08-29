<?php

namespace App\Services\Admin\System\Queues;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class QueueWorkerRestartService
{
    public function signal(): void
    {
        $exitCode = Artisan::call(
            'queue:restart',
        );

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Unable to signal queue workers to restart.',
            );
        }
    }
}
