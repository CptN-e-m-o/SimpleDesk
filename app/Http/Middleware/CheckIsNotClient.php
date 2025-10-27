<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsNotClient
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isAdminOrAgent()) {
            return $next($request);
        }

        abort(403, 'У вас нет прав для доступа к этой странице.');
    }
}
