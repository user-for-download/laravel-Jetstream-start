<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Health\HealthCheckService;
use App\Services\Health\HealthCheckServiceInterface;
use App\Services\Team\TeamService;
use App\Services\Team\TeamServiceInterface;
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use App\Services\Validation\PasswordValidator;
use Illuminate\Support\ServiceProvider;

class ServiceLayerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * Using 'bind' creates a new instance every time.
     * Using 'singleton' creates one instance per request.
     * For Services holding state or heavy setup, singleton is preferred.
     * For these stateless services, bind is fine, but singleton is slightly faster.
     */
    public function register(): void
    {
        $this->app->singleton(PasswordValidator::class);

        // Bind Interfaces to Concrete Classes
        $this->app->singleton(UserServiceInterface::class, UserService::class);
        $this->app->singleton(TeamServiceInterface::class, TeamService::class);
        $this->app->singleton(HealthCheckServiceInterface::class, HealthCheckService::class);
    }

    public function boot(): void {}
}
