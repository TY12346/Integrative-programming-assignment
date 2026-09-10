<?php

namespace App\Http\Middleware;

use App\Services\UserRoles\UserRoleFactoryResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifiedRoleMiddleware
{
    public function __construct(
        private readonly UserRoleFactoryResolver $roleFactories
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string ...$allowedRoles
    ): Response {
        if (empty($allowedRoles)) {
            abort(
                403,
                'Access denied: no permitted roles were configured for this route.'
            );
        }

        $user = $request->user();

        if (! $user) {
            abort(
                401,
                'Authentication is required.'
            );
        }

        if (! in_array($user->role, $allowedRoles, true)) {
            abort(
                403,
                'Privilege escalation prevented. Required role(s): '
                .implode(', ', $allowedRoles)
                .'. Your current role is '.$user->role.'.'
            );
        }

        $hasAccess = $this->roleFactories
            ->resolve($user->role)
            ->handler()
            ->mayAccessRoleFeatures($user);

        if (! $hasAccess) {
            abort(
                403,
                'Access denied: your account must be verified and active.'
            );
        }

        return $next($request);
    }
}