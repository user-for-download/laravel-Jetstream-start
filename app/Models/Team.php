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

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $personal_team
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $owner
 * @property-read Collection<int, \App\Models\TeamInvitation> $teamInvitations
 * @property-read int|null $team_invitations_count
 * @property-read \App\Models\Membership|null $membership
 * @property-read Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\TeamFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team wherePersonalTeam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Team extends JetstreamTeam
{
    use HasFactory;

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

    /**
     * The "booted" method of the model.
     * Refactoring: Ensure team cleanup handles members and invitations.
     */
    protected static function booted(): void
    {
        static::deleting(function (Team $team): void {
            // 1. Remove all members
            $team->users()->detach();

            // 2. Delete all pending invitations
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

    /**
     * Проверить, имеет ли пользователь одну из ролей в команде
     *
     * @param  array<string|RoleEnum>  $roles
     */
    public function userHasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->userHasRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить, имеет ли пользователь все указанные разрешения
     *
     * @param  array<string|PermissionEnum>  $permissions
     */
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

    /**
     * Проверить, имеет ли пользователь хотя бы одно разрешение
     *
     * @param  array<string|PermissionEnum>  $permissions
     */
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

    /**
     * Получить всех администраторов команды
     */
    public function getAdministrators(): Collection
    {
        return $this->allUsers()->filter(fn (\App\Models\User $user): bool => $this->userHasRole($user, RoleEnum::ADMIN) || $this->isOwner($user));
    }

    /**
     * Получить всех пользователей с определенной ролью
     */
    public function getUsersByRole(string|RoleEnum $role): Collection
    {
        return $this->allUsers()->filter(fn (\App\Models\User $user): bool => $this->userHasRole($user, $role));
    }

    /**
     * Проверить, является ли команда личной
     */
    public function isPersonal(): bool
    {
        return (bool) $this->personal_team;
    }

    /**
     * Проверить, является ли пользователь владельцем команды
     */
    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Получить количество членов команды (включая владельца)
     */
    public function getMembersCount(): int
    {
        return $this->allUsers()->count();
    }

    /**
     * Получить матрицу доступа для команды
     *
     * @return array<string, array<string, mixed>>
     */
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

    /**
     * Преобразовать в строку
     */
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

    /**
     * Магический метод для строкового представления
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
