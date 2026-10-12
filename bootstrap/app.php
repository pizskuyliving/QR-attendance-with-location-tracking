<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // percayai header X-Forwarded-* hanya dari proxy di komputer yang sama (cloudflared, ngrok, Nginx lokal)
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->append(\App\Http\Middleware\HeaderKeamanan::class);
        $middleware->alias(['asisten.aktif' => \App\Http\Middleware\AsistenAktif::class]);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('asisten*') ? route('asisten.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('asisten*') ? route('asisten.dashboard') : route('mhs.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
