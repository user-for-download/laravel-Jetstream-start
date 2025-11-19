<?php

declare(strict_types=1);

namespace App\Services\Health;

interface HealthCheckServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function checkCache(): array;

    /**
     * @return array<string, mixed>
     */
    public function checkDatabase(): array;

    /**
     * @return array<string, mixed>
     */
    public function checkQueue(): array;

    /**
     * @return array<string, mixed>
     */
    public function checkStorage(): array;

    /**
     * @param  array<string, array<string, mixed>>  $checks
     */
    public function determineOverallStatus(array $checks): string;
}
