<?php

/**
 Author: Ong Tin Yin
 */


namespace App\Services\ModuleIntegration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerStatusApiClient
{
    public function getStatus(int $partnerID): array
    {
        $clientID = 'module-3-2';
        $requestID = 'M32-'.Str::uuid()->toString();
        $timestamp = now()->toISOString();

        $url = rtrim(
            config('integrations.module_31.base_url'),
            '/'
        )."/integrations/partners/{$partnerID}/status";

        $path = ltrim(
            (string) parse_url($url, PHP_URL_PATH),
            '/'
        );

        $canonicalRequest = implode("\n", [
            'GET',
            $path,
            $clientID,
            $requestID,
            $timestamp,
        ]);

        $secret = (string) config(
            'integrations.clients.module-3-2.secret'
        );

        if ($secret === '') {
            throw new RuntimeException(
                'Module 3.2 API secret is not configured.'
            );
        }

        $signature = hash_hmac(
            'sha256',
            $canonicalRequest,
            $secret
        );

        $response = Http::acceptJson()
            ->withHeaders([
                'X-Client-ID' => $clientID,
                'X-Request-ID' => $requestID,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])
            ->connectTimeout(2)
            ->timeout(5)
            ->get($url, [
                'requestID' => $requestID,
                'timestamp' => $timestamp,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Module 3.1 returned HTTP {$response->status()}: "
                .$response->body()
            );
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException(
                'Module 3.1 returned invalid JSON.'
            );
        }

        if (($result['requestID'] ?? null) !== $requestID) {
            throw new RuntimeException(
                'Module 3.1 returned the wrong requestID.'
            );
        }

        return $result;
    }
}
