<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;

final readonly class RemoveTeamMember implements RemovesTeamMembers
{
    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function remove(User $user, Team $team, User $teamMember): void
    {
        $this->authorize($user, $team, $teamMember);

        $this->teamService->removeTeamMember($team, $teamMember);
    }

    private function authorize(User $user, Team $team, User $teamMember): void
    {
        if (!Gate::forUser($user)->check('removeTeamMember', $team) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException();
        }
    }
}
