<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Team;
use Laravel\Jetstream\Role;

trait HasTeamRoles
{
    public function hasAnyTeamRole(Team $team, array $roles): bool
    {
        foreach ($roles as $role) {
            $roleName = $role instanceof RoleEnum ? $role->value : $role;
            if ($this->hasTeamRole($team, $roleName)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllTeamRoles(Team $team, array $roles): bool
    {
        foreach ($roles as $role) {
            $roleName = $role instanceof RoleEnum ? $role->value : $role;
            if (!$this->hasTeamRole($team, $roleName)) {
                return false;
            }
        }

        return true;
    }

    public function isAdminOfTeam(?Team $team = null): bool
    {
        $targetTeam = $team ?? $this->currentTeam;

        if (!$targetTeam) {
            return false;
        }

        // Simplified logic
        if ($this->ownsTeam($targetTeam)) {
            return true;
        }

        return (bool) $this->hasTeamRole($targetTeam, RoleEnum::ADMIN->value);
    }

    public function isTeamAdmin(?Team $team = null): bool
    {
        return $this->isAdminOfTeam($team);
    }

    public function hasAllTeamPermissions(Team $team, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->canInTeam($team, $permission)) {
                return false;
            }
        }

        return true;
    }

    public function hasAnyTeamPermission(Team $team, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->canInTeam($team, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function canInCurrentTeam(string|PermissionEnum $permission): bool
    {
        if (!$this->currentTeam) {
            return false;
        }

        return $this->canInTeam($this->currentTeam, $permission);
    }

    public function hasCurrentTeamRole(string|RoleEnum $role): bool
    {
        if (!$this->currentTeam) {
            return false;
        }

        $roleName = $role instanceof RoleEnum ? $role->value : $role;

        return $this->hasTeamRole($this->currentTeam, $roleName);
    }

    public function isCurrentTeamAdmin(): bool
    {
        return $this->isAdminOfTeam($this->currentTeam);
    }

    public function getCurrentTeamRole(): ?Role
    {
        return $this->currentTeam
            ? $this->teamRole($this->currentTeam)
            : null;
    }

    public function getCurrentTeamPermissions(): array
    {
        return $this->currentTeam
            ? $this->teamPermissions($this->currentTeam)
            : [];
    }

    public function canInTeam(Team $team, string|PermissionEnum $permission): bool
    {
        $permissionName = $permission instanceof PermissionEnum
            ? $permission->value
            : $permission;

        return $this->hasTeamPermission($team, $permissionName);
    }

    public function hasRoleInTeam(Team $team, string|RoleEnum $role): bool
    {
        $roleName = $role instanceof RoleEnum ? $role->value : $role;

        return $this->hasTeamRole($team, $roleName);
    }

    public function getTeamsCount(): int
    {
        return $this->allTeams()->count();
    }

    public function getOwnedTeamsCount(): int
    {
        return $this->ownedTeams->count();
    }

    public function switchToTeamById(int $teamId): bool
    {
        $team = $this->allTeams()->firstWhere('id', $teamId);

        if ($team) {
            $this->switchTeam($team);

            return true;
        }

        return false;
    }

    public function getAdminTeams(): \Illuminate\Support\Collection
    {
        return $this->allTeams()->filter(fn ($team) => $this->isAdminOfTeam($team));
    }

    public function isCurrentTeamPersonal(): bool
    {
        return $this->currentTeam?->personal_team ?? false;
    }

    public function __toString(): string
    {
        return sprintf(
            'User{id: %d, name: %s, email: %s}',
            $this->id ?? 0,
            $this->name,
            $this->email
        );
    }
}
