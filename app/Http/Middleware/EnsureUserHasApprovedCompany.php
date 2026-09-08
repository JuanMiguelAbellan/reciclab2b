<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasApprovedCompany
{
    /**
     * Handle an incoming request.
     *
     * Staff roles (superadmin/admin) manage the platform and are not
     * required to belong to a company.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            return $next($request);
        }

        if ($user->companies()->doesntExist()) {
            return redirect()->route('companies.create');
        }

        if (! $user->hasApprovedCompany()) {
            return redirect()->route('account.status');
        }

        return $next($request);
    }
}
