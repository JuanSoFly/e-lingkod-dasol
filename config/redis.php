<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Configuration for E-Lingkod Dasol HRIS (Railway Optimized)
    |--------------------------------------------------------------------------
    |
    | Simplified Redis configuration for Railway cloud deployment.
    | Uses single database connection for cache, session, and queue.
    |
    */

    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'elingkod_dasol_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection
    |--------------------------------------------------------------------------
    |
    | Single Redis connection for all operations (cache, session, queue).
    | Simplified for Railway compatibility.
    |
    */
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', 'caboose.proxy.rlwy.net'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '53382'),
        'database' => env('REDIS_DB', '0'),

        // Basic timeout settings for Railway
        'read_timeout' => 60,
        'connect_timeout' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Connection (uses default)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', 'caboose.proxy.rlwy.net'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '53382'),
        'database' => env('REDIS_CACHE_DB', '1'), // Same database as default

        'read_timeout' => 30,
        'connect_timeout' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Connection (uses default)
    |--------------------------------------------------------------------------
    */
    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', 'caboose.proxy.rlwy.net'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '53382'),
        'database' => env('REDIS_SESSION_DB', '3'), // Same database as default

        'read_timeout' => 10,
        'connect_timeout' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Connection (uses default)
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', 'caboose.proxy.rlwy.net'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '53382'),
        'database' => env('REDIS_QUEUE_DB', '2'), // Same database as default

        'read_timeout' => 120,
        'connect_timeout' => 5,
        'block_for' => 5,
        'retry_after' => 90,
    ],
];
