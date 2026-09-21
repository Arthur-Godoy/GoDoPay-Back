<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: glob(__DIR__.'/../routes/api/*.php'),
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
        ]);

        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage() === 'This action is unauthorized.'
                    ? 'Você não tem permissão para realizar esta ação'
                    : $e->getMessage(),
            ], 403);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json(['message' => 'Sua sessão expirou. Entre novamente'], 401);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        });
    })->create();
