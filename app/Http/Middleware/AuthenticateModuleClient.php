<?php

/**
 Author: Ong Tin YIn
 */

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateModuleClient
{
    public function handle(
        Request $request,
        Closure $next,
        string $requiredClient
    ): Response {
        $clientID = (string) $request->header('X-Client-ID');
        $requestID = (string) $request->header('X-Request-ID');
        $timestamp = (string) $request->header('X-Timestamp');
        $signature = (string) $request->header('X-Signature');

        if (
            $clientID !== $requiredClient ||
            $requestID === '' ||
            $timestamp === '' ||
            $signature === ''
        ) {
            return $this->error(
                $requestID,
                'Missing or invalid module authentication.',
                401
            );
        }

        $secret = (string) config(
            "integrations.clients.{$requiredClient}.secret"
        );

        if ($secret === '') {
            return $this->error(
                $requestID,
                'Module authentication is not configured.',
                503
            );
        }

        try {
            $sentAt = CarbonImmutable::parse($timestamp);
        } catch (Throwable) {
            return $this->error(
                $requestID,
                'Invalid request timestamp.',
                401
            );
        }

        if (abs(now()->diffInSeconds($sentAt, false)) > 300) {
            return $this->error(
                $requestID,
                'The signed request has expired.',
                401
            );
        }

        if (
            $request->query('requestID') !== $requestID ||
            $request->query('timestamp') !== $timestamp
        ) {
            return $this->error(
                $requestID,
                'Request headers and parameters do not match.',
                401
            );
        }

        $canonicalRequest = implode("\n", [
            strtoupper($request->method()),
            $request->path(),
            $clientID,
            $requestID,
            $timestamp,
        ]);

        $expectedSignature = hash_hmac(
            'sha256',
            $canonicalRequest,
            $secret
        );

        if (! hash_equals($expectedSignature, $signature)) {
            return $this->error(
                $requestID,
                'Invalid request signature.',
                401
            );
        }

        $replayKey = 'module-request:'.hash(
            'sha256',
            $clientID.'|'.$requestID
        );

        if (! Cache::add($replayKey, true, now()->addMinutes(5))) {
            return $this->error(
                $requestID,
                'Duplicate requestID rejected.',
                409
            );
        }

        $request->attributes->set(
            'authenticated_module',
            $clientID
        );

        return $next($request);
    }

    private function error(
        ?string $requestID,
        string $message,
        int $status
    ): Response {
        return response()->json([
            'status' => 'error',
            'requestID' => $requestID ?: null,
            'timestamp' => now()->toISOString(),
            'message' => $message,
        ], $status);
    }
}