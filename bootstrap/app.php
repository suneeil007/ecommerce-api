<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([

            'role' =>
                \App\Http\Middleware\EnsureUserHasRole::class,

            'permission' =>
                \App\Http\Middleware\EnsureUserHasPermission::class,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent Guest Redirects for API
        |--------------------------------------------------------------------------
        */

        $middleware->redirectGuestsTo(
            fn () => null
        );
    })

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    */

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | Always Return JSON for API Requests
        |--------------------------------------------------------------------------
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->expectsJson()
                || $request->is('api/*')
        );

        /*
        |--------------------------------------------------------------------------
        | Custom API Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \App\Exceptions\ApiException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                $e->getMessage(),
                $e->status()
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                'Unauthenticated.',
                401
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Illuminate\Auth\Access\AuthorizationException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                'Forbidden.',
                403
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                'Validation failed.',
                422,
                $e->errors()
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Model Not Found
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                'Resource not found.',
                404
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Route Not Found
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                'Route not found.',
                404
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Method Not Allowed
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                'HTTP method not allowed.',
                405
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Database Errors
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Illuminate\Database\QueryException $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Database error.',
                500
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Throwable $e,
            Request $request
        ) {
            return \App\Helpers\ApiResponse::error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error.',
                500
            );
        });
    })

    ->create();
