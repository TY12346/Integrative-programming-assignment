<?php

/**
 Author: Ong Tin Yin
 */

namespace App\Services\ModuleIntegration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class DeliveryObligationApiClient
{
    public function hasActiveDeliveries(
        int $volunteerID
    ): bool {
        $clientID = 'module-3-1';
        $requestID = 'M31-'.Str::uuid()->toString();
        $timestamp = now()->utc()->toISOString();

        $baseURL = rtrim(
            (string) config('integrations.module_34.base_url'),
            '/'
        );

        if (
            $baseURL === '' ||
            filter_var($baseURL, FILTER_VALIDATE_URL) === false
        ) {
            throw new RuntimeException(
                'Module 3.4 API URL is not configured correctly.'
            );
        }

        $url = $baseURL
            ."/integrations/volunteers/{$volunteerID}"
            .'/delivery-obligations';

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
            'integrations.clients.module-3-1.secret'
        );

        if ($secret === '') {
            throw new RuntimeException(
                'Module 3.1 to Module 3.4 API secret is not configured.'
            );
        }

        $signature = hash_hmac(
            'sha256',
            $canonicalRequest,
            $secret
        );

        $response = Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->withHeaders([
                'X-Client-ID' => $clientID,
                'X-Request-ID' => $requestID,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])
            ->get($url, [
                'requestID' => $requestID,
                'timestamp' => $timestamp,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Module 3.4 returned HTTP '
                .$response->status()
                .' while checking delivery obligations.'
            );
        }

        $payload = $response->json();

        if (
            ! is_array($payload) ||
            ($payload['status'] ?? null) !== 'success' ||
            ($payload['requestID'] ?? null) !== $requestID ||
            ! isset($payload['data']) ||
            ! is_array($payload['data']) ||
            ! array_key_exists(
                'hasActiveDeliveries',
                $payload['data']
            )
        ) {
            throw new RuntimeException(
                'Module 3.4 returned an invalid response.'
            );
        }

        $hasActiveDeliveries =
            $payload['data']['hasActiveDeliveries'];

        if (! is_bool($hasActiveDeliveries)) {
            throw new RuntimeException(
                'Module 3.4 returned an invalid delivery status.'
            );
        }

        return $hasActiveDeliveries;
    }
}