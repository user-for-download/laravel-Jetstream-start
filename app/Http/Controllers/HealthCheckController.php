<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Health\HealthCheckServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckServiceInterface $healthCheckService
    ) {}

    public function __invoke(): JsonResponse
    {
        $checks = $this->healthCheckService->checkAll();

        $overallStatus = $this->healthCheckService->determineOverallStatus($checks);

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ], $overallStatus === 'healthy' ? 200 : 503);
    }
}
