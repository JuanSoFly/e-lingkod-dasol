<?php
/**
 * E-Lingkod Dasol HRIS - Environment Diagnostics
 * Safe, isolated diagnostic script to run on production (Render) to identify connection/configuration issues.
 */

// Simple security check to prevent public exposure
if (!isset($_GET['key']) || $_GET['key'] !== 'dasol_debug_2026') {
    header('HTTP/1.1 403 Forbidden');
    echo "<h1>403 Forbidden</h1><p>Invalid debug key.</p>";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

function mask($str) {
    if (!$str) return 'EMPTY';
    $len = strlen($str);
    if ($len <= 6) return '******';
    return substr($str, 0, 3) . '...' . substr($str, -3);
}

echo "==================================================\n";
echo " E-LINGKOD DASOL HRIS DIAGNOSTICS\n";
echo " Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "==================================================\n\n";

// PHP Environment
echo "--- PHP ENVIRONMENT ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "Loaded Extensions:\n";
$extensions = ['redis', 'pdo_pgsql', 'pgsql', 'openssl', 'curl', 'mbstring', 'zip'];
foreach ($extensions as $ext) {
    echo "  - {$ext}: " . (extension_loaded($ext) ? 'LOADED ✅' : 'NOT LOADED ❌') . "\n";
}
echo "\n";

// Load Laravel
try {
    echo "--- BOOTSTRAP LARAVEL ---\n";
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo "Laravel Bootstrapped Successfully! ✅\n";
} catch (\Throwable $e) {
    echo "FAILED TO BOOTSTRAP LARAVEL! ❌\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n\n";
    exit;
}
echo "\n";

// Configuration Values
echo "--- CONFIGURATION & ENVIRONMENT ---\n";
echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "LOG_CHANNEL: " . config('logging.default') . "\n";
echo "SESSION_DRIVER: " . config('session.driver') . "\n";
echo "QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "CACHE_STORE: " . config('cache.default') . "\n";
echo "\n";

echo "DB Connection Details:\n";
echo "  - Driver: " . config('database.default') . "\n";
$dbConfig = config('database.connections.' . config('database.default'));
echo "  - Host: " . ($dbConfig['host'] ?? 'N/A') . "\n";
echo "  - Port: " . ($dbConfig['port'] ?? 'N/A') . "\n";
echo "  - Database: " . ($dbConfig['database'] ?? 'N/A') . "\n";
echo "  - Username: " . mask($dbConfig['username'] ?? '') . "\n";
echo "  - Password: " . mask($dbConfig['password'] ?? '') . "\n";
echo "\n";

echo "Redis Connection Details:\n";
echo "  - Client: " . config('database.redis.client') . "\n";
$redisConfig = config('database.redis.default');
echo "  - System REDIS_URL (getenv): " . mask(getenv('REDIS_URL') ?: '') . "\n";
echo "  - Config REDIS_URL (config): " . mask(config('database.redis.default.url') ?: '') . "\n";
echo "  - Host: " . ($redisConfig['host'] ?? 'N/A') . "\n";
echo "  - Port: " . ($redisConfig['port'] ?? 'N/A') . "\n";
echo "  - Database: " . ($redisConfig['database'] ?? 'N/A') . "\n";
echo "  - Password: " . mask($redisConfig['password'] ?? '') . "\n";
echo "\n";

// Database Connection Test
echo "--- DATABASE CONNECTION TEST (PostgreSQL) ---\n";
try {
    $time = microtime(true);
    $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
    $elapsed = round((microtime(true) - $time) * 1000, 2);
    echo "Laravel DB Connection: SUCCESS ✅ (Took {$elapsed} ms)\n";
    
    // Simple query test
    $result = Illuminate\Support\Facades\DB::select('SELECT version()');
    echo "Postgres Version: " . $result[0]->version . "\n";
    
    // Check if workflows table exists and get columns
    if (Illuminate\Support\Facades\Schema::hasTable('opcr_workflows')) {
        echo "Table 'opcr_workflows' exists! ✅\n";
        $columns = Illuminate\Support\Facades\Schema::getColumnListing('opcr_workflows');
        echo "Columns in 'opcr_workflows': " . implode(', ', $columns) . "\n";
    } else {
        echo "Table 'opcr_workflows' DOES NOT EXIST! ❌\n";
    }
} catch (\Throwable $e) {
    echo "DATABASE CONNECTION FAILED! ❌\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
echo "\n";

// Redis Connection Test
echo "--- REDIS CONNECTION TEST (Upstash) ---\n";
try {
    $time = microtime(true);
    $redis = Illuminate\Support\Facades\Redis::connection();
    $ping = $redis->ping();
    $elapsed = round((microtime(true) - $time) * 1000, 2);
    echo "Laravel Redis Connection: SUCCESS ✅ (Took {$elapsed} ms)\n";
    echo "Ping Result: " . (is_string($ping) ? $ping : json_encode($ping)) . "\n";
    
    // Write and read test
    echo "Testing Write/Read to Redis...\n";
    $redis->set('diagnose_test_key', 'Hello From Render! ' . date('Y-m-d H:i:s'));
    $val = $redis->get('diagnose_test_key');
    echo "Read back value from Redis: '{$val}' " . ($val ? '✅' : '❌') . "\n";
} catch (\Throwable $e) {
    echo "LARAVEL REDIS CONNECTION FAILED! ❌\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
echo "\n";

// Raw Redis Connection Tests
echo "--- RAW REDIS TEST (PHPRedis if loaded) ---\n";
if (extension_loaded('redis')) {
    try {
        $time = microtime(true);
        $r = new Redis();
        
        $host = $redisConfig['host'] ?? '127.0.0.1';
        $port = $redisConfig['port'] ?? 6379;
        $password = $redisConfig['password'] ?? null;
        
        // Handle rediss:// TLS
        if (strpos($host, 'tls://') === 0 || strpos(env('REDIS_URL') ?: '', 'rediss://') === 0) {
            $host = 'tls://' . str_replace(['tls://', 'rediss://'], '', $host);
        }
        
        echo "Connecting raw PHPRedis to {$host}:{$port}...\n";
        $r->connect($host, $port, 2.0); // 2 second timeout
        if ($password) {
            $r->auth($password);
        }
        $ping = $r->ping();
        $elapsed = round((microtime(true) - $time) * 1000, 2);
        echo "Raw PHPRedis Connection: SUCCESS ✅ (Took {$elapsed} ms)\n";
        echo "Ping Result: " . $ping . "\n";
    } catch (\Throwable $e) {
        echo "Raw PHPRedis Connection FAILED! ❌\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "PHPRedis extension not loaded, skipping raw test.\n";
}
echo "\n";

echo "--- RAW REDIS TEST (Predis if available) ---\n";
if (class_exists('Predis\Client')) {
    try {
        $time = microtime(true);
        $url = env('REDIS_URL');
        if ($url) {
            echo "Connecting raw Predis to REDIS_URL...\n";
            $client = new Predis\Client($url, [
                'parameters' => [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    'timeout' => 2.0
                ]
            ]);
        } else {
            $host = $redisConfig['host'] ?? '127.0.0.1';
            $port = $redisConfig['port'] ?? 6379;
            $password = $redisConfig['password'] ?? null;
            echo "Connecting raw Predis to {$host}:{$port}...\n";
            $scheme = (strpos($host, 'tls://') === 0 || strpos(env('REDIS_URL') ?: '', 'rediss://') === 0) ? 'rediss' : 'redis';
            $cleanHost = str_replace(['tls://', 'rediss://', 'redis://'], '', $host);
            
            $client = new Predis\Client([
                'scheme' => $scheme,
                'host'   => $cleanHost,
                'port'   => $port,
                'password' => $password,
                'timeout' => 2.0,
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
        }
        
        $ping = $client->ping();
        $elapsed = round((microtime(true) - $time) * 1000, 2);
        echo "Raw Predis Connection: SUCCESS ✅ (Took {$elapsed} ms)\n";
        echo "Ping Result: " . $ping . "\n";
    } catch (\Throwable $e) {
        echo "Raw Predis Connection FAILED! ❌\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Predis class not found, skipping raw test.\n";
}
echo "\n==================================================\n";
echo " DIAGNOSTICS COMPLETED\n";
echo "==================================================\n";
