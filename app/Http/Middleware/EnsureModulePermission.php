<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    private const MAP = [
        'store' => 'view-settings', 'products' => 'view-products', 'sales' => 'view-sales',
        'purchases' => 'view-purchases', 'inventory' => 'view-inventory', 'repairs' => 'view-repairs',
        'customers' => 'view-customers', 'suppliers' => 'view-suppliers', 'expenses' => 'view-expenses',
        'reports' => 'view-reports', 'team' => 'view-staff', 'settings' => 'view-settings',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $permission = self::MAP[(string) $request->route('module')] ?? null;
        abort_unless($permission, 404);
        abort_unless($request->user()?->can($permission), 403);

        return $next($request);
    }
}
