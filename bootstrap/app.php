<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Every /api request is treated as "Accept: application/json".
        $middleware->api(prepend: [ForceJsonResponse::class]);

        // Sanctum's token-ability checks, used as abilities:books:read / abilities:books:write.
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unknown routes never reach the API middleware group, so also force JSON here.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 404: missing book, someone else's book, or an unknown URL (all look the same).
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        // 403: a valid token that is not allowed to do this (e.g. read-only token writing).
        $exceptions->render(function (MissingAbilityException|AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Forbidden. This token is not allowed to do that.'], 403);
            }
        });

        // Anything else with an HTTP status (405, 429, ...) keeps the same { "message": ... } shape.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(
                    ['message' => $e->getMessage() ?: 'Request failed.'],
                    $e->getStatusCode(),
                    $e->getHeaders()
                );
            }
        });

        // 401 (missing/invalid/logged-out token) and 422 (validation) already come back as JSON
        // because shouldRenderJsonWhen() is true for /api/*.
    })->create();
