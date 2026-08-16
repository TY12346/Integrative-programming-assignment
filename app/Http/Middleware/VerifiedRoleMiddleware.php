<?php

namespace App\Http\Middleware;

use App\Services\UserRoles\UserRoleFactoryResolver;
use Closure;
use Illuminate\Http\Request;

class VerifiedRoleMiddleware
{
    public function __construct(private readonly UserRoleFactoryResolver $roleFactories)
    {
    }

    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, $roles, true), 403);
        abort_unless(
            $this->roleFactories->resolve($user->role)->handler()->mayAccessRoleFeatures($user),
            403,
            'Your account must be verified and active.'
        );

        return $next($request);
    }
}
