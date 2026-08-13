<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\SystemAuditLogIndexRequest;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class SystemAuditLogController extends Controller
{
    public function __invoke(SystemAuditLogIndexRequest $request): Response
    {
        $filters = $request->validated();

        $query = SystemAuditLog::query()
            ->with([
                'actor:id,email,username,first_name,last_name',
            ]);

        $this->applyFilters(
            $query,
            $filters,
        );

        $actorIds = SystemAuditLog::query()
            ->whereNotNull('actor_id')
            ->distinct()
            ->pluck('actor_id');

        $actors = User::query()
            ->whereIn('id', $actorIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('email')
            ->get([
                'id',
                'email',
                'username',
                'first_name',
                'last_name',
            ]);

        $areas = SystemAuditLog::query()
            ->whereNotNull('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->values();

        $actions = SystemAuditLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        return Inertia::render(
            'Admin/System/Audit/Index',
            [
                'logs' => $query
                    ->latest()
                    ->paginate(25)
                    ->withQueryString(),

                'filters' => $filters,

                'filterOptions' => [
                    'areas' => $areas,
                    'actions' => $actions,
                    'actors' => $actors,
                ],
            ],
        );
    }

    private function applyFilters(
        Builder $query,
        array $filters,
    ): void {
        if (! empty($filters['area'])) {
            $query->where(
                'area',
                $filters['area'],
            );
        }

        if (! empty($filters['action'])) {
            $query->where(
                'action',
                $filters['action'],
            );
        }

        if (! empty($filters['actor_id'])) {
            $query->where(
                'actor_id',
                $filters['actor_id'],
            );
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['created_from'],
            );
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['created_to'],
            );
        }
    }
}
