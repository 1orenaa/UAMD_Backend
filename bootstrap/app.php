<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Regjistro alias-in 'role' për CheckRole middleware
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── 401: Token mungon ose ka skaduar ─────────────────────────────────
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Nuk jeni i autentikuar. Ju lutem bëni login.',
                ], 401);
            }
        });

        // ── 422: Gabime validimi ──────────────────────────────────────────────
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Të dhënat janë të pavlefshme.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // ── 404: Route ose rekord nuk u gjet ─────────────────────────────────
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Burimi i kërkuar nuk u gjet.',
                ], 404);
            }
        });

        // ── Gabime HTTP të tjera (403, 405, 429...) ───────────────────────────
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $messages = [
                    403 => 'Nuk keni leje për këtë veprim.',
                    405 => 'Metoda HTTP nuk lejohet.',
                    429 => 'Shumë kërkesa. Prisni pak dhe provoni sërish.',
                ];

                return response()->json([
                    'message' => $messages[$e->getStatusCode()] ?? $e->getMessage(),
                ], $e->getStatusCode());
            }
        });

        // ── 500: Gabim i brendshëm i serverit ────────────────────────────────
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $debug = config('app.debug');
                return response()->json([
                    'message' => 'Gabim i brendshëm i serverit.',
                    'detail'  => $debug ? $e->getMessage() : null,
                ], 500);
            }
        });

    })->create();
