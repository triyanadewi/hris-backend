<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'cors' => \App\Http\Middleware\CorsMiddleware::class,
        ]);
        
        // Add CORS middleware globally to api routes
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
   


        $exceptions->renderable(function (Illuminate\Validation\ValidationException $e, $request) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        });
    
        $exceptions->renderable(function (Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });

        $exceptions->renderable(function (Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
        return response()->json(['message' => 'Data not found.'], 404);
        });

        // Fallback for ALL other exceptions
        // $exceptions->renderable(function (Throwable $e, Request $request) {
        //     if ($request->is('api/*')) {
        //         return response()->json([
        //             'message' => 'Unknown error occurred.',
        //         ], 500);
        //     }
        // });
 
    })
    ->create();

    // $exceptions->renderable(function (Throwable $e, Request $request) {
    //     // Optional: log error detail
    //     Log::error($e);

    //     // Hanya berikan response JSON jika permintaan adalah ke API
    //     if ($request->is('api/*')) {
    //         return response()->json([
    //             'message' => 'Unknown error occurred.',
    //         ], 500);
    //     }
    // });