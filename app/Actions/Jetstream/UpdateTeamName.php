<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\DataTransferObjects\Team\UpdateTeamNameDto;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;

final readonly class UpdateTeamName implements UpdatesTeamNames
{
    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function update(User $user, Team $team, array $input): void
    {
        Gate::forUser($user)->authorize('update', $team);

        $updateTeamNameDto = UpdateTeamNameDto::fromRequest($input);

        $this->teamService->updateTeamName($team, $updateTeamNameDto);
    }
}
