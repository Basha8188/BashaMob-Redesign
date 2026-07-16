<?php

use App\Http\Middleware\EnsureActiveStaff;
use App\Http\Middleware\EnsureModulePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('staff.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        $middleware->alias([
            'staff' => EnsureActiveStaff::class,
            'module.permission' => EnsureModulePermission::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            $message = 'انتهت جلسة الحماية. تم تحديث الصفحة، اكتب بيانات الدخول مرة أخرى.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            if ($request->is('staff/*')) {
                return redirect()->route('staff.login')->withErrors(['email' => $message]);
            }

            return redirect()->back()->with('csrf_error', $message);
        });
        $exceptions->respond(function (SymfonyResponse $response, Throwable $exception, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            $message = 'انتهت جلسة الحماية. تم تحديث الصفحة، اكتب بيانات الدخول مرة أخرى.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            return $request->is('staff/*')
                ? redirect()->route('staff.login')->withErrors(['email' => $message])
                : redirect()->back()->with('csrf_error', $message);
        });
    })->create();
