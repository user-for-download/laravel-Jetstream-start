<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTeamMiddleware extends Command
{
    protected $signature = 'make:team-middleware';

    protected $description = 'Create all team-related middleware files';

    public function handle(): int
    {
        $middlewares = [
            'EnsureUserBelongsToTeam',
            'EnsureUserHasTeamRole',
            'EnsureUserHasTeamPermission',
            'EnsureUserIsTeamOwner',
        ];

        $created = 0;
        $skipped = 0;

        foreach ($middlewares as $middleware) {
            $path = app_path(sprintf('Http/Middleware/%s.php', $middleware));

            if (File::exists($path)) {
                $this->warn(sprintf('Middleware %s already exists. Skipping...', $middleware));
                $skipped++;

                continue;
            }

            $this->createMiddleware($middleware, $path);
            $this->info('Created middleware: '.$middleware);
            $created++;
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line('  Created: '.$created);
        $this->line('  Skipped: '.$skipped);

        return self::SUCCESS;
    }

    private function createMiddleware(string $name, string $path): void
    {
        $stub = $this->getStub($name);
        File::put($path, $stub);
    }

    private function getStub(string $name): string
    {
        return match ($name) {
            'EnsureUserBelongsToTeam' => $this->getEnsureUserBelongsToTeamStub(),
            'EnsureUserHasTeamRole' => $this->getEnsureUserHasTeamRoleStub(),
            'EnsureUserHasTeamPermission' => $this->getEnsureUserHasTeamPermissionStub(),
            'EnsureUserIsTeamOwner' => $this->getEnsureUserIsTeamOwnerStub(),
            default => '',
        };
    }

    private function getEnsureUserBelongsToTeamStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'User not authenticated');
        }

        if (!$user->currentTeam) {
            abort(403, 'User does not belong to any team');
        }

        return $next($request);
    }
}
PHP;
    }

    private function getEnsureUserHasTeamRoleStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTeamRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->currentTeam) {
            abort(403, 'User not authenticated or no current team');
        }

        foreach ($roles as $role) {
            if ($user->hasCurrentTeamRole($role)) {
                return $next($request);
            }
        }

        abort(403, sprintf(
            'User does not have required team role. Required: [%s]',
            implode(', ', $roles)
        ));
    }
}
PHP;
    }

    private function getEnsureUserHasTeamPermissionStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTeamPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user || !$user->currentTeam) {
            abort(403, 'User not authenticated or no current team');
        }

        foreach ($permissions as $permission) {
            if (!$user->canInCurrentTeam($permission)) {
                abort(403, sprintf(
                    'User does not have required permission: %s',
                    $permission
                ));
            }
        }

        return $next($request);
    }
}
PHP;
    }

    private function getEnsureUserIsTeamOwnerStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeamOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->currentTeam) {
            abort(403, 'User not authenticated or no current team');
        }

        if (!$user->ownsTeam($user->currentTeam)) {
            abort(403, 'User does not own the current team');
        }

        return $next($request);
    }
}
PHP;
    }
}
