<?php
/**
 * FoodLink - Module 3.3 Food Request Management

 * Configuration for the Food Request Management module, including the
 *          donation gateway driver used to integrate with the Food Donation
 *          Management module (3.2) and the request business rules.
 */

return [

    'delivery' => [
        'hmac_secret' => env('DELIVERY_API_HMAC_SECRET'),
        'hmac_window_seconds' => (int) env(
            'DELIVERY_API_HMAC_WINDOW_SECONDS',
            300
        ),
    ],
    
   
    'donation_gateway' => env('FOODLINK_DONATION_GATEWAY', 'local'),

    // Base URL of the FoodLink web service consumed by HttpDonationGateway.
    'api_base_url' => env('FOODLINK_API_BASE_URL', env('APP_URL', 'http://localhost:8000')),

    // Seconds before an outgoing web service call is abandoned.
    'api_timeout' => (int) env('FOODLINK_API_TIMEOUT', 5),

    'request' => [
        // A request deadline must be at least this many hours in the future.
        'min_deadline_hours' => 1,
        // A request deadline may not be further away than this many days.
        'max_deadline_days' => 30,
        // Upper bound for a single food request quantity (input validation).
        'max_quantity' => 100000,
        // Units a charity is allowed to pick (whitelist, not free text).
        'units' => ['kg', 'g', 'litre', 'packs', 'boxes', 'trays', 'pieces', 'meals'],
        // Deadline within this many hours is treated as URGENT on the dashboard.
        'urgent_within_hours' => 24,
        // Rows per page on the request dashboard.
        'per_page' => 10,
    ],
];
