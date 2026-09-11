<?php

/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Services/FoodRequestStatusClient.php
 * Purpose: Consume the Food Request Management REST API.
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FoodRequestStatusClient
{
    public function getStatus(int $requestId): array
    {
        $baseUrl = config(
            'foodlink.food_request_api_url',
            'http://127.0.0.1:8000/api/v1'
        );

        $token = config('foodlink.food_request_api_token');

        $requestID =
            'DELIVERY-' . bin2hex(random_bytes(8));

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout(5)
            ->get(
                "{$baseUrl}/requests/{$requestId}/status",
                [
                    'requestID' => $requestID,
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Unable to retrieve food request status.'
            );
        }

        return $response->json();
    }
}