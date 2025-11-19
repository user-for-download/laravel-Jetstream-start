<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Team\UpdateTeamNameRequest;
use App\Models\Team;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamServiceInterface $teamService
    ) {}

    /**
     * Show the team management screen.
     */
    public function show(Request $request, Team $team): View
    {
        $this->authorize('view', $team);

        return view('teams.show', [
            'user' => $request->user(),
            'team' => $team,
        ]);
    }

    /**
     * Show the team creation screen.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Team::class);

        return view('teams.create', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the team's name.
     */
    public function update(UpdateTeamNameRequest $updateTeamNameRequest, Team $team): RedirectResponse
    {

        $this->teamService->updateTeamName($team, $updateTeamNameRequest->toDto());

        return to_route('teams.show', $team)
            ->with('success', 'Team name updated successfully!');
    }
}
