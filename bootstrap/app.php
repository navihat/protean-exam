<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Lets the Alpine.js pages call /api with the session cookie (D2, D3).
        $middleware->statefulApi();

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Business rule violations are expected outcomes, not bugs: keep them out of the log.
        $exceptions->dontReport(DomainException::class);

        // Every /api error answers {message, code, errors?} (D12, API_CONTRACT §1).
        // Returning null lets Laravel render the error as usual (web pages).
        $exceptions->render(fn (DomainException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $e->getMessage(), 'code' => $e->errorCode()], $e->status())
            : null);

        $exceptions->render(fn (ValidationException $e, Request $request) => $request->is('api/*')
            ? response()->json([
                'message' => $e->getMessage(),
                'code' => 'VALIDATION_FAILED',
                'errors' => $e->errors(),
            ], $e->status)
            : null);

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Unauthenticated.', 'code' => 'UNAUTHENTICATED'], 401)
            : null);

        // Laravel has already turned AuthorizationException into a 403 and
        // ModelNotFoundException into a 404 HttpException at this point.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $codes = [403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 429 => 'TOO_MANY_REQUESTS'];
            $status = $e->getStatusCode();

            if (! $request->is('api/*') || ! isset($codes[$status])) {
                return null;
            }

            // The 404 message would name the model class ("No query results for model ..."), so hide it.
            $message = match ($status) {
                404 => 'Not found.',
                default => $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
            };

            return response()->json(['message' => $message, 'code' => $codes[$status]], $status, $e->getHeaders());
        });
    })->create();
