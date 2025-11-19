<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\Models\Team;
use App\Models\User;
use App\Rules\UniqueTeamMember;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Jetstream;

final readonly class AddTeamMember implements AddsTeamMembers
{
    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function add(User $user, Team $team, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        $addTeamMemberDto = new AddTeamMemberDto(email: $email, role: $role);

        $this->teamService->addTeamMember($team, $addTeamMemberDto);

        $newTeamMember = Jetstream::findUserByEmailOrFail($email);
        event(new TeamMemberAdded($team, $newTeamMember));
    }

    private function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], [
            'email' => ['required', 'email', 'exists:users', new UniqueTeamMember($team)],
            'role' => Jetstream::hasRoles() ? ['required', 'string', new \Laravel\Jetstream\Rules\Role()] : [],
        ])->validate();
    }
}
