<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Team;

if (!function_exists('formatBytes')) {
    /**
     * Format bytes to human readable string
     *
     * @param  int|float  $bytes  Number of bytes
     * @param  int  $precision  Decimal precision
     * @return string Formatted string (e.g., "1.23 MB")
     */
    function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision).' '.$units[$pow];
    }

}

if (!function_exists('current_team')) {
    /**
     * Получить текущую команду пользователя
     */
    function current_team(): ?Team
    {
        return auth()->user()?->currentTeam;
    }
}

if (!function_exists('team_can')) {
    /**
     * Проверить разрешение в текущей команде
     */
    function team_can(string|PermissionEnum $permission): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->canInCurrentTeam($permission);
    }
}

if (!function_exists('team_has_role')) {
    /**
     * Проверить роль в текущей команде
     */
    function team_has_role(string|RoleEnum $role): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasCurrentTeamRole($role);
    }
}

if (!function_exists('is_team_owner')) {
    /**
     * Проверить, является ли текущий пользователь владельцем команды
     */
    function is_team_owner(): bool
    {
        $user = auth()->user();
        $team = current_team();

        return $user && $team instanceof \App\Models\Team && $user->ownsTeam($team);
    }
}

if (!function_exists('is_team_admin')) {
    /**
     * Проверить, является ли текущий пользователь администратором команды
     */
    function is_team_admin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->isCurrentTeamAdmin();
    }
}

if (!function_exists('current_team_role')) {
    /**
     * Получить роль текущего пользователя в команде
     */
    function current_team_role(): ?\Laravel\Jetstream\Role
    {
        return auth()->user()?->getCurrentTeamRole();
    }
}

if (!function_exists('current_team_permissions')) {
    /**
     * Получить разрешения текущего пользователя в команде
     */
    function current_team_permissions(): array
    {
        return auth()->user()?->getCurrentTeamPermissions() ?? [];
    }
}
