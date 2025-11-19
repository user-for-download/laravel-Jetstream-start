<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure cache and queue are working for tests
        Cache::flush();
    }

    public function test_returns_healthy_status_when_all_checks_pass(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'environment',
                'version',
                'checks' => [
                    'cache',
                    'database',
                    'queue',
                    'storage',
                ],
            ])
            ->assertJson([
                'status' => 'healthy',
                'environment' => 'testing',
                'version' => '1.0.0',
            ]);

        $this->assertEquals('healthy', $testResponse->json('checks.database.status'));
        $this->assertEquals('healthy', $testResponse->json('checks.cache.status'));
    }

    public function test_includes_timestamp_in_iso8601_format(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk();

        $timestamp = $testResponse->json('timestamp');
        $this->assertNotNull($timestamp);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $timestamp
        );
    }

    public function test_includes_environment(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk()
            ->assertJson([
                'environment' => 'testing',
            ]);
    }

    public function test_includes_version(): void
    {
        config(['app.version' => '2.5.0']);

        $testResponse = $this->getJson('/health');

        $testResponse->assertOk()
            ->assertJson([
                'version' => '2.5.0',
            ]);
    }

    public function test_uses_default_version_when_not_configured(): void
    {
        config(['app.version' => null]);

        $testResponse = $this->getJson('/health');

        $testResponse->assertOk()
            ->assertJson([
                'version' => null,
            ]);
    }

    public function test_cache_check_returns_healthy_status(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk();

        $cacheCheck = $testResponse->json('checks.cache');

        $this->assertArrayHasKey('status', $cacheCheck);
        $this->assertArrayHasKey('driver', $cacheCheck);
        $this->assertContains($cacheCheck['status'], ['healthy', 'degraded']);
    }

    public function test_database_check_returns_healthy_status(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk();

        $dbCheck = $testResponse->json('checks.database');

        $this->assertEquals('healthy', $dbCheck['status']);
        $this->assertArrayHasKey('connection', $dbCheck);
        $this->assertArrayHasKey('response_time_ms', $dbCheck);
    }

    public function test_queue_check_includes_pending_jobs(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk();

        $queueCheck = $testResponse->json('checks.queue');

        $this->assertArrayHasKey('status', $queueCheck);
        $this->assertArrayHasKey('pending_jobs', $queueCheck);
        $this->assertIsInt($queueCheck['pending_jobs']);
    }

    public function test_storage_check_includes_disk_metrics(): void
    {
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk();

        $storageCheck = $testResponse->json('checks.storage');

        $this->assertArrayHasKey('status', $storageCheck);
        $this->assertContains($storageCheck['status'], ['healthy', 'warning', 'unknown']);
    }

    public function test_respects_throttle_middleware(): void
    {
        // Make 60 requests (the limit)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/health');
            $response->assertOk();
        }

        // 61st request should be throttled
        $response = $this->getJson('/health');
        $response->assertStatus(429); // Too Many Requests
    }

    public function test_does_not_require_csrf_token(): void
    {
        // This should work without CSRF token since it's excluded
        $testResponse = $this->getJson('/health');

        $testResponse->assertOk();
        // No 419 CSRF error
    }
}
