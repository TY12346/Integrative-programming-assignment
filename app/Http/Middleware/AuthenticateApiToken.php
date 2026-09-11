<?php
/**
 * FoodLink - Module 3.3 Food Request Managemen
 */

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $this->unauthorised($request, 'missing token');
        }

        $user = User::query()
            ->where('api_token', hash('sha256', $token))
            ->where('account_status', 'ACTIVE')
            ->first();

        if ($user === null) {
            return $this->unauthorised($request, 'unknown or inactive token');
        }

        Auth::setUser($user);

        return $next($request);
    }

    private function unauthorised(Request $request, string $reason): Response
    {
        Log::warning('FoodLink API authentication failed.', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        return response()->json([
            'status' => 'error',
            'timestamp' => now()->toIso8601String(),
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
