<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    public function test_health_endpoint_returns_successful_response(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'environment',
                'version',
                'checks' => [
                    'cache' => ['status'],
                    'database' => ['status'],
                    'queue' => ['status'],
                    'storage' => ['status'],
                ],
            ]);
    }

    public function test_cache_check_returns_healthy_status(): void
    {
        $response = $this->getJson('/health');
        $cacheCheck = $response->json('checks.cache');

        $this->assertArrayHasKey('status', $cacheCheck);
        // Updated: Access 'driver' via 'meta'
        $this->assertArrayHasKey('driver', $cacheCheck['meta']);
        $this->assertContains($cacheCheck['status'], ['healthy', 'degraded']);
    }

    public function test_database_check_returns_healthy_status(): void
    {
        $response = $this->getJson('/health');
        $dbCheck = $response->json('checks.database');

        $this->assertEquals('healthy', $dbCheck['status']);
        // Updated: Access via 'meta'
        $this->assertArrayHasKey('connection', $dbCheck['meta']);
        $this->assertArrayHasKey('response_ms', $dbCheck['meta']); // Renamed from response_time_ms
    }

    public function test_queue_check_includes_pending_jobs(): void
    {
        $response = $this->getJson('/health');
        $queueCheck = $response->json('checks.queue');

        $this->assertArrayHasKey('status', $queueCheck);
        // Updated: Access via 'meta'
        $this->assertArrayHasKey('pending_jobs', $queueCheck['meta']);
    }

    public function test_storage_check_includes_disk_metrics(): void
    {
        $response = $this->getJson('/health');
        $storageCheck = $response->json('checks.storage');

        $this->assertArrayHasKey('status', $storageCheck);
        // Updated: Access via 'meta' and key name 'free'
        $this->assertArrayHasKey('free', $storageCheck['meta']);
    }
}
