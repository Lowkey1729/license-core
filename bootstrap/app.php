<?php

use App\DTOs\Responses\FailureResponse;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->wantsJson()) {
                return new FailureResponse(
                    message: 'Please wait a minute before retrying',
                    httpStatusCode: 429
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->wantsJson()) {
                return new FailureResponse(
                    message: $e->getMessage(),
                    httpStatusCode: 401
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->wantsJson()) {
                return new FailureResponse(
                    message: $e->getMessage(),
                    httpStatusCode: 404
                );
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->wantsJson()) {
                return new FailureResponse(
                    message: $e->getMessage(),
                    httpStatusCode: 405
                );
            }
        });

    })->create();
