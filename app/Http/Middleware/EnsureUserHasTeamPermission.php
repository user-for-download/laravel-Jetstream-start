<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки разрешений в текущей команде
 */
class EnsureUserHasTeamPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
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
