<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Team extends JetstreamTeam
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'name',
        'personal_team',
    ];

    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => 'Team has been '.$eventName);
    }

    protected static function booted(): void
    {
        static::deleting(function (Team $team): void {
            $team->users()->detach();
            $team->teamInvitations()->delete();
        });
    }

    public function userHasRole(User $user, string|RoleEnum $role): bool
    {
        if (!$this->hasUser($user)) {
            return false;
        }

        $roleName = $role instanceof RoleEnum ? $role->value : $role;

        return $user->teamRole($this)?->key === $roleName;
    }

    public function userHasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->userHasRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    public function userHasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof PermissionEnum
                ? $permission->value
                : $permission;

            if (!$this->userHasPermission($user, $permissionName)) {
                return false;
            }
        }

        return true;
    }

    public function userHasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof PermissionEnum
                ? $permission->value
                : $permission;

            if ($this->userHasPermission($user, $permissionName)) {
                return true;
            }
        }

        return false;
    }

    public function getAdministrators(): Collection
    {
        return $this->allUsers()->filter(fn (\App\Models\User $user): bool => $this->userHasRole($user, RoleEnum::ADMIN) || $this->isOwner($user));
    }

    public function getUsersByRole(string|RoleEnum $role): Collection
    {
        return $this->allUsers()->filter(fn (\App\Models\User $user): bool => $this->userHasRole($user, $role));
    }

    public function isPersonal(): bool
    {
        return (bool) $this->personal_team;
    }

    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function getMembersCount(): int
    {
        return $this->allUsers()->count();
    }

    public function getPermissionsMatrix(): array
    {
        $matrix = [];

        foreach ($this->allUsers() as $user) {
            $role = $user->teamRole($this);
            $matrix[$user->email] = [
                'user_id' => $user->id,
                'role' => $role?->key,
                'role_label' => $role?->name,
                'permissions' => $user->teamPermissions($this),
                'is_owner' => $this->isOwner($user),
            ];
        }

        return $matrix;
    }

    public function toString(): string
    {
        return sprintf(
            'Team{id: %d, name: %s, members: %d, personal: %s}',
            $this->id,
            $this->name,
            $this->getMembersCount(),
            $this->isPersonal() ? 'yes' : 'no'
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
