<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\DataTransferObjects\Team\CreateTeamDto;
use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\DataTransferObjects\Team\UpdateTeamNameDto;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Concerns\ExecutesInTransaction;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use Laravel\Jetstream\Jetstream;

readonly class TeamService implements TeamServiceInterface
{
    use ExecutesInTransaction;

    private function ensureUserIsNotTeamOwner(User $user, Team $team): void
    {
        if ($user->id === $team->user_id) {
            throw ValidationException::withMessages([
                'team' => ['You may not leave a team that you created.'],
            ]);
        }
    }

    public function addTeamMember(Team $team, AddTeamMemberDto $addTeamMemberDto): void
    {
        $newMember = Jetstream::findUserByEmailOrFail($addTeamMemberDto->email);

        $this->transaction(function () use ($team, $newMember, $addTeamMemberDto): void {
            $team->users()->attach($newMember, [
                'role' => $addTeamMemberDto->role,
            ]);
        });
    }

    public function removeTeamMember(Team $team, User $user): void
    {
        $this->ensureUserIsNotTeamOwner($user, $team);

        $this->transaction(function () use ($team, $user): void {
            $team->users()->detach($user);

            // CHANGED: Logic to auto-switch team if the user is leaving their current team
            if ($user->current_team_id === $team->id) {
                $user->refresh(); // Refresh relations to exclude the removed team

                // 1. Try to find their Personal Team first
                // 2. Fallback to any other team they belong to
                $nextTeam = $user->ownedTeams()->where('personal_team', true)->first()
                    ?? $user->allTeams()->first();

                if ($nextTeam) {
                    $user->switchTeam($nextTeam);
                } else {
                    // No teams left
                    $user->forceFill(['current_team_id' => null])->save();
                }
            }

            event(new TeamMemberRemoved($team, $user));
        });
    }

    public function switchTeam(User $user, Team $team): void
    {
        if (!$user->belongsToTeam($team)) {
            throw ValidationException::withMessages([
                'team' => ['You do not belong to this team.'],
            ]);
        }

        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();
    }

    public function createTeam(User $user, CreateTeamDto $createTeamDto): Team
    {
        return $this->transaction(function () use ($user, $createTeamDto): Team {
            $team = Team::create($createTeamDto->toTeamAttributes($user));

            if (!$createTeamDto->personalTeam) {
                $team->users()->attach($user, ['role' => 'admin']);
            }

            return $team;
        });
    }

    public function deleteTeam(Team $team): void
    {
        $this->transaction(function () use ($team): void {
            $owner = $team->owner;
            $wasCurrentTeam = $owner->current_team_id === $team->id;

            // Delete the team
            $team->delete();

            // If the deleted team was the current team, switch to another valid team
            if ($wasCurrentTeam) {
                $owner->refresh();

                $nextTeam = $owner->allTeams()->first();

                if ($nextTeam) {
                    $owner->switchTeam($nextTeam);
                } else {
                    $owner->forceFill(['current_team_id' => null])->save();
                }
            }
        });
    }

    public function updateTeamName(Team $team, UpdateTeamNameDto $updateTeamNameDto): void
    {
        $team->update([
            'name' => $updateTeamNameDto->name,
        ]);
    }

    public function inviteTeamMember(Team $team, InviteTeamMemberDto $inviteTeamMemberDto): TeamInvitation
    {
        return $this->transaction(fn (): TeamInvitation => $team->teamInvitations()->create([
            'email' => $inviteTeamMemberDto->email,
            'role' => $inviteTeamMemberDto->role,
        ]));
    }

    public function updateTeamMemberRole(Team $team, User $user, string $role): void
    {
        $team->users()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);
    }

    public function deleteTeamInvitation(Team $team, string $email): void
    {
        $team->teamInvitations()
            ->where('email', $email)
            ->delete();
    }

    public function createPersonalTeam(User $user): Team
    {
        return $this->transaction(function () use ($user): Team {
            $team = Team::create([
                'user_id' => $user->id,
                'name' => $this->generatePersonalTeamName($user),
                'personal_team' => true,
            ]);

            if ($user->current_team_id === null) {
                $user->forceFill([
                    'current_team_id' => $team->id,
                ])->save();
            }

            return $team;
        });
    }

    private function generatePersonalTeamName(User $user): string
    {
        $firstName = explode(' ', $user->name, 2)[0];

        return $firstName."'s Team";
    }

    public function transferOwnership(Team $team, User $user): void
    {
        $this->transaction(function () use ($team, $user): void {
            $oldOwner = $team->owner;

            if ($team->users()->where('user_id', $oldOwner->id)->exists()) {
                $team->users()->updateExistingPivot($oldOwner->id, ['role' => 'admin']);
            } else {
                $team->users()->attach($oldOwner, ['role' => 'admin']);
            }

            $team->forceFill([
                'user_id' => $user->id,
            ])->save();

            if ($team->users()->where('user_id', $user->id)->exists()) {
                $team->users()->updateExistingPivot($user->id, ['role' => 'admin']);
            } else {
                $team->users()->attach($user, ['role' => 'admin']);
            }

            $team->refresh();
        });
    }
}
