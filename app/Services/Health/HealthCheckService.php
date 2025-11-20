<?php

declare(strict_types=1);

namespace App\Services\Health;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

final class HealthCheckService implements HealthCheckServiceInterface
{
    public function checkAll(): array
    {
        return [
            'cache' => $this->checkCache()->toArray(),
            'database' => $this->checkDatabase()->toArray(),
            'queue' => $this->checkQueue()->toArray(),
            'storage' => $this->checkStorage()->toArray(),
        ];
    }

    public function checkCache(): HealthResult
    {
        $driver = config('cache.default');
        $testKey = 'health_check_'.time();

        try {
            $writeTime = $this->measure(fn () => Cache::put($testKey, true, 10));
            $readTime = $this->measure(fn () => Cache::get($testKey));
            Cache::forget($testKey);

            $meta = [
                'driver' => $driver,
                'write_ms' => $writeTime,
                'read_ms' => $readTime,
            ];

            if ($writeTime > 50 || $readTime > 50) {
                return HealthResult::degraded('cache', 'Slow cache performance', $meta);
            }

            if ($driver === 'redis') {
                $meta += $this->getRedisMetrics();
            }

            return HealthResult::healthy('cache', $meta);

        } catch (Exception $exception) {
            return HealthResult::unhealthy('cache', $exception->getMessage());
        }
    }

    public function checkDatabase(): HealthResult
    {
        try {
            $connection = config('database.default');
            $time = $this->measure(function (): void {
                DB::connection()->getPdo()->query('SELECT 1');
            });

            $meta = ['connection' => $connection, 'response_ms' => $time];

            if ($time > 100) {
                return HealthResult::degraded('database', 'Slow database response', $meta);
            }

            return HealthResult::healthy('database', $meta);

        } catch (Exception $exception) {
            return HealthResult::unhealthy('database', $exception->getMessage());
        }
    }

    public function checkQueue(): HealthResult
    {
        try {
            $driver = config('queue.default');
            $jobs = Queue::size();

            return HealthResult::healthy('queue', [
                'driver' => $driver,
                'pending_jobs' => $jobs,
            ]);
        } catch (Exception $exception) {
            return HealthResult::unhealthy('queue', $exception->getMessage());
        }
    }

    public function checkStorage(): HealthResult
    {
        try {
            $path = storage_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);
            $usage = $total > 0 ? 100 - (($free / $total) * 100) : 0;

            $meta = [
                'free' => formatBytes($free),
                'usage_percent' => round($usage, 2),
            ];

            if ($usage > 90) {
                return HealthResult::degraded('storage', 'Disk space low', $meta);
            }

            return HealthResult::healthy('storage', $meta);

        } catch (Exception $exception) {
            return HealthResult::unhealthy('storage', $exception->getMessage());
        }
    }

    public function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('unhealthy', $statuses, true)) {
            return 'unhealthy';
        }

        if (in_array('degraded', $statuses, true)) {
            return 'degraded';
        }

        return 'healthy';
    }

    private function measure(callable $callback): float
    {
        $start = microtime(true);
        $callback();

        return round((microtime(true) - $start) * 1000, 2);
    }

    private function getRedisMetrics(): array
    {
        try {
            $info = Cache::getRedis()->info();

            return [
                'redis_clients' => $info['connected_clients'] ?? 'unknown',
                'redis_memory' => $info['used_memory_human'] ?? 'unknown',
            ];
        } catch (Exception) {
            return [];
        }
    }
}
