<?php

namespace App\Http\Middleware;

use Closure;

class EnsureSuperAdmin
{
    public function handle($request, Closure $next)
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
