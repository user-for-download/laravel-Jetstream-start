<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\DataTransferObjects\Team\CreateTeamDto;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;

final readonly class CreateTeam implements CreatesTeams
{
    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        event(new AddingTeam($user));

        $createTeamDto = CreateTeamDto::fromRequest($input);
        $team = $this->teamService->createTeam($user, $createTeamDto);

        $user->switchTeam($team);

        return $team;
    }
}
