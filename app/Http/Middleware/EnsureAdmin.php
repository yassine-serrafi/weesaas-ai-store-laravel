<?php

namespace App\Http\Middleware;

use App\Services\AdminAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège les routes /weeadmin : exige une session admin valide
 * (binding IP + expiration + ligne admin_sessions en base).
 */
class EnsureAdmin
{
    public function __construct(private AdminAuth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->auth->check($request)) {
            return redirect()
                ->route('admin.login')
                ->with('redirect', $request->fullUrl());
        }

        // Partage l'admin courant aux vues admin.
        view()->share('currentAdmin', $this->auth->user($request));

        return $next($request);
    }
}
