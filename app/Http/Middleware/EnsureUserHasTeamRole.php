<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки роли в текущей команде
 */
class EnsureUserHasTeamRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
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
