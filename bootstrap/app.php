<?php

use App\Http\Middleware\EnsureUserHasApprovedCompany;
use App\Http\Middleware\EnsureUserIsApproved;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Sentry\Laravel\Integration as SentryIntegration;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'approved' => EnsureUserIsApproved::class,
            'has-company' => EnsureUserHasApprovedCompany::class,
        ]);

        // Detrás de un proxy inverso (Caddy/Nginx delante de laravel.test en el
        // mismo compose de producción), sin esto Laravel no ve la petición como
        // HTTPS: rompe cookies "secure" y la generación de URLs con https://.
        // TRUSTED_PROXIES admite lista separada por comas o '*' (todo el tráfico
        // ya pasa por el proxy del propio compose, no llega directo a este
        // contenedor desde fuera).
        $trustedProxies = (string) env('TRUSTED_PROXIES', '*');

        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Sin SENTRY_LARAVEL_DSN en .env, el SDK queda inactivo y esto no
        // hace nada (ver config/sentry.php).
        SentryIntegration::handles($exceptions);

        // Con APP_DEBUG=false (producción), sustituye la página de error HTML
        // por defecto de Laravel por una página Inertia en español, coherente
        // con el resto de la interfaz.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (app()->hasDebugModeEnabled() || $request->is('api/*')) {
                return $response;
            }

            if (in_array($response->getStatusCode(), [403, 404, 419, 500, 503], true)) {
                return Inertia::render('errors/Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
