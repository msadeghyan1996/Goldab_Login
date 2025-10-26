<?php

use App\Exceptions\ProjectLogicException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return apiResponse()->errors($e->errors(), 422, 'Provided data is invalid.');
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return apiResponse()->error('Not Found.', 404);
            }
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return apiResponse()->error('Unauthenticated.', 401, 'unauthenticated');
            }
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return apiResponse()->error('Too Many Attempts.', 429, 'too_many_attempts');
            }
        });

        $exceptions->render(function (ProjectLogicException $e, Request $request) {
            return apiResponse()->error($e->getMessage(), $e->getHttpStatusCode(), $e->getErrorType());
        });
        $exceptions->dontReport([
            ProjectLogicException::class
        ]);
    })->create();
