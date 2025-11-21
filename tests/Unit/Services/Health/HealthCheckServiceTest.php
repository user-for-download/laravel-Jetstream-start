<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Health;

use App\Services\Health\HealthCheckService;
use App\Services\Health\HealthResult;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    private HealthCheckService $healthCheckService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthCheckService = new HealthCheckService();
    }

    public function test_cache_check_returns_healthy_status(): void
    {
        $healthResult = $this->healthCheckService->checkCache();

        $this->assertInstanceOf(HealthResult::class, $healthResult);
        $this->assertEquals('healthy', $healthResult->status);
        $this->assertArrayHasKey('driver', $healthResult->meta);
    }

    public function test_database_check_returns_healthy_status(): void
    {
        $healthResult = $this->healthCheckService->checkDatabase();

        $this->assertInstanceOf(HealthResult::class, $healthResult);
        $this->assertEquals('healthy', $healthResult->status);
        $this->assertArrayHasKey('connection', $healthResult->meta);
    }

    public function test_queue_check_returns_healthy_status(): void
    {
        $healthResult = $this->healthCheckService->checkQueue();

        $this->assertInstanceOf(HealthResult::class, $healthResult);
        $this->assertEquals('healthy', $healthResult->status);
        $this->assertArrayHasKey('driver', $healthResult->meta);
    }

    public function test_storage_check_returns_healthy_status(): void
    {
        $healthResult = $this->healthCheckService->checkStorage();

        $this->assertInstanceOf(HealthResult::class, $healthResult);
        $this->assertEquals('healthy', $healthResult->status);
        $this->assertArrayHasKey('free', $healthResult->meta);
    }

    public function test_determine_overall_status_with_one_warning(): void
    {
        // We need to simulate the array structure that comes out of checkAll()
        // The service's checkAll() calls toArray() on results.

        $checks = [
            'cache' => ['status' => 'healthy'],
            'database' => ['status' => 'degraded'], // degraded/warning
            'queue' => ['status' => 'healthy'],
            'storage' => ['status' => 'healthy'],
        ];

        $status = $this->healthCheckService->determineOverallStatus($checks);
        $this->assertEquals('degraded', $status);
    }
}
