<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Services\Team\TeamServiceInterface;
use Laravel\Jetstream\Contracts\DeletesTeams;

final readonly class DeleteTeam implements DeletesTeams
{
    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function delete(Team $team): void
    {
        $this->teamService->deleteTeam($team);
    }
}
