<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role_id === UserRole::Admin) {
            return $next($request);
        }

        abort(403, 'У вас нет прав для доступа к этой странице.');
    }
}
