<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  mixed  $target  (This receives the Team model or arguments array)
     */
    public function before(User $user, string $ability, mixed $target = null): ?bool
    {
        if ($target instanceof Team && $user->ownsTeam($target)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        if ($user->ownsTeam($team)) {
            return true;
        }

        return $user->hasTeamRole($team, RoleEnum::ADMIN->value);
    }

    public function addTeamMember(User $user, Team $team): bool
    {
        if ($user->ownsTeam($team)) {
            return true;
        }

        return $user->hasTeamRole($team, RoleEnum::ADMIN->value);
    }

    public function updateTeamMember(User $user, Team $team): bool
    {
        if ($user->ownsTeam($team)) {
            return true;
        }

        return $user->hasTeamRole($team, RoleEnum::ADMIN->value);
    }

    public function removeTeamMember(User $user, Team $team): bool
    {
        if ($user->ownsTeam($team)) {
            return true;
        }

        return $user->hasTeamRole($team, RoleEnum::ADMIN->value);
    }

    public function delete(User $user, Team $team): Response
    {
        if ($team->isPersonal()) {
            return Response::deny('You cannot delete your personal team.');
        }

        return $user->ownsTeam($team)
            ? Response::allow()
            : Response::deny('Only the team owner can delete the team.');
    }

    /**
     * Determine whether the user can manage team settings.
     */
    public function manageSettings(User $user, Team $team): bool
    {
        if ($user->ownsTeam($team)) {
            return true;
        }

        return $user->hasTeamRole($team, RoleEnum::ADMIN->value);
    }

    /**
     * Determine whether the user can perform actions requiring specific permission.
     */
    public function hasPermission(User $user, Team $team, string|PermissionEnum $permission): bool
    {
        return $user->belongsToTeam($team)
            && $user->hasTeamPermission($team, $permission);
    }

    public function transferOwnership(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }
}
