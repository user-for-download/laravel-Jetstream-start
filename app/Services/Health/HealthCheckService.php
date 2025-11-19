<?php

declare(strict_types=1);

namespace App\Services\Health;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PDO;

final class HealthCheckService implements HealthCheckServiceInterface
{
    public function checkAll(): array
    {
        return [
            'cache' => $this->checkCache(),
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }

    public function checkCache(): array
    {
        $driver = config('cache.default');
        $testKey = 'health_check_'.time();
        $testValue = ['test' => true, 'timestamp' => now()->toIso8601String()];

        $health = [
            'driver' => $driver,
            'status' => 'healthy',
        ];

        try {
            $startWrite = microtime(true);
            Cache::put($testKey, $testValue, 10);
            $writeTime = round((microtime(true) - $startWrite) * 1000, 2);

            $startRead = microtime(true);
            $result = Cache::get($testKey);
            $readTime = round((microtime(true) - $startRead) * 1000, 2);

            if ($result !== $testValue) {
                $health['status'] = 'degraded';
                $health['message'] = 'Cache integrity check failed';
            }

            $health['performance'] = [
                'write_time_ms' => $writeTime,
                'read_time_ms' => $readTime,
            ];

            if ($writeTime > 50 || $readTime > 50) {
                $health['status'] = 'degraded';
                $health['message'] = 'Slow cache performance';
            }

            $this->addCacheDriverMetrics($health, $driver);

        } catch (Exception $exception) {
            $health['status'] = 'unhealthy';
            $health['error'] = $exception->getMessage();
        } finally {
            // Critical Improvement: Always clean up
            try {
                Cache::forget($testKey);
            } catch (Exception) {
                // Suppress cleanup errors if the main connection is already dead
            }
        }

        return $health;
    }

    private function addCacheDriverMetrics(array &$health, string $driver): void
    {
        if ($driver === 'database') {
            $this->addDatabaseCacheMetrics($health);
        } elseif ($driver === 'redis') {
            $this->addRedisMetrics($health);
        }
    }

    private function addDatabaseCacheMetrics(array &$health): void
    {
        $table = config('cache.stores.database.table', 'cache');

        try {
            $total = DB::table($table)->count();
            $expired = DB::table($table)
                ->where('expiration', '<', now()->timestamp)
                ->count();

            $health['entries'] = $total;
            $health['expired'] = $expired;
        } catch (Exception) {
            $health['entries'] = 'error';
        }
    }

    private function addRedisMetrics(array &$health): void
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();

            $health['metrics'] = [
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
            ];
        } catch (Exception) {
            $health['metrics'] = ['error' => 'Could not fetch Redis metrics'];
        }
    }

    public function checkDatabase(): array
    {
        $health = [
            'connection' => config('database.default'),
            'status' => 'healthy',
        ];

        try {
            $start = microtime(true);
            $pdo = DB::connection()->getPdo();
            $health['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            $health['response_time_ms'] = $responseTime;

            if ($responseTime > 100) {
                $health['status'] = 'degraded';
                $health['message'] = 'Slow database response';
            }
        } catch (Exception $exception) {
            $health['status'] = 'unhealthy';
            $health['error'] = $exception->getMessage();
        }

        return $health;
    }

    public function checkQueue(): array
    {
        $health = [
            'driver' => config('queue.default'),
            'status' => 'healthy',
        ];

        try {
            $size = Queue::size();
            $health['pending_jobs'] = $size;
        } catch (Exception $exception) {
            $health['status'] = 'unhealthy';
            $health['error'] = $exception->getMessage();
        }

        return $health;
    }

    public function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            return [
                'status' => $usedPercent < 90 ? 'healthy' : 'warning',
                'free_space' => formatBytes($freeSpace),
                'used_percent' => round($usedPercent, 2),
            ];
        } catch (Exception) {
            return [
                'status' => 'unknown',
                'message' => 'Could not check storage',
            ];
        }
    }

    public function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('unhealthy', $statuses, true) || in_array('critical', $statuses, true)) {
            return 'unhealthy';
        }

        if (in_array('degraded', $statuses, true) || in_array('warning', $statuses, true)) {
            return 'degraded';
        }

        return 'healthy';
    }
}
