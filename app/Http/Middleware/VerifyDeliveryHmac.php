<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Http/Middleware/VerifyDeliveryHmac.php
 * Secure coding: HMAC request authentication + timestamp window + one-time
 * request ID ledger to reject forged and replayed delivery status updates.
 */

namespace App\Http\Middleware;

use App\Models\DeliveryApiRequest;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeliveryHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) $request->header('X-FoodLink-Request-Id', '');
        $timestamp = (string) $request->header('X-FoodLink-Timestamp', '');
        $signature = strtolower((string) $request->header('X-FoodLink-Signature', ''));
        $secret = (string) config('foodlink.delivery.hmac_secret', '');
        $window = (int) config('foodlink.delivery.hmac_window_seconds', 300);

        if ($secret === '') {
            return $this->error('Delivery API HMAC is not configured.', 500);
        }

        if (! preg_match('/^[A-Za-z0-9._:-]{16,100}$/', $requestId)
            || ! ctype_digit($timestamp)
            || ! preg_match('/^[a-f0-9]{64}$/', $signature)) {
            return $this->error('Invalid signed request.', 401);
        }

        if (abs(now()->timestamp - (int) $timestamp) > $window) {
            return $this->error('Request timestamp is outside the allowed window.', 401);
        }

        $canonical = implode("\n", [
            strtoupper($request->method()),
            '/'.$request->path(),
            $timestamp,
            $requestId,
            hash('sha256', $request->getContent()),
        ]);

        $expected = hash_hmac('sha256', $canonical, $secret);

        // Constant-time comparison avoids leaking information about the signature.
        if (! hash_equals($expected, $signature)) {
            return $this->error('Invalid signed request.', 401);
        }

        // Store the request ID before executing the update. The UNIQUE database
        // constraint also closes the race where two identical requests arrive
        // at nearly the same time.
        try {
            DeliveryApiRequest::create([
                'request_id' => $requestId,
                'request_timestamp' => (int) $timestamp,
                'http_method' => strtoupper($request->method()),
                'request_path' => '/'.$request->path(),
                'ip_address' => $request->ip(),
            ]);
        } catch (QueryException) {
            return $this->error('Replay request rejected.', 409);
        }

        return $next($request);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
        ], $status);
    }
}
