<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Middleware/AuthenticateApiToken.php
 * Purpose: Stateless bearer token authentication for the module's REST web
 *          service. A client sends "Authorization: Bearer <token>" and the
 *          middleware resolves the FoodLink user behind it, so the same
 *          policies used by the web interface also protect the API.
 *
 * Secure coding notes:
 *   - Only the SHA-256 hash of a token is stored in users.api_token, so a
 *     database leak does not hand out working credentials.
 *   - Lookup happens on the hash, so no plaintext secret is ever compared.
 *   - Suspended or deleted accounts are rejected even with a valid token.
 *   - Failures return a generic 401 and are logged without the token value.
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
