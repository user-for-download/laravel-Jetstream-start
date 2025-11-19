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
