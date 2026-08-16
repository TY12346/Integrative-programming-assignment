<?php

namespace App\Http\Middleware;

use App\Services\UserRoles\UserRoleHandler;
use Closure;
use Illuminate\Http\Request;

class VerifiedRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, $roles, true), 403);
        abort_unless(UserRoleHandler::for($user->role)->mayAccessRoleFeatures($user), 403, 'Your account must be verified and active.');

        return $next($request);
    }
}