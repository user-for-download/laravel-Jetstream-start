<?php

declare(strict_types=1);

namespace App\Services\Health;

interface HealthCheckServiceInterface
{
    public function checkCache(): HealthResult;

    public function checkDatabase(): HealthResult;

    public function checkQueue(): HealthResult;

    public function checkStorage(): HealthResult;

    /**
     * @param  array<string, array>  $checks
     */
    public function determineOverallStatus(array $checks): string;

    /**
     * Check all services and return an array of results.
     *
     * @return array<string, array>
     */
    public function checkAll(): array;
}
