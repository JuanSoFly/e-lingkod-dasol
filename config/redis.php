<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Configuration for E-Lingkod Dasol HRIS
    |--------------------------------------------------------------------------
    |
    | Redis configuration optimized for high-performance government HR system.
    | Supports 250+ concurrent employees with advanced caching strategies.
    |
    */

    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'elingkod_dasol_'),

        // Performance optimizations
        'read_timeout' => 60,
        'connect_timeout' => 5,

        // PhpRedis specific optimizations
        // Redis::SERIALIZER_JSON = 1, Redis::COMPRESSION_LZ4 = 1
        'serializer' => 1, // JSON serializer for better compatibility
        'compression' => 1, // LZ4 compression for better performance

        // Connection pooling for high concurrency
        'persistent' => env('REDIS_PERSISTENT', false),

        // Retry and backoff configuration
        'max_retries' => env('REDIS_MAX_RETRIES', 3),
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection
    |--------------------------------------------------------------------------
    |
    | Default connection for general Redis operations.
    | Used for system-level operations and miscellaneous tasks.
    |
    */
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),

        // Connection timeout settings
        'read_timeout' => 60,
        'connect_timeout' => 5,

        // TLS configuration for government compliance
        'scheme' => env('REDIS_SCHEME', 'tcp'),
        'context' => [
            'stream' => [
                'verify_peer' => env('REDIS_VERIFY_PEER', true),
                'verify_peer_name' => env('REDIS_VERIFY_PEER_NAME', true),
                'allow_self_signed' => env('REDIS_ALLOW_SELF_SIGNED', false),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Connection
    |--------------------------------------------------------------------------
    |
    | Dedicated Redis connection for Laravel cache operations.
    | Optimized for fast cache operations with separate database.
    |
    */
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),

        // Cache-specific optimizations
        'read_timeout' => 30,
        'connect_timeout' => 2,

        // Cache connection pooling
        'persistent' => true,
        'pool' => [
            'min_connections' => 5,
            'max_connections' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Connection
    |--------------------------------------------------------------------------
    |
    | Dedicated Redis connection for Laravel session storage.
    | Provides fast session access for 250+ concurrent users.
    |
    */
    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),

        // Session-specific optimizations
        'read_timeout' => 10,
        'connect_timeout' => 1,

        // Session persistence for reliability
        'persistent' => true,

        // Session lock configuration
        'lock_timeout' => 10,
        'lock_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | Dedicated Redis connection for Laravel queue operations.
    | Optimized for high-throughput background job processing.
    |
    */
    'queue' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_QUEUE_DB', '3'),

        // Queue-specific optimizations
        'read_timeout' => 120,
        'connect_timeout' => 5,

        // Queue blocking configuration
        'block_for' => 5,
        'retry_after' => 90,

        // High concurrency for PDS exports
        'persistent' => true,
        'pool' => [
            'min_connections' => 3,
            'max_connections' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Connection
    |--------------------------------------------------------------------------
    |
    | Dedicated Redis connection for dashboard analytics and metrics.
    | Optimized for complex aggregation operations.
    |
    */
    'analytics' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_ANALYTICS_DB', '4'),

        // Analytics-specific optimizations
        'read_timeout' => 45,
        'connect_timeout' => 3,

        // Pipeline support for batch operations
        'pipeline_size' => 100,

        // Memory-efficient for large datasets
        'persistent' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Connection
    |--------------------------------------------------------------------------
    |
    | Dedicated Redis connection for performance monitoring and health checks.
    | Isolated from main application traffic.
    |
    */
    'monitoring' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_MONITORING_DB', '5'),

        // Lightweight monitoring connection
        'read_timeout' => 15,
        'connect_timeout' => 2,

        // Non-blocking for monitoring
        'persistent' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Strategies for E-Lingkod Dasol HRIS
    |--------------------------------------------------------------------------
    |
    | Specialized cache configurations for different system components.
    |
    */
    'cache_strategies' => [
        // Dashboard cache strategy
        'dashboard' => [
            'ttl' => 300, // 5 minutes
            'prefix' => 'dashboard:',
            'tags' => ['dashboard', 'analytics'],
            'grace_period' => 60, // 1 minute grace period
        ],

        // PDS export cache strategy
        'pds_export' => [
            'ttl' => 7200, // 2 hours
            'prefix' => 'pds_export:',
            'tags' => ['pds', 'export', 'employee_data'],
            'grace_period' => 300, // 5 minutes grace period
        ],

        // Employee data cache strategy
        'employee_data' => [
            'ttl' => 1800, // 30 minutes
            'prefix' => 'employee:',
            'tags' => ['employee', 'hr_data'],
            'grace_period' => 120, // 2 minutes grace period
        ],

        // Leave calculation cache strategy
        'leave_calculation' => [
            'ttl' => 3600, // 1 hour
            'prefix' => 'leave:',
            'tags' => ['leave', 'calculation', 'hr'],
            'grace_period' => 180, // 3 minutes grace period
        ],

        // Government reports cache strategy
        'government_reports' => [
            'ttl' => 86400, // 24 hours
            'prefix' => 'gov_reports:',
            'tags' => ['reports', 'csc', 'government'],
            'grace_period' => 600, // 10 minutes grace period
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Advanced Redis performance optimizations for government HR system.
    |
    */
    'performance' => [
        // Memory optimization
        'memory_limit' => env('REDIS_MEMORY_LIMIT', '2gb'),
        'memory_policy' => 'allkeys-lru',
        'max_memory_samples' => 10,

        // Connection pooling
        'connection_pool' => [
            'enabled' => true,
            'min_connections' => 5,
            'max_connections' => 50,
            'connection_timeout' => 5,
            'idle_timeout' => 60,
        ],

        // Pipeline optimization
        'pipeline' => [
            'auto_pipeline' => true,
            'pipeline_size' => 50,
            'pipeline_timeout' => 10,
        ],

        // Compression settings
        'compression' => [
            'enabled' => true,
            'algorithm' => 'lz4',
            'threshold' => 1024, // Compress values > 1KB
        ],

        // Serialization settings
        'serialization' => [
            'format' => 'json',
            'compress_arrays' => true,
            'binary_safe' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Health check and monitoring configuration for Redis.
    |
    */
    'health' => [
        // Health check thresholds
        'thresholds' => [
            'memory_usage_percent' => 85,
            'hit_rate_percent' => 80,
            'connected_clients' => 100,
            'response_time_ms' => 100,
        ],

        // Alert configuration
        'alerts' => [
            'memory_high' => true,
            'hit_rate_low' => true,
            'connection_errors' => true,
            'slow_queries' => true,
        ],

        // Monitoring intervals
        'check_interval' => 300, // 5 minutes
        'alert_cooldown' => 900, // 15 minutes

        // Log configuration
        'log_level' => 'warning',
        'log_slow_queries' => true,
        'slow_query_threshold' => 100, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for government compliance.
    |
    */
    'security' => [
        // Authentication
        'require_auth' => true,
        'password_policy' => [
            'min_length' => 16,
            'require_complexity' => true,
            'rotation_days' => 90,
        ],

        // Network security
        'allowed_ips' => env('REDIS_ALLOWED_IPS', '127.0.0.1'),
        'bind_interfaces' => env('REDIS_BIND_INTERFACES', '127.0.0.1'),

        // Command restrictions
        'disabled_commands' => [
            'FLUSHDB',
            'FLUSHALL',
            'CONFIG',
            'DEBUG',
            'EVAL',
            'SCRIPT',
        ],

        // TLS configuration
        'tls' => [
            'enabled' => env('REDIS_TLS_ENABLED', false),
            'cert_file' => env('REDIS_TLS_CERT'),
            'key_file' => env('REDIS_TLS_KEY'),
            'ca_file' => env('REDIS_TLS_CA'),
            'verify_peer' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup and Persistence Settings
    |--------------------------------------------------------------------------
    |
    | Data persistence and backup configuration.
    |
    */
    'persistence' => [
        // RDB snapshot settings
        'rdb' => [
            'enabled' => true,
            'save_intervals' => [
                ['seconds' => 900, 'changes' => 1],   // 15 minutes, 1 change
                ['seconds' => 300, 'changes' => 10],  // 5 minutes, 10 changes
                ['seconds' => 60, 'changes' => 10000], // 1 minute, 10000 changes
            ],
            'compression' => true,
            'checksum' => true,
            'filename' => 'dump.rdb',
        ],

        // AOF settings
        'aof' => [
            'enabled' => true,
            'fsync_policy' => 'everysec',
            'rewrite_policy' => 'auto',
            'rewrite_min_size' => '64mb',
            'rewrite_percentage' => 100,
            'filename' => 'appendonly.aof',
        ],

        // Backup settings
        'backup' => [
            'enabled' => true,
            'schedule' => '0 2 * * *', // Daily at 2 AM
            'retention_days' => 30,
            'compression' => true,
            'backup_directory' => '/var/backups/redis',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Debug Settings
    |--------------------------------------------------------------------------
    |
    | Development-specific Redis settings.
    |
    */
    'development' => [
        'debug_mode' => env('APP_DEBUG', false),
        'slowlog_enabled' => true,
        'slowlog_length' => 128,
        'monitoring_enabled' => env('APP_DEBUG', false),
        'log_all_commands' => env('APP_DEBUG', false),
    ],
];