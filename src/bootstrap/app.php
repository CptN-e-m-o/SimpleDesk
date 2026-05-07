<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => CheckPermission::class,
            'super_admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function ($response, Request $request) {
            return Inertia::render('Errors/ErrorPage', [
                'status' => $response->getStatusCode(),
            ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
