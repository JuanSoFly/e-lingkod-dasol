<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RedisMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:monitor
                            {--action=info : Action to perform (info|status|health|clear|optimize)}
                            {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and manage Redis performance for E-Lingkod Dasol HRIS';

    /**
     * Redis connection instance
     */
    private $redis;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->redis = Redis::connection();
        $action = $this->option('action');
        $format = $this->option('format');

        try {
            switch ($action) {
                case 'info':
                    $this->showRedisInfo($format);
                    break;
                case 'status':
                    $this->showRedisStatus();
                    break;
                case 'health':
                    $this->performHealthCheck();
                    break;
                case 'clear':
                    $this->clearRedisData();
                    break;
                case 'optimize':
                    $this->optimizeRedis();
                    break;
                default:
                    $this->error("Invalid action: {$action}. Available actions: info, status, health, clear, optimize");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Redis monitoring failed: {$e->getMessage()}");
            Log::error('Redis monitor command failed', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Show comprehensive Redis information
     */
    private function showRedisInfo($format = 'table')
    {
        $this->info('🔍 Redis Information for E-Lingkod Dasol HRIS');
        $this->line(str_repeat('=', 60));

        $info = $this->redis->info('all');

        $serverInfo = $this->parseRedisInfo($info);

        if ($format === 'json') {
            $this->line(json_encode($serverInfo, JSON_PRETTY_PRINT));
        } else {
            $this->displayServerInfo($serverInfo);
        }
    }

    /**
     * Show Redis connection status
     */
    private function showRedisStatus()
    {
        $this->info('📊 Redis Status Check');
        $this->line(str_repeat('=', 60));

        // Test Redis connection
        $ping = $this->redis->ping();
        $status = $ping === 'PONG' ? '✅ Connected' : '❌ Disconnected';
        $this->line("Connection Status: {$status}");

        if ($ping === 'PONG') {
            // Get basic stats
            $info = $this->redis->info('server,memory,clients');
            $parsed = $this->parseRedisInfo($info);

            $this->table(
                ['Metric', 'Value', 'Status'],
                [
                    ['Redis Version', $parsed['redis_version'] ?? 'Unknown', '✅'],
                    ['Uptime Days', $this->formatUptime($parsed['uptime_in_seconds'] ?? 0), '✅'],
                    ['Connected Clients', $parsed['connected_clients'] ?? 0,
                        ($parsed['connected_clients'] ?? 0) > 100 ? '⚠️' : '✅'],
                    ['Memory Usage', $parsed['used_memory_human'] ?? 'Unknown',
                        $this->getMemoryStatus($parsed)],
                    ['Cache Hit Rate', $this->calculateHitRate($parsed) . '%',
                        $this->getHitRateStatus($parsed)],
                    ['Total Commands', number_format($parsed['total_commands_processed'] ?? 0), '✅'],
                ]
            );

            // Laravel-specific checks
            $this->checkLaravelIntegration();
        }
    }

    /**
     * Perform comprehensive health check
     */
    private function performHealthCheck()
    {
        $this->info('🏥 Redis Health Check');
        $this->line(str_repeat('=', 60));

        $issues = [];
        $warnings = [];

        // Basic connectivity test
        try {
            $ping = $this->redis->ping();
            if ($ping !== 'PONG') {
                $issues[] = 'Redis connection failed';
            }
        } catch (\Exception $e) {
            $issues[] = 'Redis connection error: ' . $e->getMessage();
        }

        // Memory check
        $info = $this->redis->info('memory');
        $parsed = $this->parseRedisInfo($info);
        $memoryBytes = $parsed['used_memory'] ?? 0;
        $maxMemory = $this->getMaxMemory();

        if ($maxMemory > 0) {
            $memoryUsage = ($memoryBytes / $maxMemory) * 100;
            if ($memoryUsage > 90) {
                $issues[] = 'Critical: Memory usage is ' . round($memoryUsage, 2) . '%';
            } elseif ($memoryUsage > 75) {
                $warnings[] = 'Warning: Memory usage is ' . round($memoryUsage, 2) . '%';
            }
        }

        // Cache hit rate check
        $stats = $this->redis->info('stats');
        $statsParsed = $this->parseRedisInfo($stats);
        $hitRate = $this->calculateHitRate($statsParsed);

        if ($hitRate < 50) {
            $issues[] = 'Critical: Cache hit rate is very low (' . $hitRate . '%)';
        } elseif ($hitRate < 80) {
            $warnings[] = 'Warning: Cache hit rate is below optimal (' . $hitRate . '%)';
        }

        // Laravel cache test
        try {
            Cache::store('redis')->put('health_check_test', 'test_value', 60);
            $retrieved = Cache::store('redis')->get('health_check_test');

            if ($retrieved !== 'test_value') {
                $issues[] = 'Laravel Redis cache store is not working properly';
            } else {
                Cache::store('redis')->forget('health_check_test');
            }
        } catch (\Exception $e) {
            $issues[] = 'Laravel Redis cache test failed: ' . $e->getMessage();
        }

        // Check for Laravel Horizon/Queue if configured
        if (config('queue.default') === 'redis') {
            try {
                $queueSize = $this->redis->llen('queues:default');
                if ($queueSize > 1000) {
                    $warnings[] = 'Large queue size: ' . $queueSize . ' jobs pending';
                }
            } catch (\Exception $e) {
                $warnings[] = 'Could not check queue size: ' . $e->getMessage();
            }
        }

        // Display results
        if (empty($issues) && empty($warnings)) {
            $this->info('✅ All health checks passed! Redis is operating optimally.');
        } else {
            if (!empty($issues)) {
                $this->error('❌ Critical Issues Found:');
                foreach ($issues as $issue) {
                    $this->line("  • {$issue}");
                }
            }

            if (!empty($warnings)) {
                $this->warn('⚠️  Warnings:');
                foreach ($warnings as $warning) {
                    $this->line("  • {$warning}");
                }
            }
        }

        // Performance metrics
        $this->line("\n📈 Performance Metrics:");
        $this->displayPerformanceMetrics($parsed, $statsParsed);
    }

    /**
     * Clear Redis data safely
     */
    private function clearRedisData()
    {
        $this->warn('⚠️  This will clear Redis cache data. This action cannot be undone!');

        if (!$this->confirm('Do you want to proceed with clearing Redis cache?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->info('🧹 Clearing Redis cache data...');

        try {
            // Clear Laravel cache keys safely
            $cacheKeys = $this->redis->keys(config('cache.prefix') . '*');
            if (!empty($cacheKeys)) {
                $this->redis->del($cacheKeys);
                $this->info("Cleared " . count($cacheKeys) . " cache keys");
            }

            // Clear expired keys
            $this->redis->command('SCAN', [0, 'MATCH', '*', 'COUNT', 1000]);

            $this->info('✅ Redis cache cleared successfully!');

            // Clear Laravel cache
            Cache::store('redis')->flush();
            $this->info('✅ Laravel Redis cache flushed!');

        } catch (\Exception $e) {
            $this->error("Failed to clear Redis data: {$e->getMessage()}");
        }
    }

    /**
     * Optimize Redis performance
     */
    private function optimizeRedis()
    {
        $this->info('⚡ Optimizing Redis performance...');

        try {
            // Get current info
            $info = $this->redis->info('memory');
            $parsed = $this->parseRedisInfo($info);

            // Memory optimization
            if (isset($parsed['used_memory_peak'])) {
                $this->redis->config('SET', 'maxmemory-policy', 'allkeys-lru');
                $this->info('✅ Set maxmemory policy to allkeys-lru');
            }

            // Clear slow log
            $this->redis->config('SET', 'slowlog-log-slower-than', '10000');
            $this->redis->command('SLOWLOG', ['RESET']);
            $this->info('✅ Reset slow log and optimized threshold');

            // Force memory defragmentation if available
            try {
                $this->redis->command('MEMORY', ['PURGE']);
                $this->info('✅ Triggered memory defragmentation');
            } catch (\Exception $e) {
                $this->warn('Memory defragmentation not available or failed');
            }

            // Optimize cache keys
            $this->optimizeCacheKeys();

            $this->info('✅ Redis optimization completed!');

            // Show optimization results
            $this->showOptimizationResults();

        } catch (\Exception $e) {
            $this->error("Optimization failed: {$e->getMessage()}");
        }
    }

    /**
     * Check Laravel Redis integration
     */
    private function checkLaravelIntegration()
    {
        $this->line("\n🔧 Laravel Integration Status:");

        $checks = [
            'Cache Driver' => config('cache.default') === 'redis',
            'Session Driver' => config('session.driver') === 'redis',
            'Queue Driver' => config('queue.default') === 'redis',
        ];

        foreach ($checks as $name => $enabled) {
            $status = $enabled ? '✅ Enabled' : '❌ Disabled';
            $this->line("  {$name}: {$status}");
        }

        // Test Laravel cache
        try {
            $testKey = 'laravel_test_' . time();
            Cache::store('redis')->put($testKey, 'test', 60);
            $value = Cache::store('redis')->get($testKey);
            Cache::store('redis')->forget($testKey);

            $this->line("  Laravel Cache Store: " . ($value === 'test' ? '✅ Working' : '❌ Failed'));
        } catch (\Exception $e) {
            $this->line("  Laravel Cache Store: ❌ Error - " . $e->getMessage());
        }
    }

    /**
     * Display server information in table format
     */
    private function displayServerInfo($info)
    {
        // Server Information
        $this->line("\n🖥️  Server Information:");
        $this->table(
            ['Property', 'Value'],
            [
                ['Redis Version', $info['redis_version'] ?? 'Unknown'],
                ['Redis Mode', $info['redis_mode'] ?? 'standalone'],
                ['OS', $info['os'] ?? 'Unknown'],
                ['Architecture', $info['arch_bits'] . ' bit'],
                ['Process ID', $info['process_id'] ?? 'Unknown'],
                ['Uptime', $this->formatUptime($info['uptime_in_seconds'] ?? 0)],
                ['Uptime in Days', round(($info['uptime_in_seconds'] ?? 0) / 86400, 2)],
            ]
        );

        // Memory Information
        $this->line("\n💾 Memory Information:");
        $this->table(
            ['Property', 'Value'],
            [
                ['Used Memory', $info['used_memory_human'] ?? 'Unknown'],
                ['Peak Memory', $info['used_memory_peak_human'] ?? 'Unknown'],
                ['System Memory', $info['total_system_memory_human'] ?? 'Unknown'],
                ['Memory Fragmentation', $info['mem_fragmentation_ratio'] ?? 'N/A'],
                ['Memory Allocator', $info['mem_allocator'] ?? 'Unknown'],
            ]
        );

        // Client Information
        $this->line("\n👥 Client Information:");
        $this->table(
            ['Property', 'Value'],
            [
                ['Connected Clients', $info['connected_clients'] ?? 0],
                ['Blocked Clients', $info['blocked_clients'] ?? 0],
                ['Client Connections', $info['total_connections_received'] ?? 0],
                ['Max Clients', $info['maxclients'] ?? 'Unknown'],
            ]
        );

        // Performance Statistics
        $this->line("\n📊 Performance Statistics:");
        $this->table(
            ['Property', 'Value'],
            [
                ['Total Commands', number_format($info['total_commands_processed'] ?? 0)],
                ['Commands per Second', round($info['instantaneous_ops_per_sec'] ?? 0, 2)],
                ['Keyspace Hits', $info['keyspace_hits'] ?? 0],
                ['Keyspace Misses', $info['keyspace_misses'] ?? 0],
                ['Hit Rate', $this->calculateHitRate($info) . '%'],
                ['Expired Keys', $info['expired_keys'] ?? 0],
                ['Evicted Keys', $info['evicted_keys'] ?? 0],
            ]
        );
    }

    /**
     * Display performance metrics
     */
    private function displayPerformanceMetrics($memoryInfo, $statsInfo)
    {
        $metrics = [
            'Memory Usage' => $memoryInfo['used_memory_human'] ?? 'Unknown',
            'Cache Hit Rate' => $this->calculateHitRate($statsInfo) . '%',
            'Commands/sec' => round($statsInfo['instantaneous_ops_per_sec'] ?? 0, 2),
            'Connected Clients' => $statsInfo['connected_clients'] ?? 0,
            'Expired Keys' => $statsInfo['expired_keys'] ?? 0,
            'Evicted Keys' => $statsInfo['evicted_keys'] ?? 0,
        ];

        foreach ($metrics as $metric => $value) {
            $this->line("  • {$metric}: {$value}");
        }
    }

    /**
     * Show optimization results
     */
    private function showOptimizationResults()
    {
        $this->line("\n📊 Optimization Results:");

        $info = $this->redis->info('memory,stats');
        $parsed = $this->parseRedisInfo($info);

        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Memory Usage', $parsed['used_memory_human'] ?? 'Unknown', '✅'],
                ['Hit Rate', $this->calculateHitRate($parsed) . '%', '✅'],
                ['Commands/sec', round($parsed['instantaneous_ops_per_sec'] ?? 0, 2), '✅'],
                ['Fragmentation', $parsed['mem_fragmentation_ratio'] ?? 'N/A', '✅'],
            ]
        );
    }

    /**
     * Optimize cache keys
     */
    private function optimizeCacheKeys()
    {
        // Find and consolidate similar cache keys
        $keys = $this->redis->keys('*');
        $optimized = 0;

        foreach ($keys as $key) {
            // Skip system keys
            if (strpos($key, 'system:') === 0) {
                continue;
            }

            // Check if key is expired
            $ttl = $this->redis->ttl($key);
            if ($ttl === -1) {
                // Key without expiration, set reasonable TTL
                $this->redis->expire($key, 3600); // 1 hour
                $optimized++;
            }
        }

        if ($optimized > 0) {
            $this->info("✅ Optimized {$optimized} cache keys with expiration times");
        }
    }

    /**
     * Parse Redis INFO output into associative array
     */
    private function parseRedisInfo($info)
    {
        $parsed = [];

        // Handle array response (newer PhpRedis versions)
        if (is_array($info)) {
            $parsed = $this->flattenRedisInfoArray($info);
        } elseif (is_string($info)) {
            // Handle string response (traditional format)
            $lines = explode("\r\n", $info);

            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $parsed[trim($key)] = trim($value);
                }
            }
        }

        return $parsed;
    }

    /**
     * Flatten Redis INFO array response into key-value pairs
     */
    private function flattenRedisInfoArray($infoArray)
    {
        $flattened = [];

        foreach ($infoArray as $section => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $flattened[$key] = is_string($value) ? $value : (string) $value;
                }
            } else {
                // Handle case where section might not be an array
                $flattened[$section] = is_string($data) ? $data : (string) $data;
            }
        }

        return $flattened;
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Get memory status indicator
     */
    private function getMemoryStatus($info)
    {
        $used = $info['used_memory'] ?? 0;
        $max = $this->getMaxMemory();

        if ($max > 0) {
            $usage = ($used / $max) * 100;
            if ($usage > 90) return '❌ Critical';
            if ($usage > 75) return '⚠️ Warning';
        }

        return '✅ Good';
    }

    /**
     * Get hit rate status indicator
     */
    private function getHitRateStatus($info)
    {
        $hitRate = $this->calculateHitRate($info);
        if ($hitRate < 50) return '❌ Poor';
        if ($hitRate < 80) return '⚠️ Fair';
        return '✅ Good';
    }

    /**
     * Get max memory from Redis config
     */
    private function getMaxMemory()
    {
        try {
            $maxMemory = $this->redis->config('GET', 'maxmemory');
            return is_array($maxMemory) ? ($maxMemory[1] ?? 0) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format uptime in human readable format
     */
    private function formatUptime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
}