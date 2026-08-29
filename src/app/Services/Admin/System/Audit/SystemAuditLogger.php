<?php

namespace App\Services\Admin\System\Audit;

use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;

class SystemAuditLogger
{
    public function log(
        string $area,
        string $action,
        ?string $subjectType,
        ?int $subjectId,
        ?array $before,
        ?array $after,
        array $metadata = [],
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): SystemAuditLog {
        if (! app()->runningInConsole()) {
            $request = request();

            $ipAddress ??=
                $request->ip();

            $userAgent ??=
                $request->userAgent();
        }

        return SystemAuditLog::query()->create([
            'actor_id' => $actor?->id,

            'area' => $area,

            'action' => $action,

            'subject_type' => $subjectType,

            'subject_id' => $subjectId,

            'before_state' => $before,

            'after_state' => $after,

            'metadata' => $metadata,

            'ip_address' => $ipAddress,

            'user_agent' => $userAgent,
        ]);
    }
}
