<?php

/**
 Author: Ong Tin Yin
 */

return [
    'module_31' => [
        'base_url' => env(
            'MODULE_31_API_URL',
            'http://127.0.0.1:8001/api/v1'
        ),
    ],
    
    'module_34' => [
        'base_url' => env(
            'MODULE_34_API_URL',
            'http://127.0.0.1:8001/api/v1'
        ),
    ],

    'clients' => [
        'module-3-2' => [
            'secret' => env('MODULE_32_TO_31_SECRET'),
        ],
        
        'module-3-1' => [
            'secret' => env('MODULE_31_TO_34_SECRET'),
        ],
    ],
];

