<?php

namespace App\Http\Middleware;

use App\Models\Admin\AgentStatus;
use App\Services\Admin\AgentStatuses\AgentStatusResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $agent = $request->user()?->roles()->where('type', 'agent')->exists() ? $request->user() : null;

        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                    ]
                    : null,
                'permissions' => $request->user()
                    ? $request->user()->permissionKeys()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'agentStatus' => $agent ? fn () => [
                'current' => (function ($resolved) {
                    return ['id' => $resolved->status->id, 'name' => $resolved->status->name, 'icon' => $resolved->status->icon, 'color' => $resolved->status->color, 'availability' => $resolved->availability->value, 'expires_at' => $resolved->globalPeriod?->expires_at?->toIso8601String()];
                })(app(AgentStatusResolver::class)->currentStatus($agent)),
                'options' => AgentStatus::selectable()->orderBy('sort_order')->get(['id', 'name', 'icon', 'color', 'availability', 'default_duration_minutes']),
            ] : null,
        ];
    }
}
