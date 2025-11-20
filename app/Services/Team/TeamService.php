<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\DataTransferObjects\Team\CreateTeamDto;
use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\DataTransferObjects\Team\UpdateTeamNameDto;
use App\Enums\ActivityLogEnum;
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

    // =========================================================================
    // Team Entity Management
    // =========================================================================

    public function createTeam(User $user, CreateTeamDto $createTeamDto): Team
    {
        return $this->transaction(function () use ($user, $createTeamDto): Team {
            $team = Team::create($createTeamDto->toTeamAttributes($user));

            if (!$createTeamDto->personalTeam) {
                $this->assignAdminRole($team, $user);
            }

            $this->logActivity($team, $user, ActivityLogEnum::TEAM_CREATED);

            return $team;
        });
    }

    public function createPersonalTeam(User $user): Team
    {
        return $this->transaction(function () use ($user): Team {
            $team = Team::create([
                'user_id' => $user->id,
                'name' => explode(' ', $user->name, 2)[0]."'s Team",
                'personal_team' => true,
            ]);

            $this->ensureUserHasCurrentTeam($user, $team);

            return $team;
        });
    }

    public function updateTeamName(Team $team, UpdateTeamNameDto $updateTeamNameDto): void
    {
        $team->update(['name' => $updateTeamNameDto->name]);
        // Activity log handled by Model Observer if configured, or can be added here explicitly
    }

    public function deleteTeam(Team $team): void
    {
        $this->transaction(function () use ($team): void {
            $owner = $team->owner;
            $wasCurrentTeam = $owner->current_team_id === $team->id;

            $team->delete();

            if ($wasCurrentTeam) {
                $this->resetCurrentTeam($owner);
            }
        });
    }

    public function transferOwnership(Team $team, User $user): void
    {
        $this->transaction(function () use ($team, $user): void {
            $oldOwner = $team->owner;

            // Ensure both users end up as admins
            $this->assignAdminRole($team, $oldOwner);
            $team->forceFill(['user_id' => $user->id])->save();
            $this->assignAdminRole($team, $user);

            $team->refresh();

            $this->logActivity($team, auth()->user(), ActivityLogEnum::OWNERSHIP_TRANSFERRED, [
                'old_owner' => $oldOwner->email,
                'new_owner' => $user->email,
            ]);
        });
    }

    // =========================================================================
    // Membership Management
    // =========================================================================

    public function addTeamMember(Team $team, AddTeamMemberDto $addTeamMemberDto): void
    {
        $newMember = Jetstream::findUserByEmailOrFail($addTeamMemberDto->email);

        $this->transaction(function () use ($team, $newMember, $addTeamMemberDto): void {
            $team->users()->attach($newMember, ['role' => $addTeamMemberDto->role]);

            $this->logActivity($team, auth()->user(), ActivityLogEnum::MEMBER_ADDED, [
                'member_email' => $newMember->email,
                'role' => $addTeamMemberDto->role,
            ]);
        });
    }

    public function removeTeamMember(Team $team, User $user): void
    {
        if ($user->id === $team->user_id) {
            throw ValidationException::withMessages([
                'team' => ['You may not leave a team that you created.'],
            ]);
        }

        $this->transaction(function () use ($team, $user): void {
            $team->users()->detach($user);

            if ($user->current_team_id === $team->id) {
                $this->resetCurrentTeam($user->fresh());
            }

            $this->logActivity($team, auth()->user(), ActivityLogEnum::MEMBER_REMOVED, [
                'member_email' => $user->email,
            ]);

            event(new TeamMemberRemoved($team, $user));
        });
    }

    public function updateTeamMemberRole(Team $team, User $user, string $role): void
    {
        $team->users()->updateExistingPivot($user->id, ['role' => $role]);

        $this->logActivity($team, auth()->user(), ActivityLogEnum::ROLE_UPDATED, [
            'member_email' => $user->email,
            'new_role' => $role,
        ]);
    }

    public function switchTeam(User $user, Team $team): void
    {
        if (!$user->belongsToTeam($team)) {
            throw ValidationException::withMessages([
                'team' => ['You do not belong to this team.'],
            ]);
        }

        $user->forceFill(['current_team_id' => $team->id])->save();
    }

    // =========================================================================
    // Invitation Management
    // =========================================================================

    public function inviteTeamMember(Team $team, InviteTeamMemberDto $inviteTeamMemberDto): TeamInvitation
    {
        return $this->transaction(function () use ($team, $inviteTeamMemberDto) {
            $model = $team->teamInvitations()->create($inviteTeamMemberDto->toArray());

            $this->logActivity($team, auth()->user(), ActivityLogEnum::INVITATION_SENT, [
                'invited_email' => $inviteTeamMemberDto->email,
                'role' => $inviteTeamMemberDto->role,
            ]);

            return $model;
        });
    }

    public function deleteTeamInvitation(Team $team, string $email): void
    {
        $team->teamInvitations()->where('email', $email)->delete();

        $this->logActivity($team, auth()->user(), ActivityLogEnum::INVITATION_CANCELLED, [
            'invited_email' => $email,
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function assignAdminRole(Team $team, User $user): void
    {
        if ($team->users()->where('user_id', $user->id)->exists()) {
            $team->users()->updateExistingPivot($user->id, ['role' => 'admin']);
        } else {
            $team->users()->attach($user, ['role' => 'admin']);
        }
    }

    private function ensureUserHasCurrentTeam(User $user, Team $team): void
    {
        if ($user->current_team_id === null) {
            $user->forceFill(['current_team_id' => $team->id])->save();
        }
    }

    private function resetCurrentTeam(User $user): void
    {
        $nextTeam = $user->ownedTeams()->where('personal_team', true)->first()
            ?? $user->allTeams()->first();

        if ($nextTeam) {
            $user->switchTeam($nextTeam);
        } else {
            $user->forceFill(['current_team_id' => null])->save();
        }
    }

    private function logActivity(Team $team, ?User $user, ActivityLogEnum $activityLogEnum, array $properties = []): void
    {
        activity()
            ->performedOn($team)
            ->causedBy($user)
            ->withProperties($properties)
            ->log($activityLogEnum->value);
    }
}
