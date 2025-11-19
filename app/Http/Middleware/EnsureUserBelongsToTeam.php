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
