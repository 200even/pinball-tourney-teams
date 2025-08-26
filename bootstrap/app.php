<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        
        // Trust Railway proxies for HTTPS detection
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle authentication failures for Inertia requests properly
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->hasHeader('X-Inertia')) {
                // For Inertia requests that are unauthenticated, force a full page redirect
                return Inertia::location(route('login'));
            }
            
            // For regular requests, redirect to login
            return redirect()->guest(route('login'));
        });
        
        // Enable detailed error reporting for debugging (exclude auth and validation exceptions)
        $exceptions->render(function (Throwable $e, $request) {
            if (env('APP_DEBUG', false) && 
                !$e instanceof AuthenticationException &&
                !$e instanceof \Illuminate\Validation\ValidationException &&
                !app()->environment('testing')) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }
        });
    })->create();
