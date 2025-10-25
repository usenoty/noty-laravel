<?php

return [
    // Noty API endpoint
    'dsn' => env('NOTY_DSN', 'http://localhost:3020'),

    // API Token (Bearer token)
    'token' => env('NOTY_TOKEN'),

    // Channels configuration
    'channels' => [
        // Default channel to use when no channel is specified
        'default' => env('NOTY_DEFAULT_CHANNEL', 'general'),

        // Named channels - you can reference these by name in your code
        'list' => [
            'general' => env('NOTY_CHANNEL_GENERAL'),
            'auth' => env('NOTY_CHANNEL_AUTH'),
            'payments' => env('NOTY_CHANNEL_PAYMENTS'),
            'orders' => env('NOTY_CHANNEL_ORDERS'),
            // Add more channels as needed...
        ],
    ],

    // http | queue
    'transport' => env('NOTY_TRANSPORT', 'http'),

    // HTTP transport ayarları
    'http' => [
        'timeout' => 0.5, // saniye
        'connect_timeout' => 0.25,
        'path' => '/api/v1/events',
    ],

    // Queue transport ayarları
    'queue' => [
        // Laravel queue connection (null = default, 'redis', 'database', 'sqs', etc.)
        'connection' => env('NOTY_QUEUE_CONNECTION', null),
        
        // Queue name
        'queue_name' => env('NOTY_QUEUE_NAME', 'noty-events'),
        
        // Batch size - how many events to collect before dispatching to queue
        'batch_size' => env('NOTY_QUEUE_BATCH_SIZE', 10),
        
        // Retry configuration
        'retry_times' => env('NOTY_QUEUE_RETRY_TIMES', 3),
        'retry_delay' => env('NOTY_QUEUE_RETRY_DELAY', 60), // seconds
        
        // Concurrency for batch processing
        'concurrency' => env('NOTY_QUEUE_CONCURRENCY', 5),
        
        // Log failures
        'log_failures' => env('NOTY_QUEUE_LOG_FAILURES', true),
    ],

    // flush davranışı (terminate sonrası maksimum bekleme)
    'flush_timeout' => 1.0,

    // Default priority
    'default_priority' => env('NOTY_DEFAULT_PRIORITY', 'NORMAL'), // HIGH, NORMAL, LOW
];
