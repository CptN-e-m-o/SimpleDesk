<?php

namespace App\Services\Admin\Mail\Settings;

use Illuminate\Support\Facades\Cache;

class MailAdminActionLock
{
    public function acquire(
        string $action,
        int $modelId
    ): bool {
        return Cache::add(
            $this->key($action, $modelId),
            now()->toIso8601String(),
            max(
                1,
                (int) config(
                    'simpledesk-mail-admin.actions.dispatch_lock_seconds',
                    300,
                ),
            ),
        );
    }

    public function release(
        string $action,
        int $modelId
    ): void {
        Cache::forget(
            $this->key($action, $modelId)
        );
    }

    private function key(
        string $action,
        int $modelId
    ): string {
        return "simpledesk:mail:admin-action:{$action}:{$modelId}";
    }
}
