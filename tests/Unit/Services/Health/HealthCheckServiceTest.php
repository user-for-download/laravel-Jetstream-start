<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Health;

use App\Services\Health\HealthCheckService;
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
        $result = $this->healthCheckService->checkCache();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('driver', $result);
    }

    public function test_database_check_returns_healthy_status(): void
    {
        $result = $this->healthCheckService->checkDatabase();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('connection', $result);
    }

    public function test_queue_check_returns_healthy_status(): void
    {
        $result = $this->healthCheckService->checkQueue();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('driver', $result);
    }

    public function test_storage_check_returns_healthy_status(): void
    {
        $result = $this->healthCheckService->checkStorage();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('free_space', $result);
    }

    public function test_determine_overall_status_with_all_healthy(): void
    {
        $checks = [
            'cache' => ['status' => 'healthy'],
            'database' => ['status' => 'healthy'],
            'queue' => ['status' => 'healthy'],
            'storage' => ['status' => 'healthy'],
        ];

        $status = $this->healthCheckService->determineOverallStatus($checks);
        $this->assertEquals('healthy', $status);
    }

    public function test_determine_overall_status_with_one_unhealthy(): void
    {
        $checks = [
            'cache' => ['status' => 'healthy'],
            'database' => ['status' => 'unhealthy'], // One unhealthy
            'queue' => ['status' => 'healthy'],
            'storage' => ['status' => 'healthy'],
        ];

        $status = $this->healthCheckService->determineOverallStatus($checks);
        $this->assertEquals('unhealthy', $status);
    }

    public function test_determine_overall_status_with_one_warning(): void
    {
        $checks = [
            'cache' => ['status' => 'healthy'],
            'database' => ['status' => 'warning'], // One warning
            'queue' => ['status' => 'healthy'],
            'storage' => ['status' => 'healthy'],
        ];

        $status = $this->healthCheckService->determineOverallStatus($checks);
        $this->assertEquals('degraded', $status);
    }
}
